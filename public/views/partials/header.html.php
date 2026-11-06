<header class="site-header">
    <div class="page actions">
        <p class="brand"><a href="/"><?= $t('site.name') ?></a> <small><?= $t('site.tagline') ?></small></p>
        <nav aria-label="<?= $t('nav.label') ?>">
            <ul>
                <li><a href="/"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>><?= $t('nav.home') ?></a></li>
                <li><a href="/wines"<?= $activePage === 'wines' ? ' aria-current="page"' : '' ?>><?= $t('nav.wines') ?></a></li>
                <li><a href="/tastings"<?= $activePage === 'tastings' ? ' aria-current="page"' : '' ?>><?= $t('nav.tastings') ?></a></li>
                <li><a href="/about"<?= $activePage === 'about' ? ' aria-current="page"' : '' ?>><?= $t('nav.about') ?></a></li>
                <li><a href="/login"<?= $activePage === 'login' ? ' aria-current="page"' : '' ?>><?= $t('nav.login') ?></a></li>
            </ul>
        </nav>
        <button type="button" class="theme-toggle" aria-pressed="false"
            aria-label="<?= $t('theme.to_dark') ?>"
            data-label-to-dark="<?= $t('theme.to_dark') ?>"
            data-label-to-light="<?= $t('theme.to_light') ?>"><?= $t('theme.toggle') ?></button>
    </div>
</header>
