<?php

declare(strict_types=1);

use App\Controller\AboutController;
use App\Controller\AccountController;
use App\Controller\AdminController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\ReservationApiController;
use App\Controller\SecurityController;
use App\Controller\TastingController;
use App\Controller\UserApiController;
use App\Controller\WineController;
use App\Controller\WineManagementController;
use App\Exception\ApiException;
use App\Exception\HttpException;
use App\Routing;
use App\Service\Session;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Sesja jest konfigurowana i uruchamiana raz, zanim jakikolwiek kontroler odczyta $_SESSION.
Session::start();

$routing = new Routing();

// Trasy to biała lista: tylko te adresy i metody są obsługiwane. Stałe fragmenty (/wines/new)
// muszą stać przed trasami z parametrem (/wines/{id}), bo pierwsza pasująca trasa wygrywa.
$routing->get('/wines', WineController::class, 'index');
$routing->get('/wines/new', WineManagementController::class, 'create');
$routing->post('/wines', WineManagementController::class, 'store');
$routing->get('/wines/{id}', WineController::class, 'show');
$routing->get('/wines/{id}/edit', WineManagementController::class, 'edit');
$routing->post('/wines/{id}/edit', WineManagementController::class, 'update');
$routing->post('/wines/{id}/delete', WineManagementController::class, 'destroy');
$routing->get('/tastings', TastingController::class, 'index');
$routing->get('/about', AboutController::class, 'index');
$routing->get('/login', SecurityController::class, 'login');
$routing->post('/login', SecurityController::class, 'authenticate');
$routing->get('/register', SecurityController::class, 'register');
$routing->post('/register', SecurityController::class, 'createAccount');
$routing->post('/logout', SecurityController::class, 'logout');
$routing->get('/account', AccountController::class, 'show');
$routing->get('/admin/users', AdminController::class, 'index');
$routing->get('/api/tasting-sessions', ReservationApiController::class, 'sessions');
$routing->post('/api/reservations', ReservationApiController::class, 'create');
$routing->delete('/api/reservations/{id}', ReservationApiController::class, 'cancel');
$routing->get('/api/users', UserApiController::class, 'index');
$routing->delete('/api/users/{id}', UserApiController::class, 'delete');
$routing->patch('/api/users/{id}/block', UserApiController::class, 'block');
$routing->patch('/api/users/{id}/role', UserApiController::class, 'role');
$routing->get('/', DefaultController::class, 'index');

// Żądania do /api/ dostają błędy w formacie JSON, reszta aplikacji strony błędów HTML.
$isApiRequest = str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/api/');

try {
    $routing->dispatch(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI'],
    );
} catch (HttpException $exception) {
    $errors = new ErrorController();

    if ($isApiRequest) {
        $errors->showJson($exception->statusCode(), $exception instanceof ApiException ? $exception->reason() : null);
    } else {
        $errors->show($exception->statusCode());
    }
} catch (\Throwable $exception) {
    // Szczegóły trafiają do logu serwera, użytkownik widzi tylko ogólną stronę błędu (500).
    error_log((string) $exception);

    if ($isApiRequest) {
        (new ErrorController())->showJson(500);
    } else {
        (new ErrorController())->show(500);
    }
}
