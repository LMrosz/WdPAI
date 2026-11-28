<?php
/**
 * @var string $formAction
 * @var array<string, list<string>> $rules reguły walidacji pól (pole => lista reguł)
 * @var array<string, string> $errors nazwa pola => komunikat
 * @var array<string, string> $old wcześniej wpisane wartości
 * @var string|null $formError ogólny błąd formularza (zły adres lub hasło)
 * @var bool $registered czy użytkownik wraca po założeniu konta
 * @var 'sent'|'failed'|null $mailStatus wynik wysyłki wiadomości powitalnej (tylko po rejestracji)
 * @var bool $loggedOut czy użytkownik wraca po wylogowaniu
 */
$fields = [
    ['name' => 'email', 'type' => 'email', 'label' => $content->text('login.email'),
        'autocomplete' => 'username', 'value' => $old['email'] ?? '', 'rules' => $rules['email']],
    ['name' => 'password', 'type' => 'password', 'label' => $content->text('login.password'),
        'autocomplete' => 'current-password', 'rules' => $rules['password']],
];
$labels = array_column($fields, 'label', 'name');
?>
<main class="page auth">
    <section class="auth-card" aria-labelledby="login-title">
        <h1 id="login-title"><?= $t('login.title') ?></h1>
        <?php if ($registered): ?>
            <p class="form-status" role="status"><?= $t('login.registered') ?></p>
            <?php if ($mailStatus === 'sent'): ?>
                <p class="form-status" role="status"><?= $t('login.mail_sent') ?></p>
            <?php elseif ($mailStatus === 'failed'): ?>
                <p class="form-status form-status-warning" role="status"><?= $t('login.mail_failed') ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($loggedOut): ?>
            <p class="form-status" role="status"><?= $t('login.logged_out') ?></p>
        <?php endif; ?>
        <p class="auth-intro"><?= $t('login.intro') ?></p>

        <form action="<?= $e($formAction) ?>" method="post" novalidate data-validate>
            <?php require __DIR__ . '/partials/csrf-field.html.php'; ?>
            <?php if ($formError !== null): ?>
                <div class="form-errors" role="alert">
                    <p class="form-errors-title"><?= $e($formError) ?></p>
                </div>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/form-errors.html.php'; ?>
            <fieldset>
                <legend><?= $t('login.legend') ?></legend>
                <?php foreach ($fields as $field): ?>
                    <?php require __DIR__ . '/partials/form-field.html.php'; ?>
                <?php endforeach; ?>
            </fieldset>
            <button type="submit" class="button button-block"><?= $t('login.submit') ?></button>
        </form>

        <p class="auth-back"><?= $t('login.no_account') ?> <a href="/register"><?= $t('login.register_link') ?></a></p>
        <p class="auth-back"><a href="/"><?= $t('login.back') ?></a></p>
    </section>
</main>
