<?php
/**
 * Konto zalogowanego użytkownika.
 *
 * @var App\Model\User $user
 * @var string $roleLabel nazwa roli do wyświetlenia
 * @var string $since data założenia konta
 * @var list<string> $permissions opisy uprawnień roli
 * @var bool $canManageWines
 * @var bool $canManageUsers
 */
?>
<main class="page catalog-page">
    <section class="account" aria-labelledby="account-title">
        <header class="catalog-header">
            <h1 id="account-title"><?= $t('account.title') ?></h1>
            <p><?= $t('account.intro') ?></p>
        </header>

        <div class="account-grid">
            <section class="card" aria-labelledby="account-data-title">
                <h2 id="account-data-title"><?= $t('account.data_title') ?></h2>
                <dl class="wine-facts">
                    <div>
                        <dt><?= $t('account.email') ?></dt>
                        <dd><?= $e($user->email->value()) ?></dd>
                    </div>
                    <div>
                        <dt><?= $t('account.name') ?></dt>
                        <dd><?= $e($user->label()) ?></dd>
                    </div>
                    <div>
                        <dt><?= $t('account.role') ?></dt>
                        <dd><?= $e($roleLabel) ?></dd>
                    </div>
                    <div>
                        <dt><?= $t('account.since') ?></dt>
                        <dd><?= $e($since) ?></dd>
                    </div>
                </dl>
            </section>

            <section class="card" aria-labelledby="account-permissions-title">
                <h2 id="account-permissions-title"><?= $t('account.permissions_title') ?></h2>
                <ul class="permission-list">
                    <?php foreach ($permissions as $permission): ?>
                        <li><?= $e($permission) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($canManageWines): ?>
                    <p><a class="button" href="/wines/new"><?= $t('account.add_wine') ?></a></p>
                <?php endif; ?>
                <?php if ($canManageUsers): ?>
                    <p><a class="button" href="/admin/users"><?= $t('account.admin_panel') ?></a></p>
                <?php endif; ?>
            </section>
        </div>

        <form action="/logout" method="post" class="account-logout">
            <?php require __DIR__ . '/partials/csrf-field.html.php'; ?>
            <button type="submit" class="button"><?= $t('nav.logout') ?></button>
        </form>
    </section>
</main>
