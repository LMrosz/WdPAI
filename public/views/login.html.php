<?php
/**
 * @var string $formAction
 * @var array<string, string> $errors nazwa pola => komunikat
 * @var array<string, string> $old wcześniej wpisane wartości
 * @var bool $registered czy użytkownik wraca po wysłaniu rejestracji
 */
$fields = [
    ['name' => 'email', 'type' => 'email', 'label' => $content->text('login.email'),
        'autocomplete' => 'username', 'value' => $old['email'] ?? ''],
    ['name' => 'password', 'type' => 'password', 'label' => $content->text('login.password'),
        'autocomplete' => 'current-password'],
];
$labels = array_column($fields, 'label', 'name');
?>
<main class="page auth">
    <section class="auth-card" aria-labelledby="login-title">
        <h1 id="login-title"><?= $t('login.title') ?></h1>
        <?php if ($registered): ?>
            <p class="form-status" role="status"><?= $t('login.registered') ?></p>
        <?php endif; ?>
        <p class="auth-intro"><?= $t('login.intro') ?></p>

        <form action="<?= $e($formAction) ?>" method="post" novalidate>
            <?php require __DIR__ . '/partials/form-errors.html.php'; ?>
            <fieldset>
                <legend><?= $t('login.legend') ?></legend>
                <?php foreach ($fields as $field): ?>
                    <?php require __DIR__ . '/partials/form-field.html.php'; ?>
                <?php endforeach; ?>
                <p class="form-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember"><?= $t('login.remember') ?></label>
                </p>
            </fieldset>
            <button type="submit" class="button button-block"><?= $t('login.submit') ?></button>
        </form>

        <p class="auth-back"><?= $t('login.no_account') ?> <a href="/register"><?= $t('login.register_link') ?></a></p>
        <p class="auth-back"><a href="/"><?= $t('login.back') ?></a></p>
    </section>
</main>
