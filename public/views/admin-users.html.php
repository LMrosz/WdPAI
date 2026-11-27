<?php
/**
 * Panel kont użytkowników. Lista nie jest renderowana na serwerze: public/js/users.js pobiera ją
 * z GET /api/users (Fetch API) i rysuje wiersze, a zmiany wysyła przez DELETE i PATCH.
 *
 * @var bool $canManageAdmins czy zalogowana osoba jest właścicielem (może nadawać role)
 * @var list<string> $assignableRoles kody ról, które właściciel może nadać
 */
?>
<main class="page catalog-page">
    <section class="admin" aria-labelledby="admin-title"
        data-users-panel
        data-api-url="/api/users"
        data-csrf-token="<?= $e($csrf()) ?>"
        data-roles="<?= $e(json_encode($assignableRoles, JSON_THROW_ON_ERROR)) ?>">
        <header class="catalog-header">
            <h1 id="admin-title" tabindex="-1"><?= $t('admin.title') ?></h1>
            <p><?= $t('admin.intro') ?></p>
        </header>

        <?php /* wczytywanie i wynik każdej akcji; role="status" sprawia, że czytnik ekranu odczyta zmianę. Ukryty do czasu uruchomienia skryptu */ ?>
        <p class="form-status" role="status" data-users-status hidden></p>

        <div class="form-errors" role="alert" data-users-error hidden>
            <p class="form-errors-title" data-users-error-text></p>
            <button type="button" class="button button--small" data-users-retry hidden><?= $t('admin.retry') ?></button>
        </div>

        <ul class="user-list" data-users-list aria-label="<?= $t('admin.list_label') ?>"></ul>

        <noscript><p class="form-status"><?= $t('admin.no_javascript') ?></p></noscript>
    </section>
</main>
