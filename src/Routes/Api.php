<?php

use App\Middleware\Authorizer;
use App\Controllers\HealthController;
use App\Controllers\GameController;
use App\Controllers\RoleController;
use App\Controllers\UserController;

// Health check route - No auth required
$app->get('/health', [HealthController::class, 'check']);

// Protected routes group
$app->group('/api', function($app) {
    // Game routes
    $app->post('/game/broadcast', [GameController::class, 'broadcastRequest']);
    $app->get('/game/online', [GameController::class, 'onlineListRequest']);
    $app->post('/game/email', [GameController::class, 'emailRequest']);
    $app->put('/game/rate', [GameController::class, 'setDoubleRate']);

    // Role routes
    $app->get('/role/{id}', [RoleController::class, 'characterRequest']);
    $app->put('/role/{id}', [RoleController::class, 'characterResponse']);
    $app->get('/role/name/{name}', [RoleController::class, 'characternameRequest']);
    $app->get('/role/{id}/faction', [RoleController::class, 'factionRequest']);
    $app->get('/role/{id}/faction/user', [RoleController::class, 'userfactionRequest']);
    $app->post('/role/{id}/bank/reset', [RoleController::class, 'resetBankRequest']);
    $app->put('/role/{id}/name', [RoleController::class, 'renameRequest']);
    $app->put('/role/{id}/meridian', [RoleController::class, 'meridianFull']);
    $app->put('/role/{id}/title', [RoleController::class, 'titleFull']);
    $app->post('/role/{id}/ban', [RoleController::class, 'banRole']);
    $app->post('/role/{id}/mute', [RoleController::class, 'muteRole']);
    $app->post('/role/{id}/ban/account', [RoleController::class, 'banAccount']);

    // User routes
    $app->get('/user/{id}/roles', [UserController::class, 'rolesRequest']);
    $app->delete('/user/{id}/lock', [UserController::class, 'removelockRequest']);
})->add(new Authorizer());

