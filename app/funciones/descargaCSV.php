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
function cargarCSV($request, $response, $args) {
    try {
        
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("No se recibió un archivo CSV válido.");
        }

        $rutaArchivo = $_FILES['archivo']['tmp_name'];

        if (($archivo = fopen($rutaArchivo, 'r')) === false) {
            throw new Exception("No se pudo abrir el archivo CSV.");
        }

        $encabezado = fgetcsv($archivo, 1000, ',');

        while (($datos = fgetcsv($archivo, 1000, ',')) !== false) {
            $producto = new Producto();
            $producto->nombre = $datos[0];
            $producto->precio = $datos[1];
            $producto->area_preparacion = $datos[2];
            $producto->altaProducto();
        }

        fclose($archivo);

        $response->getBody()->write(json_encode(["mensaje" => "Productos cargados correctamente"]));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
