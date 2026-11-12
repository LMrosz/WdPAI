<?php
/**
 * Wspólny szablon strony: head, nagłówek, treść, stopka i skrypt motywu.
 *
 * Oczekuje zmiennych: $main (gotowy HTML elementu <main>), $pageTitle, $activePage
 * oraz pomocników $t i $e z AppController::render().
 */
?>
<!doctype html>
<html lang="<?= $t('site.locale') ?>">
<?php require __DIR__ . '/partials/head.html.php'; ?>
<body>
    <?php require __DIR__ . '/partials/header.html.php'; ?>

    <?= $main ?>

    <?php require __DIR__ . '/partials/footer.html.php'; ?>

    <script src="/js/theme.js?v=<?= (int) @filemtime(__DIR__ . '/../js/theme.js') ?>" defer></script>
</body>
</html>
