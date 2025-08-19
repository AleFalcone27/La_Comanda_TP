<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface as MiddlewareInterface;
use Slim\Psr7\Factory\ResponseFactory;


enum Roles: string {
    case SOCIO = "socio";
    case BARTENDER = "bartender";
    case MOZO = "mozo";
    case COCINERO = "cocinero";
    case CERVERCERO = "cervecero";
}

class RolMiddleware{
    public function __invoke(Request $request, RequestHandler $handler): Response {
        $responseFactory = new ResponseFactory();

        $header = $request->getHeaderLine('Authorization');
        if (strpos($header, 'Bearer ') === false) {
            $response = $responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['error' => 'Token no proporcionado']));
            return $response->withStatus(401);
        }

        $token = trim(explode("Bearer", $header)[1]);
        
        if (!$token) {
            $response = $responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['error' => 'Token no proporcionado']));
            return $response->withStatus(401);
        }

        try {
            $data = AutentificadorJWT::ObtenerData($token);
            $request = $request->withAttribute('rol', $data->rol)
                            ->withAttribute('id', $data->id);

            return $handler->handle($request);
        } catch (Exception $e) {
            $response = $responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['error' => 'Token inválido', 'message' => $e->getMessage()]));
            return $response->withStatus(401);
        }
    }
}