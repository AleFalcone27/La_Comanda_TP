<?php

include_once 'base_datos/AccesoDatos.php';

function descargarCSV($request, $response, $args) {
    try {
        $consulta = Producto::descargarTodos();
        
        $nombreDescarga = 'productosCSV.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $nombreDescarga);

        $archivo = fopen('php://output', 'w');

        fputcsv($archivo, array('id', 'nombre', 'precio', 'area_preparacion'));

        foreach($consulta as $producto){
            fputcsv($archivo, $producto);
        }           

        fclose($archivo);

        exit;

    } catch (PDOException $e) {
        $response->getBody()->write("Error en la base de datos: " . $e->getMessage());
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write("Error: " . $e->getMessage());
        return $response->withHeader('Content-Type', 'application/json');
    }
}