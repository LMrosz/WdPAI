<?php
/**
 * Wspólny szablon strony: head, nagłówek, treść, stopka i skrypt motywu.
 *
 * Oczekuje zmiennych: $main (gotowy HTML elementu <main>), $pageTitle, $activePage, $flash
 * (komunikat po przekierowaniu albo null) oraz pomocników $t i $e z AppController::render().
 *
 * Teksty dla JavaScriptu (komunikaty walidacji, etykiety) trafiają do skryptu jako JSON w bloku
 * <script type="application/json">, więc zmiana języka obejmuje także komunikaty z przeglądarki.
 */
$jsMessages = json_encode([
    'validation' => $content->get('validation'),
    'forms' => $content->get('forms'),
    'js' => $content->get('js'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
?>
<!doctype html>
<html lang="<?= $t('site.locale') ?>">
<?php require __DIR__ . '/partials/head.html.php'; ?>
<body>
    <?php require __DIR__ . '/partials/header.html.php'; ?>

    <?php if ($flash !== null): ?>
        <div class="page flash">
            <p class="form-status" role="status"><?= $e($flash) ?></p>
        </div>
    <?php endif; ?>

    <?= $main ?>

    <?php require __DIR__ . '/partials/footer.html.php'; ?>

    <script type="application/json" id="js-messages"><?= $jsMessages ?></script>
    <script src="/js/theme.js?v=<?= (int) @filemtime(__DIR__ . '/../js/theme.js') ?>" defer></script>
    <script type="module" src="/js/app.js?v=<?= (int) @filemtime(__DIR__ . '/../js/app.js') ?>"></script>
</body>
</html>
