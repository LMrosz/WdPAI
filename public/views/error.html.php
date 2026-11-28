<main class="page auth">
    <section class="auth-card" aria-labelledby="error-title">
        <h1 id="error-title"><?= $t("errors.$errorKey.title") ?></h1>
        <p><?= $t("errors.$errorKey.text") ?></p>
        <?php if ($returnUrl !== null): ?>
            <p class="auth-back"><a href="<?= $e($returnUrl) ?>"><?= $e($content->get('errors.bad_request.return.' . $returnUrl)) ?></a></p>
        <?php endif; ?>
        <p class="auth-back"><a href="<?= $e($backUrl) ?>"><?= $t("errors.$errorKey.back") ?></a></p>
    </section>
</main>
