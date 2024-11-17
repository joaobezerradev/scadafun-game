<?php

namespace App\Middleware;

use App\Environment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class Authorizer implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response {
        $apiKey = $request->getHeaderLine('x-api-key');
        
        if ($apiKey === $_ENV['API_KEY']) {
            return $handler->handle($request);
        }

        $response = (new ResponseFactory())->createResponse(401);
        $response->getBody()->write('Unauthorized access');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
