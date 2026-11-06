/*
 * Przełącznik motywu: trzy tryby — systemowy, jasny, ciemny.
 *
 * Tryb „systemowy” = brak zapisu w localStorage; wtedy motyw wynika z prefers-color-scheme
 * i zmienia się razem z ustawieniem systemu. Tryby „jasny” i „ciemny” to świadomy wybór
 * użytkownika zapisany pod kluczem `theme`. Motyw przed pierwszym renderem ustawia osobny
 * skrypt w <head> (partials/head.html.php), a ten plik obsługuje przycisk i późniejsze zmiany.
 */
const storageKey = 'theme';
const modes = ['system', 'light', 'dark'];
const root = document.documentElement;
const themeToggle = document.querySelector('.theme-toggle');
const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

/** Zapisany wybór użytkownika ('light' | 'dark') albo null, gdy obowiązuje ustawienie systemu. */
function getSavedTheme() {
    try {
        const theme = localStorage.getItem(storageKey);
        return theme === 'light' || theme === 'dark' ? theme : null;
    } catch (error) {
        return null;
    }
}

function saveTheme(theme) {
    try {
        localStorage.setItem(storageKey, theme);
    } catch (error) {
        // Motyw nadal działa w bieżącym dokumencie.
    }
}

function clearSavedTheme() {
    try {
        localStorage.removeItem(storageKey);
    } catch (error) {
        // Bez dostępu do pamięci motyw wróci do systemowego po odświeżeniu.
    }
}

function getSystemTheme() {
    return colorSchemeQuery.matches ? 'dark' : 'light';
}

/** Aktualny tryb przełącznika: 'system' (bez zapisu), 'light' albo 'dark'. */
function getMode() {
    return getSavedTheme() ?? 'system';
}

/** Teksty przycisku pochodzą z atrybutów data-* (config/content.php), nie z kodu. */
function modeName(mode) {
    return themeToggle?.dataset[`mode${mode[0].toUpperCase()}${mode.slice(1)}`] ?? mode;
}

function syncThemeToggle(mode) {
    if (!themeToggle) {
        return;
    }

    const next = modes[(modes.indexOf(mode) + 1) % modes.length];
    const toggleLabel = themeToggle.dataset.toggleLabel ?? '';
    const switchTo = themeToggle.dataset.switchTo ?? '';

    // Przycisk ma tylko ikonę (słońce, księżyc, system); jej znaczenie niesie etykieta,
    // która podaje aktualny tryb i akcję po kliknięciu. Ta sama treść trafia do podpowiedzi.
    const label = `${toggleLabel}: ${modeName(mode)}. ${switchTo} ${modeName(next)}`;

    themeToggle.setAttribute('aria-label', label);
    themeToggle.setAttribute('title', label);
}

/**
 * Ustawia motyw dla wybranego trybu. `persist` zapisuje świadomy wybór (jasny/ciemny)
 * albo czyści zapis dla trybu systemowego; przy synchronizacji z innej karty jest false.
 */
function applyMode(mode, persist = true) {
    const theme = mode === 'system' ? getSystemTheme() : mode;

    if (persist) {
        if (mode === 'system') {
            clearSavedTheme();
        } else {
            saveTheme(mode);
        }
    }

    root.dataset.theme = theme;
    root.dataset.themeMode = mode;
    syncThemeToggle(mode);

    // Inne skrypty (np. wykresy) mogą zareagować na zmianę motywu.
    document.dispatchEvent(new CustomEvent('themechange', { detail: { mode, theme } }));
}

themeToggle?.addEventListener('click', () => {
    const next = modes[(modes.indexOf(getMode()) + 1) % modes.length];
    applyMode(next);
});

// W trybie systemowym motyw podąża za ustawieniem systemu; zapis użytkownika ma pierwszeństwo.
colorSchemeQuery.addEventListener('change', () => {
    if (getMode() === 'system') {
        applyMode('system', false);
    }
});

// Zmiana w innej karcie lub oknie tej samej przeglądarki (zdarzenie storage) od razu się odbija tutaj.
window.addEventListener('storage', (event) => {
    if (event.key === null || event.key === storageKey) {
        applyMode(getMode(), false);
    }
});

applyMode(getMode(), false);
