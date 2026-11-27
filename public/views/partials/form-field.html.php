<?php
/**
 * Pole formularza: etykieta, opcjonalna podpowiedź, kontrolka i komunikat błędu.
 * Błąd jest powiązany z polem przez aria-describedby i aria-invalid.
 *
 * Typ "textarea" daje pole wieloliniowe, typ "select" listę rozwijaną (klucz options: wartość => etykieta),
 * każdy inny typ to zwykły <input>. Opcjonalne klucze: inputmode, rows, rules i maxlength (dla pól bez reguły max_length).
 *
 * Klucz rules to lista reguł walidacji (np. ['required', 'max_length:150']), te same, które sprawdza
 * serwer (Validator::rules). Z niej pole dostaje gwiazdkę wymaganego pola, limit maxlength i atrybut
 * data-rules, który czyta public/js/modules/form-validation.js.
 *
 * @var array{name: string, type: string, label: string, autocomplete?: string, value?: string, hint?: string,
 *     options?: array<string, string>, rules?: list<string>, maxlength?: int, inputmode?: string, rows?: int} $field
 * @var array<string, string> $errors nazwa pola => komunikat
 */
$name = $field['name'];
$error = $errors[$name] ?? null;
$describedBy = implode(' ', array_filter([
    isset($field['hint']) ? $name . '-hint' : null,
    $error !== null ? $name . '-error' : null,
]));
$value = $field['value'] ?? '';
$rules = $field['rules'] ?? [];
$isRequired = in_array('required', $rules, true);
$maxLength = $field['maxlength'] ?? null;

foreach ($rules as $rule) {
    if (str_starts_with($rule, 'max_length:')) {
        $maxLength = (int) substr($rule, strlen('max_length:'));
    }
}

$attributes = ' id="' . $e($name) . '" name="' . $e($name) . '"'
    . (isset($field['autocomplete']) ? ' autocomplete="' . $e($field['autocomplete']) . '"' : '')
    . ($maxLength !== null ? ' maxlength="' . $maxLength . '"' : '')
    . (isset($field['inputmode']) ? ' inputmode="' . $e($field['inputmode']) . '"' : '')
    . ($rules !== [] ? ' data-rules="' . $e(implode(' ', $rules)) . '"' : '')
    . ($field['type'] === 'password' ? ' data-no-trim' : '')
    . ($isRequired ? ' aria-required="true"' : '')
    . ($error !== null ? ' aria-invalid="true"' : '')
    . ($describedBy !== '' ? ' aria-describedby="' . $e($describedBy) . '"' : '');
?>
<div class="form-field">
    <label for="<?= $e($name) ?>"><?= $e($field['label']) ?><?php if ($isRequired): ?> <span class="required-mark" aria-hidden="true">*</span><?php endif; ?></label>
    <?php if (isset($field['hint'])): ?>
        <p class="form-hint" id="<?= $e($name) ?>-hint"><?= $e($field['hint']) ?></p>
    <?php endif; ?>
    <?php if ($field['type'] === 'textarea'): ?>
        <textarea<?= $attributes ?> rows="<?= (int) ($field['rows'] ?? 4) ?>"><?= $e($value) ?></textarea>
    <?php elseif ($field['type'] === 'select'): ?>
        <select<?= $attributes ?>>
            <?php foreach ($field['options'] ?? [] as $optionValue => $optionLabel): ?>
                <option value="<?= $e($optionValue) ?>"<?= (string) $optionValue === $value ? ' selected' : '' ?>><?= $e($optionLabel) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input type="<?= $e($field['type']) ?>"<?= $attributes ?>
            <?php if (isset($field['value'])): ?>value="<?= $e($value) ?>"<?php endif; ?>>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <p class="field-error" id="<?= $e($name) ?>-error"><?= $e($error) ?></p>
    <?php endif; ?>
</div>
