<?php

declare(strict_types=1);

use App\Controller\AboutController;
use App\Controller\DefaultController;
use App\Controller\ErrorController;
use App\Controller\SecurityController;
use App\Controller\TastingController;
use App\Controller\WineController;
use App\Exception\HttpException;
use App\Routing;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$routing = new Routing();

$routing->get('/wines', WineController::class, 'index');
$routing->get('/wines/{id}', WineController::class, 'show');
$routing->get('/tastings', TastingController::class, 'index');
$routing->get('/about', AboutController::class, 'index');
$routing->get('/login', SecurityController::class, 'login');
$routing->post('/login', SecurityController::class, 'authenticate');
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
