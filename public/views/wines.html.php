<?php
/**
 * Katalog win.
 *
 * @var list<App\Model\Wine> $wines
 * @var array<string, string> $filters etykiety filtrów (klucz all + kategorie)
 * @var string $activeFilter
 * @var bool $canManageWines czy zalogowana rola może dodawać wina
 */
?>
<main class="page catalog-page">
    <section aria-labelledby="wines-title">
        <header class="catalog-header">
            <p class="eyebrow"><?= $t('wines.eyebrow') ?></p>
            <h1 id="wines-title"><?= $t('wines.title') ?></h1>
            <p><?= $t('wines.intro') ?></p>
            <?php if ($canManageWines): ?>
                <p><a class="button" href="/wines/new"><?= $t('wines.form.add_link') ?></a></p>
            <?php endif; ?>
        </header>

        <?php // pole szukania pokazuje dopiero JavaScript (public/js/modules/wine-search.js); bez niego działają same filtry ?>
        <div class="catalog-search" data-wine-search hidden>
            <label for="wine-search"><?= $t('wines.search_label') ?></label>
            <input type="search" id="wine-search" autocomplete="off"
                placeholder="<?= $t('wines.search_placeholder') ?>" aria-describedby="wine-search-status">
            <p class="form-hint" id="wine-search-status" role="status"></p>
        </div>

        <nav class="filter" aria-label="<?= $t('wines.filter_label') ?>">
            <ul>
                <?php foreach ($filters as $key => $label): ?>
                    <li>
                        <a href="<?= $key === 'all' ? '/wines' : '/wines?type=' . $e($key) ?>"<?= $key === $activeFilter ? ' aria-current="true"' : '' ?>><?= $e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <?php if ($wines === []): ?>
            <p><?= $t($activeFilter === 'all' ? 'wines.empty' : 'wines.empty_filtered') ?></p>
        <?php else: ?>
            <ul class="catalog" data-wine-list>
                <?php foreach ($wines as $wine): ?>
                    <li>
                        <?php require __DIR__ . '/partials/wine-card.html.php'; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
