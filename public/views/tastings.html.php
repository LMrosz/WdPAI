<main class="page catalog-page">
    <section aria-labelledby="tastings-title">
        <header class="catalog-header">
            <p class="eyebrow"><?= $t('tastings.eyebrow') ?></p>
            <h1 id="tastings-title"><?= $t('tastings.title') ?></h1>
            <p><?= $t('tastings.intro') ?></p>
        </header>

        <ul class="catalog">
            <?php foreach ($tastings as $tasting): ?>
                <li>
                    <article class="card">
                        <?php if ($tasting->badge !== null): ?>
                            <p class="card-badge"><?= $e($tasting->badge) ?></p>
                        <?php endif; ?>
                        <h2><?= $e($tasting->name) ?></h2>
                        <p class="card-meta"><?= $t('tastings.duration_label') ?>: <?= $e($tasting->durationLabel()) ?> • <?= $t('tastings.group_label', ['count' => $tasting->maxGroupSize]) ?></p>
                        <p class="card-text"><?= $e($tasting->description) ?></p>
                        <?php if ($tasting->wines !== []): ?>
                            <p class="card-meta"><?= $t('tastings.wines_label') ?>: <?= $e(implode(', ', array_map(static fn ($wine): string => $wine->title, $tasting->wines))) ?></p>
                        <?php endif; ?>
                        <h3 class="card-subtitle"><?= $t('tastings.sessions_label') ?></h3>
                        <?php if ($tasting->sessions === []): ?>
                            <p class="card-meta"><?= $t('tastings.sessions_empty') ?></p>
                        <?php else: ?>
                            <ul class="card-sessions">
                                <?php foreach ($tasting->sessions as $session): ?>
                                    <li>
                                        <span class="session-date"><?= $e($session->startsAtLabel()) ?></span>
                                        <span class="session-seats"><?= $session->seatsFree() > 0
                                            ? $t('tastings.seats_free', ['free' => $session->seatsFree(), 'total' => $session->seatsTotal])
                                            : $t('tastings.seats_none') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <p class="card-price"><?= $t('tastings.price_per_person', ['price' => $tasting->pricePerPerson]) ?></p>
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
