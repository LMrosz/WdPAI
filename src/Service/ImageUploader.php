<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\UploadException;

/**
 * Bezpieczne przyjmowanie zdjęcia z formularza (Lab 13).
 *
 * Nie ufamy niczemu, co przysłał klient (nazwa, typ MIME z żądania, rozszerzenie). Plik przechodzi kolejno:
 *  1. kod błędu PHP i sprawdzenie, że to naprawdę plik z tego żądania (is_uploaded_file),
 *  2. rozmiar na dysku (najwyżej MAX_BYTES),
 *  3. rzeczywisty typ MIME z zawartości (finfo) — tylko JPEG i PNG,
 *  4. rozszerzenie z nazwy użytkownika — dozwolone jpg, jpeg, png i zgodne z typem z zawartości,
 *  5. getimagesize: plik da się odczytać jako obraz tego samego typu, a jego wymiary mieszczą się w limicie
 *     (chroni przed „bombą dekompresyjną”, czyli małym plikiem rozpakowującym się do ogromnego obrazu).
 *
 * Zapisany plik dostaje własną, losową nazwę (32 znaki szesnastkowe) z rozszerzeniem wynikającym z typu
 * z zawartości, więc nazwa od użytkownika nigdy nie trafia na dysk (brak ../, podwójnych rozszerzeń, kolizji).
 * Do bazy trafia wyłącznie ścieżka względna (np. uploads/wines/3f9a….jpg).
 */
final class ImageUploader
{
    public const MAX_BYTES = 2 * 1024 * 1024;
    public const MAX_SIDE = 6000;
    public const MAX_PIXELS = 24_000_000;

    /** Wzorzec ścieżki, jaką tworzymy; ten sam pilnuje ograniczenie wines_image_path_check w bazie. */
    public const PATH_PATTERN = '#^uploads/wines/[0-9a-f]{32}\.(jpg|png)$#';

    /** Typ MIME z zawartości => rozszerzenie zapisanego pliku. */
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

    /** Rozszerzenie z nazwy od użytkownika => typ MIME, z którym musi być zgodne. */
    private const EXTENSIONS = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

    private const IMAGE_TYPES = ['image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG];

    /** @var \Closure(string): bool */
    private readonly \Closure $isUploadedFile;

    /** @var \Closure(string, string): bool */
    private readonly \Closure $moveFile;

    /**
     * @param string $directory katalog na dysku, do którego trafiają pliki (musi leżeć w public/uploads/wines)
     * @param (\Closure(string): bool)|null $isUploadedFile podmiana is_uploaded_file (testy bez prawdziwego żądania HTTP)
     * @param (\Closure(string, string): bool)|null $moveFile podmiana move_uploaded_file
     */
    public function __construct(
        private readonly string $directory,
        ?\Closure $isUploadedFile = null,
        ?\Closure $moveFile = null,
    ) {
        $this->isUploadedFile = $isUploadedFile ?? is_uploaded_file(...);
        $this->moveFile = $moveFile ?? move_uploaded_file(...);
    }

    public static function create(): self
    {
        return new self(dirname(__DIR__, 2) . '/public/uploads/wines');
    }

    /** Czy użytkownik w ogóle wybrał plik (puste pole pliku to nie błąd). */
    public static function wasSubmitted(mixed $file): bool
    {
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Sprawdza plik i zwraca rozszerzenie, z jakim zostanie zapisany (jpg albo png).
     *
     * @param array<string, mixed> $file element z $_FILES
     * @throws UploadException
     */
    public function validate(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $tmpName = $file['tmp_name'] ?? null;

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new UploadException('too_large');
        }

        if ($error !== UPLOAD_ERR_OK || !is_string($tmpName) || $tmpName === '' || !($this->isUploadedFile)($tmpName)) {
            throw new UploadException('upload_failed', 'PHP upload error ' . var_export($error, true));
        }

        $size = filesize($tmpName);

        if ($size === false || $size === 0) {
            throw new UploadException('invalid_image', 'Empty file');
        }

        if ($size > self::MAX_BYTES) {
            throw new UploadException('too_large');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName);

        if (!is_string($mime) || !isset(self::TYPES[$mime])) {
            throw new UploadException('invalid_type', 'MIME ' . var_export($mime, true));
        }

        $name = is_string($file['name'] ?? null) ? $file['name'] : '';
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!isset(self::EXTENSIONS[$extension]) || self::EXTENSIONS[$extension] !== $mime) {
            throw new UploadException('invalid_extension', 'Extension ' . $extension . ' for ' . $mime);
        }

        $info = @getimagesize($tmpName);

        if ($info === false || $info[2] !== self::IMAGE_TYPES[$mime]) {
            throw new UploadException('invalid_image', 'getimagesize failed or type mismatch');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE
            || $width * $height > self::MAX_PIXELS) {
            throw new UploadException('invalid_image', 'Dimensions ' . $width . 'x' . $height);
        }

        return self::TYPES[$mime];
    }

    /**
     * Sprawdza plik, zapisuje go pod losową nazwą i zwraca ścieżkę względną do public/ (to trafia do bazy).
     *
     * @param array<string, mixed> $file element z $_FILES
     * @throws UploadException
     */
    public function store(array $file): string
    {
        $extension = $this->validate($file);

        if (!is_dir($this->directory) && !@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new UploadException('upload_failed', 'Cannot create upload directory');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        if (!($this->moveFile)((string) $file['tmp_name'], $target)) {
            throw new UploadException('upload_failed', 'Cannot move uploaded file');
        }

        // plik statyczny: czytać może każdy, wykonywać nikt (bez bitu x)
        @chmod($target, 0644);

        return 'uploads/wines/' . $fileName;
    }

    /**
     * Usuwa zapisany wcześniej plik. Ścieżka musi mieć dokładnie taki kształt, jaki tworzy store(),
     * więc nawet zmanipulowana wartość z bazy nie pozwoli skasować niczego poza katalogiem zdjęć.
     */
    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || preg_match(self::PATH_PATTERN, $relativePath) !== 1) {
            return;
        }

        $target = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . basename($relativePath);

        if (is_file($target)) {
            @unlink($target);
        }
    }
}
