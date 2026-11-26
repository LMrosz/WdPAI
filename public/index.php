<?php

declare(strict_types=1);

use App\Controller\AboutController;
use App\Controller\AccountController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\SecurityController;
use App\Controller\TastingController;
use App\Controller\WineController;
use App\Controller\WineManagementController;
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
$routing->get('/', DefaultController::class, 'index');

try {
    $routing->dispatch(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI'],
    );
} catch (HttpException $exception) {
    (new ErrorController())->show($exception->statusCode());
} catch (\Throwable $exception) {
    // Szczegóły trafiają do logu serwera, użytkownik widzi tylko ogólną stronę błędu (500).
    error_log((string) $exception);
    (new ErrorController())->show(500);
}
