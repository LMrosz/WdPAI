/*
 * Reguły walidacji po stronie przeglądarki. Nazwy i parametry są takie same jak w
 * App\Service\Validator (PHP): serwer i przeglądarka dostają ten sam zapis reguł
 * (np. "max_length:64 required") z atrybutu data-rules, więc nie trzeba ich utrzymywać dwa razy.
 *
 * Walidacja w przeglądarce jest tylko wygodą dla użytkownika. O tym, czy dane są poprawne,
 * decyduje serwer, bo żądanie można wysłać z pominięciem JavaScriptu.
 */

/** Nazwy parametrów reguł, potrzebne do podstawienia ich w komunikacie ({min}, {max}). */
const PARAMETER_NAMES = {
    min_length: ['min'],
    max_length: ['max'],
    max_bytes: ['max'],
    integer_between: ['min', 'max'],
    decimal_between: ['min', 'max'],
};

/** Prosty wzorzec adresu e-mail; ostateczną decyzję podejmuje serwer (filter_var). */
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

/** Długość w znakach Unicode (jak mb_strlen w PHP), a nie w jednostkach UTF-16. */
const characterCount = (value) => [...value].length;

/** Reguły poza required pomijają puste pole, tak jak w PHP. */
const checks = {
    required: (value) => value !== '',
    email: (value) => value === '' || EMAIL_PATTERN.test(value),
    min_length: (value, [min]) => value === '' || characterCount(value) >= Number(min),
    max_length: (value, [max]) => characterCount(value) <= Number(max),
    max_bytes: (value, [max]) => new TextEncoder().encode(value).length <= Number(max),
    matches: (value, [otherField], form) => value === '' || value === (form.elements[otherField]?.value ?? ''),
    letter_and_digit: (value) => value === '' || (/\p{L}/u.test(value) && /\d/.test(value)),
    special_character: (value) => value === '' || /[^\p{L}\d\s]/u.test(value),
    in_list: (value, allowed) => value === '' || allowed.includes(value),
    integer_between: (value, [min, max]) => value === ''
        || (/^\d{1,9}$/.test(value) && Number(value) >= Number(min) && Number(value) <= Number(max)),
    decimal_between: (value, [min, max, decimals]) => {
        if (value === '') {
            return true;
        }

        const pattern = new RegExp(`^\\d{1,9}([.,]\\d{1,${Number(decimals)}})?$`);
        const number = Number(value.replace(',', '.'));

        return pattern.test(value) && number >= Number(min) && number <= Number(max);
    },
};

/** "max_length:64" => { name: 'max_length', args: ['64'] } */
export function parseRule(text) {
    const separator = text.indexOf(':');

    if (separator === -1) {
        return { name: text, args: [] };
    }

    return { name: text.slice(0, separator), args: text.slice(separator + 1).split(',') };
}

/** Lista reguł pola z atrybutu data-rules ("required email max_length:254"). */
export function readRules(field) {
    return (field.dataset.rules ?? '').split(/\s+/).filter(Boolean).map(parseRule);
}

/** Wartość do sprawdzenia: bez spacji na brzegach, ale hasła zostają bez zmian (jak na serwerze). */
export function readValue(field) {
    return field.dataset.noTrim === undefined ? field.value.trim() : field.value;
}

/**
 * Sprawdza pole kolejnymi regułami i zwraca pierwszy błąd albo null.
 *
 * @returns {{rule: string, params: Object<string, string>} | null}
 */
export function validateField(field) {
    const value = readValue(field);

    for (const { name, args } of readRules(field)) {
        const check = checks[name];

        if (check === undefined) {
            console.warn(`Nieznana reguła walidacji: ${name}`);
            continue;
        }

        if (!check(value, args, field.form)) {
            const names = PARAMETER_NAMES[name] ?? [];

            return { rule: name, params: Object.fromEntries(names.map((key, index) => [key, args[index]])) };
        }
    }

    return null;
}

/** Nazwa pola, od którego zależy dana reguła matches (np. potwierdzenie hasła), albo null. */
export function readDependency(field) {
    return readRules(field).find((rule) => rule.name === 'matches')?.args[0] ?? null;
}
