<?php
/**
 * Formularz dodawania i edycji wina.
 *
 * @var bool $editing true przy edycji, false przy dodawaniu
 * @var string $formAction
 * @var string $cancelUrl
 * @var array<string, string> $categories kod kategorii => etykieta
 * @var array<string, string> $errors nazwa pola => komunikat
 * @var array<string, string> $old wartości pól
 */
// pomocnicze domknięcia: etykieta pola z treści i definicja pola dla partialu form-field
$label = fn (string $name): string => $content->text('wines.form.' . $name);
$text = static function (string $name, string $type = 'text', array $extra = []) use ($label, $old): array {
    return ['name' => $name, 'type' => $type, 'label' => $label($name), 'value' => $old[$name] ?? ''] + $extra;
};

$groups = [
    'basics' => [
        $text('name', 'text', ['required' => true, 'maxlength' => 150]),
        $text('category', 'select', ['required' => true, 'options' => $categories]),
        $text('type', 'text', ['required' => true, 'maxlength' => 60, 'hint' => $label('type_hint')]),
        $text('grape', 'text', ['maxlength' => 80]),
        $text('vintage', 'text', ['inputmode' => 'numeric', 'maxlength' => 4, 'hint' => $label('vintage_hint')]),
        $text('abv', 'text', ['inputmode' => 'decimal', 'maxlength' => 5, 'hint' => $label('abv_hint')]),
        $text('price', 'text', ['required' => true, 'inputmode' => 'decimal', 'maxlength' => 9, 'hint' => $label('price_hint')]),
        $text('award', 'text', ['maxlength' => 100]),
    ],
    'description' => [
        $text('notes', 'textarea', ['required' => true, 'rows' => 3, 'maxlength' => 2000]),
        $text('story', 'textarea', ['rows' => 5, 'maxlength' => 2000]),
    ],
    'tasting' => [
        $text('aroma', 'textarea', ['rows' => 2, 'maxlength' => 2000]),
        $text('palate', 'textarea', ['rows' => 2, 'maxlength' => 2000]),
        $text('finish', 'textarea', ['rows' => 2, 'maxlength' => 2000]),
        $text('serving', 'text', ['maxlength' => 150]),
        $text('cellaring', 'text', ['maxlength' => 200]),
    ],
    'pairings' => [
        $text('pairings', 'textarea', ['rows' => 4, 'hint' => $label('pairings_hint')]),
    ],
];

$labels = [];

foreach ($groups as $fields) {
    foreach ($fields as $definition) {
        $labels[$definition['name']] = $definition['label'];
    }
}
?>
<main class="page auth">
    <section class="auth-card auth-card-wide" aria-labelledby="wine-form-title">
        <h1 id="wine-form-title"><?= $t($editing ? 'wines.form.edit_title' : 'wines.form.create_title') ?></h1>
        <p class="auth-intro"><?= $t('wines.form.required_note') ?></p>

        <form action="<?= $e($formAction) ?>" method="post" novalidate>
            <?php require __DIR__ . '/partials/csrf-field.html.php'; ?>
            <?php require __DIR__ . '/partials/form-errors.html.php'; ?>

            <?php foreach ($groups as $groupKey => $fields): ?>
                <fieldset>
                    <legend><?= $t('wines.form.group_' . $groupKey) ?></legend>
                    <?php foreach ($fields as $field): ?>
                        <?php require __DIR__ . '/partials/form-field.html.php'; ?>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>

            <button type="submit" class="button button-block"><?= $t($editing ? 'wines.form.save' : 'wines.form.create') ?></button>
        </form>

        <p class="auth-back"><a href="<?= $e($cancelUrl) ?>"><?= $t('wines.form.cancel') ?></a></p>
    </section>
</main>
