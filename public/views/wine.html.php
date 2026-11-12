<?php
/**
 * Szczegóły jednego wina.
 *
 * @var App\Model\Wine $wine
 * @var list<App\Model\Wine> $related
 */
$facts = [
    'wines.detail.type' => $wine->type,
    'wines.detail.grape' => $wine->grape,
    'wines.detail.vintage' => $wine->vintage,
    'wines.detail.abv' => $wine->abv,
    'wines.detail.serving' => $wine->serving,
    'wines.detail.cellaring' => $wine->cellaring,
];
$tasting = [
    'wines.detail.aroma' => $wine->aroma,
    'wines.detail.palate' => $wine->palate,
    'wines.detail.finish' => $wine->finish,
];
?>
<main class="page catalog-page wine--<?= $e($wine->category) ?>">
    <nav class="breadcrumb" aria-label="<?= $t('wines.detail.breadcrumb_label') ?>">
        <ol>
            <li><a href="/wines"><?= $t('nav.wines') ?></a></li>
            <li aria-current="page"><?= $e($wine->title()) ?></li>
        </ol>
    </nav>

    <article aria-labelledby="wine-title">
        <header class="wine-hero">
            <div class="wine-hero-text">
                <?php if ($wine->award !== null && $wine->award !== ''): ?>
                    <p class="card-badge"><?= $e($wine->award) ?></p>
                <?php endif; ?>
                <h1 id="wine-title"><?= $e($wine->title()) ?></h1>
                <p class="wine-lead"><?= $e($content->fill($wine->notes)) ?></p>
                <p class="wine-price">
                    <small><?= $t('wines.price_label') ?></small>
                    <?= $e($wine->price) ?>
                </p>
            </div>
            <div class="wine-stage" aria-hidden="true">
                <?php if ($wine->vintage !== null): ?>
                    <span class="wine-stage-year"><?= $e($wine->vintage) ?></span>
                <?php endif; ?>
                <?php require __DIR__ . '/partials/wine-bottle.html.php'; ?>
            </div>
        </header>

        <div class="wine-layout">
            <div class="wine-body">
                <?php if ($wine->story !== null): ?>
                    <section class="wine-story" aria-labelledby="wine-story-title">
                        <h2 id="wine-story-title"><?= $t('wines.detail.story_title') ?></h2>
                        <p><?= $e($content->fill($wine->story)) ?></p>
                    </section>
                <?php endif; ?>

                <?php if ($wine->hasTastingNotes()): ?>
                    <section aria-labelledby="wine-tasting-title">
                        <h2 id="wine-tasting-title"><?= $t('wines.detail.tasting_title') ?></h2>
                        <dl class="wine-tasting">
                            <?php foreach ($tasting as $labelKey => $value): ?>
                                <?php if ($value !== null): ?>
                                    <div>
                                        <dt><?= $t($labelKey) ?></dt>
                                        <dd><?= $e($value) ?></dd>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endif; ?>

                <?php if ($wine->pairings !== []): ?>
                    <section aria-labelledby="wine-pairings-title">
                        <h2 id="wine-pairings-title"><?= $t('wines.detail.pairings_title') ?></h2>
                        <ul class="wine-pairings">
                            <?php foreach ($wine->pairings as $dish): ?>
                                <li><?= $e($dish) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="card wine-aside" aria-labelledby="wine-facts-title">
                <h2 id="wine-facts-title"><?= $t('wines.detail.facts_title') ?></h2>
                <dl class="wine-facts">
                    <?php foreach ($facts as $labelKey => $value): ?>
                        <?php if ($value !== null && $value !== ''): ?>
                            <div>
                                <dt><?= $t($labelKey) ?></dt>
                                <dd><?= $e($value) ?></dd>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </dl>
            </aside>
        </div>
    </article>

    <?php if ($related !== []): ?>
        <section class="wine-related" aria-labelledby="wine-related-title">
            <h2 id="wine-related-title"><?= $t('wines.detail.related_title') ?></h2>
            <ul class="catalog">
                <?php foreach ($related as $wine): ?>
                    <li>
                        <?php $headingTag = 'h3'; require __DIR__ . '/partials/wine-card.html.php'; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>
