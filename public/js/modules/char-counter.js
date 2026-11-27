/*
 * Licznik znaków pod polami wieloliniowymi z limitem maxlength. Liczba aktualizuje się przy każdym
 * wpisanym znaku; blisko limitu (90%) zmienia kolor. Licznik nie jest regionem aria-live, żeby
 * czytnik ekranu nie odczytywał każdej zmiany, a opisuje pole przez aria-describedby.
 */
import { format } from './messages.js';

const WARNING_RATIO = 0.9;

export function initCharCounters(messages) {
    document.querySelectorAll('textarea[maxlength]').forEach((textarea) => {
        const max = Number(textarea.getAttribute('maxlength'));
        const counter = document.createElement('p');

        counter.className = 'char-counter';
        counter.id = `${textarea.id}-counter`;

        const update = () => {
            const count = textarea.value.length;

            counter.textContent = format(messages.js.counter, { count, max });
            counter.classList.toggle('is-near-limit', count >= max * WARNING_RATIO);
        };

        textarea.addEventListener('input', update);
        textarea.after(counter);
        textarea.setAttribute(
            'aria-describedby',
            [textarea.getAttribute('aria-describedby'), counter.id].filter(Boolean).join(' '),
        );
        update();
    });
}
