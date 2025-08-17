<?php

use Slim\Psr7\Request;

require_once 'modelos/Usuario.php';
require_once 'interfaces/IApiUsable.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

class UsuarioControles extends Usuario implements IApiUsable{
    
    public function CargarUno($request, $response, $args)
    {
        $roles = array("mozo", "cervecero", "cocinero", "bartender","socio");
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $clave = $parametros['clave'];
        $tipo = $parametros['tipo'];

        if(!Usuario::existeUsuario($nombre) &&  in_array($tipo, $roles)){

        $usuario = new Usuario();
        $usuario->nombre = $nombre;
        $usuario->clave = $clave;
        $usuario->fecha_registro = date('Y-m-d H:i:s');
        $usuario->tipo = $tipo;
        $usuario->estado = "activo";

        $usuario->altaUsuario();

        $payload = json_encode(array("Exito" => "Usuario creado con exito"));
        }
        else{
            $payload = json_encode(array("Error" => "Usuario ya existente o rol incorrecto"));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args){

        $id_usuario = $args['id_usuario'];
        if(Usuario::borrarUsuario($id_usuario)){
            $payload = json_encode(array("EXITO:" => "Usuario borrado con exito"));
        }
        else{
            $payload = json_encode(array("ERROR" => "El usuario no se pudo eliminar"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Usuario::obtenerTodos();
        $payload = json_encode(array("listaUsuario" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args){

        $roles = array("mozo", "cervecero", "cocinero", "bartender","socio");
        $estados = array("activo", "suspendido");

        $parametros = json_decode($request->getBody()->getContents(), true);

        $nombre = $parametros['nombre'];
        $estado = $parametros['estado'];
        $tipo = $parametros['tipo'];

        if(Usuario::existeUsuario($nombre) &&  in_array($tipo, $roles) && in_array($estado, $estados)){
            if(Usuario::modificarUsuario($nombre, $estado, $tipo)){
                $payload = json_encode(array("Exito" => "Usuario modificado correctamente"));
            }
            else{
                $payload = json_encode(array("Error" => "El usuario no se ha modificado"));
            }
        }else{
            $payload = json_encode(array("Error" => "Datos ingresados incorrectos(nombre, estado, tipo)" ));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ComezarAPreparar($request, $response, $args){
        $rol = $request->getAttribute('rol');
        $id_usuario = $request->getAttribute('id');
        $parametros = $parametros = json_decode($request->getBody()->getContents(), true);
        $demora = $parametros['demora'];
        $id = $parametros['id'];

        $payload = Venta::preparar($id, $rol, $demora, $id_usuario);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function FinalizarPreparacion($request, $response, $args){
        $id_usuario = $request->getAttribute('id');
        $parametros = $parametros = json_decode($request->getBody()->getContents(), true);
        $id = $parametros['id'];

        $payload = Venta::finalizar($id, $id_usuario);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerFechas($request, $response, $args)
    {
        $parametros = $request->getQueryParams();

        $nombre = $parametros['nombre'];
        $lista = Usuario::fechasRegistro($nombre);
        $payload = json_encode(array("Fechas Registros" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function obtenerOperaciones($request, $response, $args){

        $operaciones = Usuario::actividadUsuarios();
        $payload = json_encode(array("Operaciones por usuarios:" => $operaciones));
    
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function obtenerOperacionesPorSector($request, $response, $args){

        $operaciones = Usuario::actividadUsuarios();
        $operacionesPorSector = [];

        foreach ($operaciones as $operacion) {
            $sector = $operacion['rol'];
            $cantidadOperaciones = $operacion['operaciones'];
    
            if (isset($operacionesPorSector[$sector])) {
                $operacionesPorSector[$sector] += $cantidadOperaciones;
            } else {
                $operacionesPorSector[$sector] = $cantidadOperaciones;
            }
        }

        $payload = json_encode(array("Operaciones por sector:" => $operacionesPorSector));
    
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}