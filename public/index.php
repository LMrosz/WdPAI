<?php

declare(strict_types=1);

use App\Controller\AboutController;
use App\Controller\DefaultController;
use App\Controller\ProjectController;
use App\Controller\SecurityController;
use App\Controller\TastingController;
use App\Controller\WineController;
use App\Routing;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$routing = new Routing();

$routing->get('/projects', ProjectController::class, 'index');
$routing->get('/projects/{id}', ProjectController::class, 'show');
$routing->get('/wines', WineController::class, 'index');
$routing->get('/tastings', TastingController::class, 'index');
$routing->get('/about', AboutController::class, 'index');
$routing->get('/login', SecurityController::class, 'login');
$routing->post('/login', SecurityController::class, 'authenticate');
$routing->get('/', DefaultController::class, 'index');

$routeFound = $routing->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
);

if (!$routeFound) {
    http_response_code(404);
    (new DefaultController())->notFound();
}
