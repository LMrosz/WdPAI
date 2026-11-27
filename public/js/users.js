/*
 * Panel kont użytkowników (/admin/users) oparty na Fetch API. Strona przychodzi z serwera bez listy:
 * ten skrypt pobiera ją z GET /api/users, rysuje wiersze i wysyła zmiany bez przeładowania strony:
 *   - PATCH  /api/users/{id}/block  blokowanie i odblokowanie konta,
 *   - PATCH  /api/users/{id}/role   zmiana roli (przycisk widzi tylko właściciel winnicy),
 *   - DELETE /api/users/{id}        usunięcie konta (po potwierdzeniu w wierszu).
 *
 * Stany interfejsu: wczytywanie, lista, pusta lista, błąd sieci i błąd odpowiedzi API (z przyciskiem
 * ponowienia). Dane z serwera trafiają do strony wyłącznie przez textContent, nigdy przez innerHTML,
 * więc znaczniki w nazwie konta nie wykonają się jako HTML. Wszystkie napisy pochodzą z config/content.php
 * (sekcje admin i roles w #js-messages). Serwer jest źródłem prawdy: flagi canBlock, canDelete
 * i canChangeRole tylko ukrywają przyciski, a reguły i tak sprawdza API przy każdej akcji.
 */
import { loadMessages, format } from './modules/messages.js';

const REQUEST_TIMEOUT_MS = 15000;

const messages = loadMessages();
const text = messages.admin ?? {};
const roleNames = messages.roles ?? {};

/** Błąd żądania do API: message to tekst do pokazania, status to kod HTTP (0 = brak połączenia). */
class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.status = status;
    }
}

/** Element z klasą i tekstem (textContent, więc bez interpretowania HTML). */
function element(tag, className = '', content = '') {
    const node = document.createElement(tag);

    if (className !== '') {
        node.className = className;
    }

    node.textContent = content;

    return node;
}

function button(label, action, className, ariaLabel = null) {
    const node = element('button', className, label);

    node.type = 'button';
    node.dataset.action = action;

    if (ariaLabel !== null) {
        node.setAttribute('aria-label', ariaLabel);
    }

    return node;
}

