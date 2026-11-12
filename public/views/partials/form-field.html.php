<?php
/**
 * Pole formularza: etykieta, opcjonalna podpowiedź, input i komunikat błędu.
 * Błąd jest powiązany z polem przez aria-describedby i aria-invalid.
 *
 * @var array{name: string, type: string, label: string, autocomplete: string, value?: string, hint?: string} $field
 * @var array<string, string> $errors nazwa pola => komunikat
 */
$name = $field['name'];
$error = $errors[$name] ?? null;
$describedBy = implode(' ', array_filter([
    isset($field['hint']) ? $name . '-hint' : null,
    $error !== null ? $name . '-error' : null,
]));
?>
<div class="form-field">
    <label for="<?= $e($name) ?>"><?= $e($field['label']) ?></label>
    <?php if (isset($field['hint'])): ?>
        <p class="form-hint" id="<?= $e($name) ?>-hint"><?= $e($field['hint']) ?></p>
    <?php endif; ?>
    <input type="<?= $e($field['type']) ?>" id="<?= $e($name) ?>" name="<?= $e($name) ?>"
        autocomplete="<?= $e($field['autocomplete']) ?>"
        <?php if (isset($field['value'])): ?>value="<?= $e($field['value']) ?>"<?php endif; ?>
        <?php if ($error !== null): ?>aria-invalid="true"<?php endif; ?>
        <?php if ($describedBy !== ''): ?>aria-describedby="<?= $e($describedBy) ?>"<?php endif; ?>>
    <?php if ($error !== null): ?>
        <p class="field-error" id="<?= $e($name) ?>-error"><?= $e($error) ?></p>
    <?php endif; ?>
</div>
