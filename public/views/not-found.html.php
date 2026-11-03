<!doctype html>
<html lang="<?= $t('site.locale') ?>">
<?php require __DIR__ . '/partials/head.html.php'; ?>
<body>
    <?php require __DIR__ . '/partials/header.html.php'; ?>

    <main class="page auth">
        <section class="auth-card" aria-labelledby="error-title">
            <h1 id="error-title"><?= $t('errors.not_found.title') ?></h1>
            <p><?= $t('errors.not_found.text') ?></p>
            <p class="auth-back"><a href="/"><?= $t('errors.not_found.back') ?></a></p>
        </section>
    </main>

    <?php require __DIR__ . '/partials/footer.html.php'; ?>
</body>
</html>
