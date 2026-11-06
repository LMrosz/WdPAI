<main>
    <section class="hero" aria-labelledby="hero-title">
        <div class="page hero-inner">
            <div class="hero-text">
                <p class="eyebrow"><?= $t('home.eyebrow') ?></p>
                <h1 id="hero-title"><?= $t('home.title') ?></h1>
                <p><?= $t('home.intro') ?></p>
                <p class="actions">
                    <a class="button" href="/login"><?= $t('home.cta') ?></a>
                </p>
            </div>
            <dl class="stats">
                <?php foreach ($content->get('home.stats') ?? [] as $stat): ?>
                    <div class="stat-card">
                        <dt><?= $e($stat['label']) ?></dt>
                        <dd><?= $e($stat['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>
</main>
