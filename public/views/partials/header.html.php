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
        <button type="button" class="theme-toggle"
            data-toggle-label="<?= $t('theme.toggle') ?>"
            data-switch-to="<?= $t('theme.switch_to') ?>"
            data-mode-system="<?= $t('theme.mode_system') ?>"
            data-mode-light="<?= $t('theme.mode_light') ?>"
            data-mode-dark="<?= $t('theme.mode_dark') ?>"
            aria-label="<?= $t('theme.toggle') ?>: <?= $t('theme.mode_system') ?>"
            title="<?= $t('theme.toggle') ?>: <?= $t('theme.mode_system') ?>">
            <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zM11 1h2v3h-2zM11 20h2v3h-2zM1 11h3v2H1zM20 11h3v2h-3zM4.2 5.6l1.4-1.4 2.1 2.1-1.4 1.4zM16.3 17.7l1.4-1.4 2.1 2.1-1.4 1.4zM4.2 18.4l2.1-2.1 1.4 1.4-2.1 2.1zM16.3 6.3l2.1-2.1 1.4 1.4-2.1 2.1z"/></svg>
            <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            <svg class="icon-system" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M3 4h18a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-7v2h3v2H7v-2h3v-2H3a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM4 6v9h16V6z"/></svg>
        </button>
    </div>
</header>
