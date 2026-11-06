<!doctype html>
<html lang="<?= $t('site.locale') ?>">
<?php require __DIR__ . '/partials/head.html.php'; ?>
<body>
    <?php require __DIR__ . '/partials/header.html.php'; ?>

    <main class="page catalog-page">
        <section aria-labelledby="wines-title">
            <header class="catalog-header">
                <p class="eyebrow"><?= $t('wines.eyebrow') ?></p>
                <h1 id="wines-title"><?= $t('wines.title') ?></h1>
                <p><?= $t('wines.intro') ?></p>
            </header>

            <?php if ($wines === []): ?>
                <p><?= $t('wines.empty') ?></p>
            <?php else: ?>
                <ul class="catalog">
                    <?php foreach ($wines as $wine): ?>
                        <li>
                            <article class="wine-card">
                                <?php if (!empty($wine['award'])): ?>
                                    <p class="wine-badge"><?= $e($wine['award']) ?></p>
                                <?php endif; ?>
                                <h2><?= $e(trim($wine['name'] . ' ' . ($wine['vintage'] ?? ''))) ?></h2>
                                <p class="wine-meta"><?= $e(implode(' • ', array_filter([$wine['type'] ?? null, $wine['grape'] ?? null, $wine['abv'] ?? null]))) ?></p>
                                <p class="wine-notes"><?= $e($content->fill($wine['notes'])) ?></p>
                                <p class="wine-price">
                                    <small><?= $t('wines.price_label') ?></small>
                                    <?= $e($wine['price']) ?>
                                </p>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>

    <?php require __DIR__ . '/partials/footer.html.php'; ?>
</body>
</html>
