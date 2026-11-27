/*
 * Rezerwacja degustacji na /tastings przez Fetch API, bez przeładowania strony:
 *   - GET    /api/tasting-sessions   odświeża liczbę wolnych miejsc (przy wejściu na stronę, po każdej
 *                                    akcji i gdy użytkownik wraca do karty przeglądarki),
 *   - POST   /api/reservations       rezerwuje miejsca, ciało {"sessionId": 3, "seats": 2},
 *   - DELETE /api/reservations/{id}  anuluje własną rezerwację.
 *
 * Strona działa bez tego skryptu: terminy i wolne miejsca wypisuje serwer, a niezalogowany widzi
 * tylko je (i odsyłacz do logowania). Skrypt dopisuje formularz rezerwacji pod każdym terminem
 * wyłącznie zalogowanym (token CSRF jest na stronie tylko wtedy). Dane z serwera trafiają do DOM
 * przez textContent, a napisy pochodzą z config/content.php (sekcje reservations i tastings).
 * Serwer jest źródłem prawdy: limity miejsc sprawdza transakcja w bazie, a nie atrybuty pola.
 */
import { loadMessages, format } from './modules/messages.js';

const REQUEST_TIMEOUT_MS = 15000;

const messages = loadMessages();
const text = messages.reservations ?? {};
const seatsText = messages.tastings ?? {};

/** Błąd żądania do API: message to tekst do pokazania, status to kod HTTP (0 = brak połączenia). */
class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.status = status;
    }
}

function element(tag, className = '', content = '') {
    const node = document.createElement(tag);

    if (className !== '') {
        node.className = className;
    }

    node.textContent = content;

    return node;
}

