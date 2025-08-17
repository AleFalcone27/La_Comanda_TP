<?php
    include_once "base_datos/AccesoDatos.php";
class Producto{

    public $id;
    public $nombre;
    public $precio;
    public $area_preparacion;  //(#Bartender, #Cervecero, #Cocinero)

    public function altaProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO producto (nombre, precio, area_preparacion) VALUES (:nombre, :precio, :area_preparacion)");
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
        $consulta->bindValue(':area_preparacion', $this->area_preparacion, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarProducto($id_producto){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM producto WHERE id = :id_producto");
        $consulta->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT nombre, precio FROM producto");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerProducotID($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, precio, area_preparacion FROM producto WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Producto');
    }

    public static function obtenerProductoNombre($nombre)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, precio, area_preparacion FROM producto WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();

        $producto = $consulta->fetchObject('Producto');

        return $producto;
    }

    public static function existeYActualizar($params)
    {
        
        if($producto = Producto::existe($params['nombre'])){
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("UPDATE producto SET precio = :precio, area_preparacion = :area_preparacion WHERE id = :id");
            $consulta->bindValue(':precio', $params['precio'], PDO::PARAM_INT);
            $consulta->bindValue(':area_preparacion', $params['area_preparacion'], PDO::PARAM_STR);
            $consulta->bindValue(':id', $producto['id'], PDO::PARAM_INT);
            $consulta->execute();

            return true;
        }
        return false;
    }

    public static function existe($nombre)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM producto WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();
        $producto = $consulta->fetch(PDO::FETCH_ASSOC);

        return $producto;
    }

    public static function descargarTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM producto");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

}    
