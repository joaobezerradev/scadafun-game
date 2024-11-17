<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController extends Controller
{
    public function check(Request $request): Response
    {
        return $this->response([
            'status' => 'ok',
            'timestamp' => time()
        ]);
    }
} 