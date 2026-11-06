<!doctype html>
<html lang="<?= $t('site.locale') ?>">
<?php require __DIR__ . '/partials/head.html.php'; ?>
<body>
    <?php require __DIR__ . '/partials/header.html.php'; ?>

    <main class="page catalog-page">
        <section aria-labelledby="tastings-title">
            <header class="catalog-header">
                <p class="eyebrow"><?= $t('tastings.eyebrow') ?></p>
                <h1 id="tastings-title"><?= $t('tastings.title') ?></h1>
                <p><?= $t('tastings.intro') ?></p>
            </header>

            <ul class="catalog">
                <?php foreach ($offers as $offer): ?>
                    <li>
                        <article class="card">
                            <?php if (!empty($offer['badge'])): ?>
                                <p class="card-badge"><?= $e($offer['badge']) ?></p>
                            <?php endif; ?>
                            <h2><?= $e($offer['name']) ?></h2>
                            <p class="card-meta"><?= $t('tastings.duration_label') ?>: <?= $e($offer['duration']) ?> • <?= $e($offer['group']) ?></p>
                            <p class="card-text"><?= $e($content->fill($offer['description'])) ?></p>
                            <p class="card-price"><?= $e($offer['price']) ?></p>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="steps-section" aria-labelledby="steps-title">
            <h2 id="steps-title"><?= $t('tastings.steps_title') ?></h2>
            <ol class="steps">
                <?php foreach ($steps as $step): ?>
                    <li><?= $e($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="cta-box" aria-labelledby="cta-title">
            <h2 id="cta-title"><?= $t('tastings.cta_title') ?></h2>
            <p><?= $t('tastings.cta_text') ?></p>
            <p><a class="button" href="/login"><?= $t('tastings.cta') ?></a></p>
        </section>
    </main>

    <?php require __DIR__ . '/partials/footer.html.php'; ?>
</body>
</html>
