/*
 * Przycisk „Pokaż hasło” pod każdym polem hasła. Przełącza type między password i text;
 * stan komunikuje aria-pressed, a etykieta przycisku się nie zmienia (czytnik ekranu mówi
 * „Pokaż hasło, przycisk przełącznika, wciśnięty”).
 */
export function initPasswordToggles(messages) {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'link-button password-toggle';
        button.textContent = messages.forms.show_password;
        button.setAttribute('aria-controls', input.id);
        button.setAttribute('aria-pressed', 'false');

        button.addEventListener('click', () => {
            const reveal = input.type === 'password';

            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
        });

        input.after(button);
    });
}
