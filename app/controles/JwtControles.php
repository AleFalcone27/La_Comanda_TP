<?php

require_once './jwt/AutentificadorJWT.php';
require_once './modelos/Usuario.php';

class JwtControles{
    public function TokenLogin($request, $response, $args){
        $parametros = $request->getParsedBody();

        $usuario = $parametros['usuario'];
        $contraseña = $parametros['contraseña'];

        if($usuario = Usuario::validacionUsuario($usuario, $contraseña)){
            $datos = array('id' => $usuario['id'],
                            'rol' => $usuario['tipo']
                        );

            $token = AutentificadorJWT::CrearToken($datos);
            Usuario::loguear($usuario['id']);
            $payload = json_encode(array('token' => $token));
        } else {
            $payload = json_encode(array('error' => 'Usuario o contraseña incorrectos'));
        }

        $response->getBody()->write($payload);
        return $response
        ->withHeader('Content-Type', 'application/json');
    }
}