function initUsersPanel(panel) {
    const apiUrl = panel.dataset.apiUrl;
    const csrfToken = panel.dataset.csrfToken;
    const assignableRoles = JSON.parse(panel.dataset.roles ?? '[]');
    const title = panel.querySelector('h1');
    const status = panel.querySelector('[data-users-status]');
    const errorBox = panel.querySelector('[data-users-error]');
    const errorText = panel.querySelector('[data-users-error-text]');
    const retryButton = panel.querySelector('[data-users-retry]');
    const list = panel.querySelector('[data-users-list]');

    let users = [];

    /** Jedno miejsce wysyłające żądania: dokłada nagłówki i zamienia każdą porażkę na ApiError. */
    async function request(method, url, body = undefined) {
        const headers = { Accept: 'application/json', 'X-CSRF-Token': csrfToken };
        const options = { method, headers, credentials: 'same-origin' };

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

        if (response.status === 204) {
            return null;
        }

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            // odpowiedź nie jest JSON-em (np. awaria serwera www), zostaje komunikat ogólny
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

    function showError(message, canRetry = false) {
        errorText.textContent = message;
        retryButton.hidden = !canRetry;
        errorBox.hidden = false;
    }

    function clearError() {
        errorBox.hidden = true;
        errorText.textContent = '';
    }

    /** Wygasła sesja albo zablokowane konto (401): dalsza praca w panelu nie ma sensu. */
    function handleSessionEnd(error) {
        if (error.status === 401) {
            window.location.assign('/login');

            return true;
        }

        return false;
    }

    async function loadUsers() {
        clearError();
        setStatus(text.loading);
        list.replaceChildren();
        list.setAttribute('aria-busy', 'true');

        try {
            const payload = await request('GET', apiUrl);

            users = payload.users;
            renderList();
        } catch (error) {
            if (handleSessionEnd(error)) {
                return;
            }

            setStatus('');
            showError(`${text.error_load} ${error.message}`, true);
        } finally {
            list.removeAttribute('aria-busy');
        }
    }

    function renderList() {
        list.replaceChildren(...users.map(buildRow));
        setStatus(users.length === 0 ? text.empty : format(text.summary, { count: users.length }));
    }

    function buildRow(user) {
        const row = element('li', 'card user-row');

        row.dataset.userId = String(user.id);
        row.classList.toggle('user-row--blocked', user.blocked);

        const main = element('div', 'user-main');
        const name = element('p', 'user-name');

        name.append(element('span', '', user.name));

        if (user.isSelf) {
            name.append(element('span', 'user-tag', text.you));
        }

        if (user.blocked) {
            name.append(element('span', 'user-tag user-tag--blocked', text.blocked));
        }

        const since = new Date(user.createdAt).toLocaleDateString(document.documentElement.lang || 'pl');

        main.append(
            name,
            element('p', 'user-email', user.email),
            element('p', 'user-meta', format(text.since, { date: since })),
        );

        row.append(main, buildRoleCell(user), buildActions(user));

        return row;
    }

    function buildRoleCell(user) {
        const cell = element('div', 'user-role');

        if (!user.canChangeRole) {
            cell.append(element('span', 'card-badge', roleNames[user.role] ?? user.role));

            return cell;
        }

        const select = element('select');

        select.dataset.action = 'role';
        select.setAttribute('aria-label', format(text.role_label, { email: user.email }));

        for (const code of assignableRoles) {
            const option = element('option', '', roleNames[code] ?? code);

            option.value = code;
            option.selected = code === user.role;
            select.append(option);
        }

        select.addEventListener('change', () => changeRole(user, select));
        cell.append(select);

        return cell;
    }

    function buildActions(user) {
        const actions = element('div', 'user-actions');

        if (user.canBlock) {
            actions.append(button(
                user.blocked ? text.unblock : text.block,
                'block',
                'button button--small',
                format(user.blocked ? text.unblock_label : text.block_label, { email: user.email }),
            ));
        }

        if (user.canDelete) {
            actions.append(button(
                text.delete,
                'delete',
                'button button--small button-danger',
                format(text.delete_label, { email: user.email }),
            ));
        }

        actions.addEventListener('click', (event) => {
            const action = event.target.closest('button')?.dataset.action;

            if (action === 'block') {
                toggleBlock(user);
            } else if (action === 'delete') {
                askToDelete(user, actions);
            }
        });

        return actions;
    }

    function rowOf(user) {
        return list.querySelector(`[data-user-id="${user.id}"]`);
    }

    /** Wyłącza kontrolki wiersza na czas żądania, żeby nie wysłać tej samej akcji dwa razy. */
    function setBusy(row, busy) {
        row.setAttribute('aria-busy', String(busy));

        for (const control of row.querySelectorAll('button, select')) {
            control.disabled = busy;
        }
    }

    /** Wspólny przebieg akcji: blokada wiersza, wykonanie, komunikat błędu albo wyniku. */
    async function perform(user, action, focusTarget) {
        const row = rowOf(user);

        clearError();
        setBusy(row, true);

        try {
            await action();
        } catch (error) {
            if (handleSessionEnd(error)) {
                return;
            }

            setBusy(row, false);
            showError(error.message);

            if (error.status === 404) {
                // konto zniknęło w międzyczasie (np. usunął je drugi administrator)
                removeUser(user);
            }

            return;
        }

        rowOf(user)?.querySelector(`[data-action="${focusTarget}"]`)?.focus();
    }

    /** Wstawia zaktualizowane konto na miejsce starego wiersza. */
    function replaceUser(updated) {
        users = users.map((user) => (user.id === updated.id ? updated : user));
        rowOf(updated).replaceWith(buildRow(updated));
    }

    function removeUser(user) {
        const row = rowOf(user);
        const next = row?.nextElementSibling ?? row?.previousElementSibling;

        users = users.filter((candidate) => candidate.id !== user.id);
        row?.remove();

        (next?.querySelector('button, select') ?? title).focus();
    }

    function toggleBlock(user) {
        return perform(user, async () => {
            const { user: updated } = await request('PATCH', `${apiUrl}/${user.id}/block`, { blocked: !user.blocked });

            replaceUser(updated);
            setStatus(format(updated.blocked ? text.done_blocked : text.done_unblocked, { email: updated.email }));
        }, 'block');
    }

    function changeRole(user, select) {
        return perform(user, async () => {
            try {
                const { user: updated } = await request('PATCH', `${apiUrl}/${user.id}/role`, { role: select.value });

                replaceUser(updated);
                setStatus(format(text.done_role, { email: updated.email, role: roleNames[updated.role] ?? updated.role }));
            } catch (error) {
                select.value = user.role;
                throw error;
            }
        }, 'role');
    }

    /** Usunięcie jest nieodwracalne, więc przycisk zamienia się na pytanie z potwierdzeniem w tym samym wierszu. */
    function askToDelete(user, actions) {
        const original = [...actions.children];
        const confirmation = element('div', 'user-confirm');
        const question = element('p', '', format(text.delete_question, { email: user.email }));
        const confirm = button(text.delete_confirm, 'confirm-delete', 'button button--small button-danger');
        const cancel = button(text.delete_cancel, 'cancel-delete', 'button button--small');

        question.id = `delete-question-${user.id}`;
        confirmation.setAttribute('role', 'group');
        confirmation.setAttribute('aria-labelledby', question.id);
        confirmation.append(question, confirm, cancel);
        actions.replaceChildren(confirmation);

        cancel.addEventListener('click', (event) => {
            event.stopPropagation();
            actions.replaceChildren(...original);
            actions.querySelector('[data-action="delete"]')?.focus();
        });

        confirm.addEventListener('click', (event) => {
            event.stopPropagation();
            deleteUser(user);
        });

        cancel.focus();
    }

    function deleteUser(user) {
        const row = rowOf(user);

        return perform(user, async () => {
            await request('DELETE', `${apiUrl}/${user.id}`);

            removeUser(user);
            setStatus(format(text.done_deleted, { email: user.email }));
        }, 'none').finally(() => {
            // przy błędzie (np. 403) wiersz został, więc przywracamy przyciski zamiast pytania
            if (row.isConnected) {
                row.querySelector('.user-actions')?.replaceWith(buildActions(users.find((item) => item.id === user.id) ?? user));
            }
        });
    }

    retryButton.addEventListener('click', loadUsers);
    loadUsers();
}

const panel = document.querySelector('[data-users-panel]');

if (panel !== null) {
    initUsersPanel(panel);
}
