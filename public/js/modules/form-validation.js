/*
 * Dynamiczna walidacja formularzy z atrybutem data-validate.
 *
 * Zdarzenia:
 *  - change (po opuszczeniu zmienionego pola) sprawdza to pole,
 *  - input poprawia komunikat na bieżąco, ale dopiero gdy pole było już sprawdzane albo ma błąd,
 *    żeby nie krzyczeć przy pierwszym wpisanym znaku,
 *  - submit sprawdza wszystkie pola i przy błędach zatrzymuje wysyłkę, pokazuje podsumowanie
 *    i ustawia fokus na pierwszym błędnym polu.
 * Poprawny formularz jest wysyłany normalnie; przycisk jest wtedy blokowany, żeby nie wysłać
 * danych dwa razy.
 */
import { format } from './messages.js';
import { readDependency, validateField } from './validation-rules.js';
import { clearError, hasError, renderSummary, showError } from './field-feedback.js';

export function initFormValidation(messages) {
    document.querySelectorAll('form[data-validate]').forEach((form) => setUpForm(form, messages));
}

function setUpForm(form, messages) {
    const fields = [...form.elements].filter((element) => element.dataset.rules !== undefined);
    const checked = new Set();

    /** Sprawdza jedno pole i pokazuje albo usuwa komunikat. Zwraca true, gdy pole jest poprawne. */
    function checkField(field) {
        const error = validateField(field);

        checked.add(field);

        if (error === null) {
            clearError(field);

            return true;
        }

        showError(field, format(messages.validation[error.rule] ?? error.rule, error.params));

        return false;
    }

    /** Pola z regułą matches zależą od innego pola (np. potwierdzenie hasła od hasła). */
    function recheckDependents(changed) {
        fields
            .filter((field) => field !== changed && readDependency(field) === changed.name && checked.has(field))
            .forEach(checkField);
    }

    form.addEventListener('change', (event) => {
        if (fields.includes(event.target)) {
            checkField(event.target);
            recheckDependents(event.target);
        }
    });

    form.addEventListener('input', (event) => {
        const field = event.target;

        if (fields.includes(field)) {
            if (checked.has(field) || hasError(field)) {
                checkField(field);
            }

            recheckDependents(field);
        }
    });

    form.addEventListener('submit', (event) => {
        const invalid = fields.filter((field) => !checkField(field));

        if (invalid.length > 0) {
            event.preventDefault();
            renderSummary(form, invalid.map((field) => ({ field, message: errorText(field) })), messages.forms.error_summary);
            invalid[0].focus();

            return;
        }

        lockSubmitButton(form, messages.js.saving);
    });

    // Po powrocie przyciskiem Wstecz strona może wrócić z pamięci podręcznej z zablokowanym przyciskiem.
    window.addEventListener('pageshow', () => unlockSubmitButton(form));
}

const errorText = (field) => document.getElementById(`${field.id}-error`)?.textContent ?? '';

function lockSubmitButton(form, busyLabel) {
    const button = form.querySelector('button[type="submit"]');

    if (button && !button.disabled) {
        button.dataset.label = button.textContent;
        button.textContent = busyLabel;
        button.disabled = true;
    }
}

function unlockSubmitButton(form) {
    const button = form.querySelector('button[type="submit"]');

    if (button?.dataset.label !== undefined) {
        button.textContent = button.dataset.label;
        button.disabled = false;
        delete button.dataset.label;
    }
}
