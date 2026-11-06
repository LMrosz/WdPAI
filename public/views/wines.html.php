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
                        <article class="card">
                            <?php if (!empty($wine['award'])): ?>
                                <p class="card-badge"><?= $e($wine['award']) ?></p>
                            <?php endif; ?>
                            <h2><?= $e(trim($wine['name'] . ' ' . ($wine['vintage'] ?? ''))) ?></h2>
                            <p class="card-meta"><?= $e(implode(' • ', array_filter([$wine['type'] ?? null, $wine['grape'] ?? null, $wine['abv'] ?? null]))) ?></p>
                            <p class="card-text"><?= $e($content->fill($wine['notes'])) ?></p>
                            <p class="card-price">
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
