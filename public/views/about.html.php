<main class="page catalog-page">
    <header class="catalog-header">
        <p class="eyebrow"><?= $t('about.eyebrow') ?></p>
        <h1 id="about-title"><?= $t('about.title') ?></h1>
        <p><?= $t('about.intro') ?></p>
    </header>

    <section class="about-section" aria-labelledby="history-title">
        <h2 id="history-title"><?= $t('about.history_title') ?></h2>
        <?php foreach ($history as $paragraph): ?>
            <p class="prose"><?= $e($content->fill($paragraph)) ?></p>
        <?php endforeach; ?>
    </section>

    <section class="about-section" aria-labelledby="facts-title">
        <h2 id="facts-title"><?= $t('about.facts_title') ?></h2>
        <dl class="facts">
            <?php foreach ($facts as $fact): ?>
                <div class="stat-card">
                    <dt><?= $e($fact['label']) ?></dt>
                    <dd><?= $e($fact['value']) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </section>

    <section class="about-section" aria-labelledby="estate-title">
        <h2 id="estate-title"><?= $t('about.estate_title') ?></h2>
        <p class="prose"><?= $t('about.estate_intro') ?></p>
        <ul class="feature-list">
            <?php foreach ($features as $feature): ?>
                <li><?= $e($feature) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="cta-box" aria-labelledby="visit-title">
        <h2 id="visit-title"><?= $t('about.cta_title') ?></h2>
        <p class="actions">
            <a class="button" href="/wines"><?= $t('about.cta_wines') ?></a>
            <a class="button" href="/tastings"><?= $t('about.cta_tastings') ?></a>
        </p>
    </section>
</main>
