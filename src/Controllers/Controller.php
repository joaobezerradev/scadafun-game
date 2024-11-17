<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ResponseFactory;

class Controller
{
    protected function response($data = '', $status = 200): Response 
    {
        $response = (new ResponseFactory())->createResponse($status);
        
        if (!empty($data)) {
            $response->getBody()->write(
                is_string($data) ? $data : json_encode($data)
            );
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
