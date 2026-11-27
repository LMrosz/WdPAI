/*
 * Punkt wejścia JavaScriptu aplikacji (ładowany z layoutu jako moduł ES, więc wykonuje się
 * dopiero po zbudowaniu dokumentu, tak jak skrypt z atrybutem defer).
 *
 * Każdy moduł szuka na stronie własnych elementów i nic nie robi, gdy ich nie ma, dlatego jeden
 * plik obsługuje wszystkie widoki. Bez JavaScriptu strona nadal działa: formularze waliduje
 * serwer, a katalog win filtrują linki rodzajów.
 */
import { loadMessages } from './modules/messages.js';
import { initPasswordToggles } from './modules/password-toggle.js';
import { initCharCounters } from './modules/char-counter.js';
import { initFormValidation } from './modules/form-validation.js';
import { initWineSearch } from './modules/wine-search.js';

const messages = loadMessages();

initPasswordToggles(messages);
initCharCounters(messages);
initFormValidation(messages);
initWineSearch(messages);