function initReservations(root) {
    const sessionsUrl = root.dataset.sessionsUrl;
    const reservationsUrl = root.dataset.reservationsUrl;
    const csrfToken = root.dataset.csrfToken ?? null;
    const status = root.querySelector('[data-reservations-status]');
    const errorBox = root.querySelector('[data-reservations-error]');
    const rows = new Map([...root.querySelectorAll('[data-session-id]')].map((row) => [Number(row.dataset.sessionId), row]));

    /** Ostatnio pobrane dane terminów; potrzebne do podpisów przycisków i komunikatów. */
    const known = new Map();

    async function request(method, url, body = undefined) {
        const headers = { Accept: 'application/json' };
        const options = { method, headers, credentials: 'same-origin' };

        if (csrfToken !== null) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        if (typeof AbortSignal.timeout === 'function') {
            options.signal = AbortSignal.timeout(REQUEST_TIMEOUT_MS);
        }

        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        let response;

        try {
            response = await fetch(url, options);
        } catch (error) {
            // brak sieci, przerwane połączenie albo przekroczony czas
            throw new ApiError(text.error_network, 0);
        }

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            // odpowiedź nie jest JSON-em (np. awaria serwera www)
        }

        if (!response.ok) {
            throw new ApiError(payload?.error?.message ?? text.error_unknown, response.status);
        }

        return payload;
    }

    function setStatus(message) {
        status.textContent = message;
        status.hidden = message === '';
    }

    function setError(message) {
        errorBox.textContent = message;
        errorBox.hidden = message === '';
    }

    /** Wolne miejsca w tym samym zapisie co tekst z serwera (tastings.seats_free i seats_none). */
    function seatsLabel(session) {
        return session.seatsFree > 0
            ? format(seatsText.seats_free, { free: session.seatsFree, total: session.seatsTotal })
            : seatsText.seats_none;
    }

    /** Aktualizuje wiersz terminu: liczbę wolnych miejsc oraz formularz albo informację o rezerwacji. */
    function renderSession(session, keepFocus = false) {
        const row = rows.get(session.id);

        if (row === undefined) {
            return;
        }

        known.set(session.id, session);
        row.querySelector('[data-session-seats]').textContent = seatsLabel(session);
        row.dataset.maxSeats = String(session.maxSeats);

        if (csrfToken === null) {
            return;
        }

        // wyłączony (disabled) przycisk traci fokus, więc o jego zachowaniu decyduje wywołujący (keepFocus);
        // sprawdzamy też fokus w chwili renderowania, na wypadek odświeżenia w tle
        const hadFocus = keepFocus || row.contains(document.activeElement);

        row.querySelector('[data-session-booking]')?.remove();

        const booking = session.myReservation !== null ? buildMine(session) : session.seatsFree > 0 ? buildForm(session) : null;

        if (booking !== null) {
            row.append(booking);
        }

        if (hadFocus) {
            (booking?.querySelector('button, input') ?? row).focus?.();
        }
    }

    function buildForm(session) {
        const form = element('form', 'session-booking');
        const max = Math.min(session.maxSeats, session.seatsFree);
        const id = `seats-${session.id}`;
        const label = element('label', '', text.seats_label);
        const input = element('input');
        const button = element('button', 'button button--small', text.book);

        form.dataset.sessionBooking = '';
        form.noValidate = true;
        label.htmlFor = id;
        input.id = id;
        input.type = 'number';
        input.inputMode = 'numeric';
        input.min = '1';
        input.max = String(max);
        input.value = '1';
        button.type = 'submit';
        button.setAttribute('aria-label', format(text.book_label, { date: session.label }));

        form.append(label, input, button);
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            book(session, input, form);
        });

        return form;
    }

    function buildMine(session) {
        const box = element('div', 'session-booking session-booking--mine');
        const info = element('span', '', format(text.mine, { seats: session.myReservation.seats }));
        const button = element('button', 'button button--small button-danger', text.cancel);

        box.dataset.sessionBooking = '';
        button.type = 'button';
        button.setAttribute('aria-label', format(text.cancel_label, { date: session.label }));
        button.addEventListener('click', () => cancel(session, box));
        box.append(info, button);

        return box;
    }

    function setBusy(container, busy) {
        container.setAttribute('aria-busy', String(busy));

        for (const control of container.querySelectorAll('button, input')) {
            control.disabled = busy;
        }
    }

    async function book(session, input, form) {
        const seats = Number(input.value);
        const max = Number(input.max);

        setError('');
        setStatus('');

        if (!Number.isInteger(seats) || seats < 1 || seats > max) {
            setError(format(text.invalid_seats, { max }));
            input.focus();

            return;
        }

        const hadFocus = form.contains(document.activeElement);

        setBusy(form, true);

        try {
            const payload = await request('POST', reservationsUrl, { sessionId: session.id, seats });

            renderSession(payload.session, hadFocus);
            setStatus(format(text.done_booked, { date: payload.session.label, seats: payload.reservation.seats }));
        } catch (error) {
            setBusy(form, false);
            setError(error.message);

            if (hadFocus) {
                form.querySelector('button')?.focus();
            }

            if (error.status === 409 || error.status === 404) {
                // dane na stronie są już nieaktualne (np. miejsca zajął ktoś inny), więc pobieramy je od nowa
                await refresh();
            }
        }
    }

    async function cancel(session, box) {
        const hadFocus = box.contains(document.activeElement);

        setError('');
        setStatus('');
        setBusy(box, true);

        try {
            const payload = await request('DELETE', `${reservationsUrl}/${session.myReservation.id}`);

            renderSession(payload.session, hadFocus);
            setStatus(format(text.done_cancelled, { date: payload.session.label }));
        } catch (error) {
            setBusy(box, false);
            setError(error.message);

            if (hadFocus) {
                box.querySelector('button')?.focus();
            }

            if (error.status === 404) {
                await refresh();
            }
        }
    }

    /** Pobiera aktualne terminy; błąd odświeżenia nie psuje strony, bo serwer wypisał już ich początkowy stan. */
    async function refresh() {
        try {
            const payload = await request('GET', sessionsUrl);

            for (const session of payload.sessions) {
                renderSession(session);
            }
        } catch (error) {
            setError(`${text.error_load} ${error.message}`);
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    });

    refresh();
}

const root = document.querySelector('[data-reservations]');

if (root !== null) {
    initReservations(root);
}
