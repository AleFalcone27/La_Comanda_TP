<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Slim\Psr7\Factory\ResponseFactory;

enum Roles: string
{
    case SOCIO = "socio";
    case BARTENDER = "bartender";
    case MOZO = "mozo";
    case COCINERO = "cocinero";
    case CERVERCERO = "cervecero";
}

class RolMiddleware
{

    private $rolAcceso;

    public function __construct($rolAcceso)
    {
        $this->rolAcceso = $rolAcceso;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {

        $responseFactory = new ResponseFactory();

        if (in_array($request->getAttribute("rol"), $this->rolAcceso)) {
            return $handler->handle($request);
        } else {
            $response = $responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['Error' => 'Acceso denegado']));
            return $response->withStatus(403);
        }
    }
}
