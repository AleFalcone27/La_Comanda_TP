<?php

require_once 'modelos/Venta.php';
require_once 'interfaces/IApiUsable.php';

class VentaControles extends Venta{
    
    public function TraerTodos($request, $response, $args)
    {
        $lista = Venta::obtenerTodos();
        $payload = json_encode(array("listaVentas" => $lista));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerVentasRol($request, $response, $args)
    {
        $rol = $request->getAttribute('rol');

        $lista = Venta::obtenerSegunRol($rol);
        $payload = json_encode(array("listaVentas" => $lista));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}