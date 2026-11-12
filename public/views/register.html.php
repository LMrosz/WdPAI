<?php
/**
 * @var string $formAction
 * @var array<string, string> $errors nazwa pola => komunikat
 * @var array<string, string> $old wcześniej wpisane wartości (bez haseł)
 * @var int $passwordMinLength
 */
$fields = [
    ['name' => 'email', 'type' => 'email', 'label' => $content->text('register.email'),
        'autocomplete' => 'email', 'value' => $old['email'] ?? ''],
    ['name' => 'password', 'type' => 'password', 'label' => $content->text('register.password'),
        'autocomplete' => 'new-password',
        'hint' => $content->text('register.password_hint', ['min' => $passwordMinLength])],
    ['name' => 'password_confirmation', 'type' => 'password',
        'label' => $content->text('register.password_confirmation'), 'autocomplete' => 'new-password'],
];
$labels = array_column($fields, 'label', 'name');
?>
<main class="page auth">
    <section class="auth-card" aria-labelledby="register-title">
        <h1 id="register-title"><?= $t('register.title') ?></h1>
        <p class="auth-intro"><?= $t('register.intro') ?></p>

        <form action="<?= $e($formAction) ?>" method="post" novalidate>
            <?php require __DIR__ . '/partials/form-errors.html.php'; ?>
            <fieldset>
                <legend><?= $t('register.legend') ?></legend>
                <?php foreach ($fields as $field): ?>
                    <?php require __DIR__ . '/partials/form-field.html.php'; ?>
                <?php endforeach; ?>
            </fieldset>
            <button type="submit" class="button button-block"><?= $t('register.submit') ?></button>
        </form>

        <p class="auth-back"><?= $t('register.have_account') ?> <a href="/login"><?= $t('register.login_link') ?></a></p>
        <p class="auth-back"><a href="/"><?= $t('register.back') ?></a></p>
    </section>
</main>
