/*
 * Pokazywanie błędów walidacji w DOM: komunikat przy polu, aria-invalid, powiązanie z polem przez
 * aria-describedby oraz podsumowanie nad formularzem. Znaczniki są takie same jak te, które
 * generuje serwer (partials/form-field i partials/form-errors), więc wygląd się nie zmienia.
 * Tekst wstawiamy przez textContent, nigdy przez innerHTML.
 */

const errorId = (field) => `${field.id}-error`;

/** Nazwa pola z jego etykiety, bez gwiazdki wymaganego pola. */
export function labelOf(field) {
    const label = field.form.querySelector(`label[for="${field.id}"]`)?.cloneNode(true);
    label?.querySelector('.required-mark')?.remove();

    return label?.textContent.trim() ?? field.name;
}

function addToDescribedBy(field, id) {
    const ids = new Set((field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
    ids.add(id);
    field.setAttribute('aria-describedby', [...ids].join(' '));
}

function removeFromDescribedBy(field, id) {
    const ids = (field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter((item) => item && item !== id);

    if (ids.length === 0) {
        field.removeAttribute('aria-describedby');
    } else {
        field.setAttribute('aria-describedby', ids.join(' '));
    }
}

export function showError(field, message) {
    let note = document.getElementById(errorId(field));

    if (note === null) {
        note = document.createElement('p');
        note.className = 'field-error';
        note.id = errorId(field);
        field.closest('.form-field').append(note);
    }

    note.textContent = message;
    field.setAttribute('aria-invalid', 'true');
    addToDescribedBy(field, note.id);
    updateSummaryEntry(field, message);
}

export function clearError(field) {
    document.getElementById(errorId(field))?.remove();
    field.removeAttribute('aria-invalid');
    removeFromDescribedBy(field, errorId(field));
    removeSummaryEntry(field);
}

export function hasError(field) {
    return field.getAttribute('aria-invalid') === 'true';
}

const summaryOf = (form) => form.querySelector('[data-form-summary]');

/** Wiersz podsumowania: link do pola i komunikat. */
function createSummaryEntry(field, message) {
    const item = document.createElement('li');
    const link = document.createElement('a');

    item.dataset.for = field.id;
    link.href = `#${field.id}`;
    link.textContent = labelOf(field);
    item.append(link, `: ${message}`);

    return item;
}

function updateSummaryEntry(field, message) {
    const list = summaryOf(field.form)?.querySelector('ul');

    if (!list) {
        return;
    }

    const entry = list.querySelector(`[data-for="${field.id}"]`);
    const fresh = createSummaryEntry(field, message);

    if (entry) {
        entry.replaceWith(fresh);
    } else {
        list.append(fresh);
    }
}

function removeSummaryEntry(field) {
    const summary = summaryOf(field.form);

    summary?.querySelector(`[data-for="${field.id}"]`)?.remove();

    if (summary && summary.querySelector('li') === null) {
        summary.remove();
    }
}

/**
 * Podsumowanie błędów nad formularzem po nieudanej próbie wysłania. Serwer renderuje ten sam
 * blok (z atrybutem data-form-summary), więc przeglądarka tylko go uzupełnia albo tworzy.
 *
 * @param {HTMLFormElement} form
 * @param {Array<{field: HTMLElement, message: string}>} errors
 * @param {string} title
 */
export function renderSummary(form, errors, title) {
    let summary = summaryOf(form);

    if (summary === null) {
        summary = document.createElement('div');
        summary.className = 'form-errors';
        summary.setAttribute('role', 'alert');
        summary.dataset.formSummary = '';
        form.querySelector('fieldset').before(summary);
    }

    const heading = document.createElement('p');
    const list = document.createElement('ul');

    heading.className = 'form-errors-title';
    heading.textContent = title;
    list.append(...errors.map(({ field, message }) => createSummaryEntry(field, message)));
    summary.replaceChildren(heading, list);
}
