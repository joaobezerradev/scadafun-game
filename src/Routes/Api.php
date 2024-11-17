<?php

use App\Middleware\Authorizer;
use App\Middleware\RequestLogger;
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
    $app->get('/role/{roleid}', [RoleController::class, 'characterRequest']);
    $app->put('/role/{roleid}', [RoleController::class, 'characterResponse']);
    $app->post('/role/name', [RoleController::class, 'characternameRequest']);
    $app->get('/faction/{factionid}', [RoleController::class, 'factionRequest']);
    $app->get('/role/{roleid}/faction', [RoleController::class, 'userfactionRequest']);
    $app->post('/role/{roleid}/bank/reset', [RoleController::class, 'resetBankRequest']);
    $app->put('/role/{roleid}/name', [RoleController::class, 'renameRequest']);
    $app->put('/role/{roleid}/meridian', [RoleController::class, 'meridianFull']);
    $app->put('/role/{roleid}/title', [RoleController::class, 'titleFull']);
    $app->post('/role/{roleid}/ban', [RoleController::class, 'banRole']);
    $app->post('/role/{roleid}/mute', [RoleController::class, 'muteRole']);
    $app->post('/user/{userid}/ban', [RoleController::class, 'banAccount']);

    // User routes
    $app->get('/user/{userid}/roles', [UserController::class, 'rolesRequest']);
    $app->delete('/user/{userid}/lock', [UserController::class, 'removelockRequest']);
})->add(new Authorizer());

