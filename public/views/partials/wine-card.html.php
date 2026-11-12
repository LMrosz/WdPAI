<?php
/**
 * Karta wina na liście (katalog i sekcja "Zobacz także").
 *
 * @var App\Model\Wine $wine
 * @var string $headingTag poziom nagłówka karty (domyślnie h2)
 */
$headingTag = $headingTag ?? 'h2';
?>
<article class="card card--wine wine--<?= $e($wine->category) ?>">
    <div class="card-visual" aria-hidden="true">
        <?php require __DIR__ . '/wine-bottle.html.php'; ?>
    </div>
    <div class="card-body">
        <?php if ($wine->award !== null && $wine->award !== ''): ?>
            <p class="card-badge"><?= $e($wine->award) ?></p>
        <?php endif; ?>
        <<?= $headingTag ?>><a href="/wines/<?= $e($wine->id) ?>"><?= $e($wine->title()) ?></a></<?= $headingTag ?>>
        <p class="card-meta"><?= $e(implode(' • ', array_filter([$wine->type, $wine->grape, $wine->abv]))) ?></p>
        <p class="card-text"><?= $e($content->fill($wine->notes)) ?></p>
        <p class="card-price">
            <small><?= $t('wines.price_label') ?></small>
            <?= $e($wine->price) ?>
        </p>
    </div>
</article>
