/*
 * Teksty interfejsu dla JavaScriptu. Nie są wpisane w kod: layout wypisuje je jako JSON
 * z config/content.php (sekcje validation, forms i js) w <script type="application/json" id="js-messages">.
 */

/** @returns {{validation: Object<string, string>, forms: Object<string, string>, js: Object<string, string>}} */
export function loadMessages() {
    const source = document.getElementById('js-messages');

    try {
        return JSON.parse(source?.textContent ?? '{}');
    } catch (error) {
        console.error('Nie udało się odczytać tekstów interfejsu.', error);
        return { validation: {}, forms: {}, js: {} };
    }
}

/** Podstawia {nazwa} z params, np. format('Wpisz co najmniej {min} znaków.', { min: 8 }). */
export function format(template = '', params = {}) {
    return template.replace(/\{(\w+)\}/g, (placeholder, name) => String(params[name] ?? placeholder));
}
