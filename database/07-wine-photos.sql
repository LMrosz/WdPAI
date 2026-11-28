-- VinoKeep: zdjęcia win (Lab 13).
--
-- Skrypt dodaje do tabeli wines kolumnę image_path z adresem zdjęcia. W bazie jest tylko ścieżka
-- względem katalogu public/ (np. uploads/wines/3f9a....jpg), a sam plik leży na dysku. Brak zdjęcia to NULL,
-- wtedy aplikacja pokazuje ilustrację butelki.
-- Ograniczenie CHECK dopuszcza wyłącznie ścieżki, jakie tworzy ImageUploader (losowa nazwa
-- z 32 znaków szesnastkowych i rozszerzenie jpg albo png), więc do bazy nie trafi np. "../../etc/passwd".
--
-- Przy pierwszym starcie z pustym wolumenem pg-data uruchamia się sam, po 01-06.
-- Na działającej bazie wykonaj go raz:
--   docker compose exec db psql -U app -d app -v ON_ERROR_STOP=1 -f /docker-entrypoint-initdb.d/07-wine-photos.sql
-- Skrypt można uruchomić ponownie bez szkody.

ALTER TABLE wines ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL;

ALTER TABLE wines DROP CONSTRAINT IF EXISTS wines_image_path_check;
ALTER TABLE wines ADD CONSTRAINT wines_image_path_check
    CHECK (image_path IS NULL OR image_path ~ '^uploads/wines/[0-9a-f]{32}\.(jpg|png)$');
