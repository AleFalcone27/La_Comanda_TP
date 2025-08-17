<?php

require_once 'modelos/Mesa.php';
require_once 'interfaces/IApiUsable.php';

class MesaControles extends Mesa implements IApiUsable{
    
    public function CargarUno($request, $response, $args)
    {   
        $parametros = $request->getParsedBody();

        $numero = $parametros['numero'];

        if(!Mesa::existeNumeroMesa($numero)){
            $mesa = new Mesa();
            $mesa->estado = "abierta";
            $mesa->numero = (int)$numero;
            $mesa->altaMesa();
    
            $payload = json_encode(array("mensaje" => "Mesa creada con exito"));
        }
        else{
            $payload = json_encode(array("Error" => "Mesa ya existente"));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesas" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args){
    
        $estados = array("con cliente esperando pedido", "con cliente comiendo", "con cliente pagando", "cerrada", "abierta");

        $rol = $request->getAttribute('rol');

        $parametros = json_decode($request->getBody()->getContents(), true);
        $numero = $parametros['numero'];
        $estado = $parametros['estado'];
        $id_mesa = $parametros['id_mesa'];

        $estadoActual = Mesa::estadoMesa($id_mesa);
        
        $sePuedeCerrar = $estadoActual == 'con cliente pagando' || $estadoActual == 'abierta';

        if($rol != "socio" && $estado == "cerrada" &&  $sePuedeCerrar){
            $payload = json_encode(array("Error" => "Solo el socio puede cerrar una mesa y no debe estar en uso."));
        }
        else{
            if(Mesa::existeMesa($id_mesa) &&  in_array($estado, $estados)){
                if(Mesa::modificarMesa($id_mesa ,$estado)){
                    $payload = json_encode(array("Exito" => "Mesa modificado correctamente"));
                }
                else{
                    $payload = json_encode(array("Error" => "La mesa no se ha modificado"));
                }
            }else{
                $payload = json_encode(array("Error" => "Datos ingresados incorrectos(numero, estado)"));
            }
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args){

        $id_mesa = $args['id_mesa'];
        if(Mesa::borrarMesa($id_mesa)){
            $payload = json_encode(array("EXITO:" => "Mesa borrado con exito"));
        }
        else{
            $payload = json_encode(array("ERROR" => "La mesa no se pudo eliminar"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function MasUsada($request, $response, $args){

        $mesa = Mesa::ordenadasPorUso()[0]['id_mesa'];
        $payload = json_encode(array("Mesa mas usada ID:" => $mesa));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function MenosFacturo($request, $response, $args){

        $mesa = Mesa::ordenadasPorFacturacion();
        $payload = json_encode(array("Mesas Facturacion Dsc" => $mesa));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function FacturoFechas($request, $response, $args){

        $parametros = $request->getQueryParams();

        $id_mesa = $parametros['id_mesa'];
        $fecha1 = $parametros['fecha1'];
        $fecha2 = $parametros['fecha2'];

        if ($fecha1 && strtotime($fecha1) && $fecha2 && strtotime($fecha2)) {
            $fecha1 = date('Y-m-d', strtotime($fecha1));
            $fecha2 = date('Y-m-d', strtotime($fecha2));
            
            $facturacion = Mesa::facturacionEntreFechas($fecha1, $fecha2, $id_mesa);

            if($facturacion == NULL){
                $payload = json_encode(array("Facturacion" => "No hay facturaciones registradas en entre esas fechas"));
            }else{

                $payload = json_encode(array("Facturacion" => $facturacion));
            }

        } else {

            return $response->getBody()->write("Fechas inválidas");
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}