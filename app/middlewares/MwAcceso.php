<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Slim\Psr7\Factory\ResponseFactory;

class AccesoMiddleware{

    private $rolAcceso;

    public function __construct($rolAcceso) {
        $this->rolAcceso = $rolAcceso;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
     
        $responseFactory = new ResponseFactory();

        $header = $request->getHeaderLine('Authorization');

        if($header){
            $token = trim(explode("Bearer", $header)[1]);
        }
        
        if (!$token) {
            $response = $responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['Error' => 'Token no proporcionado']));
            return $response->withStatus(401);
        }
        try {     
            $data = AutentificadorJWT::ObtenerData($token);
            
            if (in_array($data->rol, $this->rolAcceso)) {
                return $handler->handle($request);
            } else {
                $response = $responseFactory->createResponse();
                $response = $response->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode(['Error' => 'Acceso denegado']));
                return $response->withStatus(403);
            }
        } catch (Exception $e) {
            $response = $responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['Error' => 'Token inválido', 'message' => $e->getMessage()]));
            return $response->withStatus(401);
        }
    }
}