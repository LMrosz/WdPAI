<header class="site-header">
    <div class="page actions">
        <p class="brand"><a href="/"><?= $t('site.name') ?></a> <small><?= $t('site.tagline') ?></small></p>
        <nav aria-label="<?= $t('nav.label') ?>">
            <ul>
                <li><a href="/"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>><?= $t('nav.home') ?></a></li>
                <li><a href="/wines"<?= $activePage === 'wines' ? ' aria-current="page"' : '' ?>><?= $t('nav.wines') ?></a></li>
                <li><a href="/tastings"<?= $activePage === 'tastings' ? ' aria-current="page"' : '' ?>><?= $t('nav.tastings') ?></a></li>
                <li><a href="/login"<?= $activePage === 'login' ? ' aria-current="page"' : '' ?>><?= $t('nav.login') ?></a></li>
            </ul>
        </nav>
    </div>
</header>
