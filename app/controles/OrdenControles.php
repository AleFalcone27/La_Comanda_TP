<?php

require_once 'modelos/Orden.php';
require_once 'modelos/Venta.php';
require_once 'interfaces/IApiUsable.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

class OrdenControles extends Orden{

    public function CargarUno($request, $response, $args)
    {
        $json = $request->getBody()->getContents();
        $parametros = json_decode($json, true);

        $codigo = Orden::generarCodigo();
        $id_mesa = $parametros['id_mesa'];
        $productos = $parametros['productos'];
        $cliente_nombre = $parametros['cliente_nombre'];        

        if (json_last_error() === JSON_ERROR_NONE && is_array($productos) && Mesa::existeMesa($id_mesa) && Mesa::disponible($id_mesa)) {
            
            foreach ($productos as $producto) {
                if(!Producto::existe($producto['producto'])){
                    $payload = json_encode(array("Error" => "Pedidos incorrectos"));
                    $response->getBody()->write($payload);
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            foreach ($productos as $producto) {
                $venta = new Venta();
                $venta->producto = $producto["producto"];
                $venta->codigo = $codigo;
                $venta->cantidad = (int)$producto["cantidad"];
                $venta->estado = "espera";
                $venta->id_usuario = null;
                $venta->altaVenta();
            }
        } else {
            $payload = json_encode(array("Error" => "Formato de productos inválido o la mesa no está disponible"));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        $orden = new Orden();
        $orden->codigo = $codigo;
        $orden->id_mesa = (int)$id_mesa;
        $orden->estado = "espera";
        $orden->hora_pedido = date('H:i:s');
        $orden->fecha = date('Y-m-d');
        $orden->cliente_nombre = $cliente_nombre;
        $orden->altaOrden();

        Mesa::modificarMesa($id_mesa,"con cliente esperando pedido");

        $payload = json_encode(array("Exito" => "Orden creado con exito",
                                    "Codigo De Orden" => $codigo));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Orden::obtenerTodos();
        $payload = json_encode(array("listaOrdenes" => $lista));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function AgregarImagen($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $codigo = $parametros['codigo'];

        if(Orden::existeOrden($codigo)){
            try{
                guardarImagen('./ImagenesDeOrdenes/', $codigo);
                $payload = json_encode(array("message" => "se vinculo correctamente la imagen"));
            }
            catch(Exception $ex){
                $payload = json_encode(array("error" => $ex->getMessage()));
            }
        }
        else{
            $payload = json_encode(array("error" => "la orden no existe"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function DemoraOrden($request, $response, $args)
    {
        $parametros = $request->getQueryParams();
        $codigo = $parametros['codigo'];

        if(Orden::existeOrden($codigo)){
                $demora = Orden::demorayEstado($codigo);
                if($demora[0] == 0){
                    $payload = json_encode(array("Demora" => $demora[1]));
                }
                else{
                    $payload = json_encode(array("Demora" => $demora[0]));
                }
        }
        else{
            $payload = json_encode(array("Error" => "la orden no existe"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListaOrdenes($request, $response, $args){
        Orden::actualizarDemorasOrdenes();
        $ordenes = Orden::obtenerTodos();

        $ordenesFiltradas = array_map(function($orden) {
            return [
                "codigo" => $orden->codigo,
                "demora_orden" => $orden->demora_orden,
                "estado" => $orden->estado
            ];
        }, $ordenes);

        $payload = json_encode(array("Pedidos" => $ordenesFiltradas));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ServirOrden($request, $response, $args){

        $parametros = json_decode($request->getBody()->getContents(), true);
        $codigo = $parametros['codigo'];
        Orden::actualizarDemorasOrdenes();

        if(Orden::demorayEstado($codigo)[1] == "listo para servir"){

            $mensaje = Venta::servir($codigo);
            Orden::servir($codigo);
            if(Mesa::modificarMesa(Orden::obtenerMesa($codigo), "con cliente comiendo")){
                $payload = json_encode(array("Orden" => $mensaje));
            }
            else{
                $payload = json_encode(array("Error" => "No se puede actualizar la mesa"));
            }
        }
        else{
            $payload = json_encode(array("Error" => "No esta lista para servir o el codigo no existe"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');

    }

    public static function CobrarOrden($request, $response, $args){

        $parametros = json_decode($request->getBody()->getContents(), true);
        $codigo = $parametros['codigo'];
        $mesa_id = Orden::obtenerMesa($codigo);
        $mensaje = null;

        if(Mesa::estadoMesa($mesa_id) == "con cliente comiendo" && Orden::existeOrden($codigo)){
            $cuenta = Orden::calcularCuenta($codigo);
            if(Orden::cobrar($codigo, $cuenta));
            Mesa::modificarMesa($mesa_id, "con cliente pagando");
            $mensaje = 'Cuenta cobrada, total: $' . $cuenta;
        }
        else{
            $mensaje = 'No se puede cobrar la orden';
        }

        $payload = json_encode(array("Orden" => $mensaje));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function OrdenesEntregadasTarde($request, $response, $args){
        $ordenes = Orden::ordenesTarde();

        $payload = json_encode(array("Ordenes entregadas tarde" => $ordenes));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function OrdenesEntregadasATiempo($request, $response, $args){
        $ordenes = Orden::ordenesATiempo();

        $payload = json_encode(array("Ordenes entregadas a tiempo" => $ordenes));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}