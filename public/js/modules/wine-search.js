/*
 * Wyszukiwarka w katalogu win: filtruje karty na bieżąco, bez przeładowania strony i bez
 * pytania serwera. Szuka w nazwie, rodzaju, szczepie i opisie karty (nie w cenie), nie zwraca
 * uwagi na wielkość liter ani polskie znaki (np. „zolte” znajdzie „żółte”). Działa w obrębie
 * kategorii wybranej linkiem filtra. Liczba wyników trafia do regionu role="status".
 */
import { format } from './messages.js';

/** Części karty, w których szukamy (partials/wine-card.html.php). */
const SEARCHABLE_PARTS = '.card-badge, .card-body :is(h2, h3), .card-meta, .card-text';

/** Małe litery bez znaków diakrytycznych; „ł” nie rozkłada się w Unicode, więc zamieniamy je ręcznie. */
const normalize = (text) => text.toLowerCase().replaceAll('ł', 'l').normalize('NFD').replace(/\p{M}/gu, '');

export function initWineSearch(messages) {
    const search = document.querySelector('[data-wine-search]');
    const list = document.querySelector('[data-wine-list]');

    if (search === null || list === null) {
        return;
    }

    const input = search.querySelector('input');
    const status = search.querySelector('[role="status"]');
    const items = [...list.children].map((item) => ({
        element: item,
        text: normalize([...item.querySelectorAll(SEARCHABLE_PARTS)].map((part) => part.textContent).join(' ')),
    }));

    function applyFilter() {
        const query = input.value.trim();
        const words = normalize(query).split(/\s+/).filter(Boolean);
        let shown = 0;

        for (const { element, text } of items) {
            const matches = words.every((word) => text.includes(word));

            element.hidden = !matches;
            shown += matches ? 1 : 0;
        }

        if (query === '') {
            status.textContent = '';
        } else if (shown === 0) {
            status.textContent = format(messages.js.search_empty, { query });
        } else {
            status.textContent = format(messages.js.search_status, { shown, total: items.length });
        }
    }

    input.addEventListener('input', applyFilter);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && input.value !== '') {
            input.value = '';
            applyFilter();
        }
    });

    search.hidden = false;
}
