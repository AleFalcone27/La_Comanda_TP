<?php
    include_once "base_datos/AccesoDatos.php";
class Venta{

    public $id;
    public $producto;
    public $id_usuario;
    public $codigo;
    public $cantidad;
    public $demora;
    public $estado;

    public function altaVenta()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO venta (producto, id_usuario, codigo, cantidad, demora, estado) VALUES (:producto, :id_usuario, :codigo, :cantidad, :demora, :estado)");
        $consulta->bindValue(':producto', $this->producto, PDO::PARAM_STR);
        $consulta->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $consulta->bindValue(':demora', $this->demora, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT producto, id_usuario, codigo, cantidad, demora, estado FROM venta");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Venta');
    }

    public static function obtenerSegunRol($area_preparacion)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        
        $consultaVentas = $objAccesoDatos->prepararConsulta("SELECT id, producto, id_usuario, codigo, cantidad, demora, estado FROM venta WHERE  id_usuario IS NULL AND estado = 'espera' OR estado = 'en preparacion' ");
        $consultaVentas->execute();
        $ventas = $consultaVentas->fetchAll(PDO::FETCH_CLASS, 'Venta');
        $resultadosFiltrados = [];

        foreach ($ventas as $venta) {
            $consultaProducto = $objAccesoDatos->prepararConsulta("SELECT * FROM producto WHERE nombre = :producto AND area_preparacion = :area_preparacion");
            $consultaProducto->bindValue(':producto', $venta->producto, PDO::PARAM_INT);
            $consultaProducto->bindValue(':area_preparacion', $area_preparacion, PDO::PARAM_STR);
            $consultaProducto->execute();
            
            $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);
            
            if ($producto) {
                $resultadosFiltrados[] = $venta;
            }
        }
        
        return $resultadosFiltrados;
    }

    public static function preparar($id, $rol, $demora, $id_usuario) {

        if(Venta::noSePuedePreparar($id)){
            return json_encode(['Error' => 'Ya se encuentra en preparacion o fue entregado']);
        }

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT producto FROM venta WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $producto = $consulta->fetch(PDO::FETCH_ASSOC);
    
        if (!$producto) {
            return json_encode(['Error' => 'No se encontró el producto']);
        }
    
        $nombreProducto = $producto['producto'];
        $consultaArea = $objAccesoDatos->prepararConsulta("SELECT area_preparacion FROM producto WHERE nombre = :nombreProducto");
        $consultaArea->bindValue(':nombreProducto', $nombreProducto, PDO::PARAM_STR);
        $consultaArea->execute();
        $producto = $consultaArea->fetch(PDO::FETCH_ASSOC);
    
        if ($producto['area_preparacion'] != $rol) {
            return json_encode(['Error' => 'Usted no está autorizado para realizar esta tarea']);
        }
    
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE venta SET estado = 'en preparacion', demora = :demora, id_usuario = :id_usuario WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':demora', $demora, PDO::PARAM_INT);
        $consulta->execute();
    
        return json_encode(['Message' => 'El pedido está siendo preparadao']);
    }
    
    public static function noSePuedePreparar($id){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("SELECT estado FROM venta WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $venta = $consulta->fetch(PDO::FETCH_ASSOC);

        if($venta && $venta['estado'] != "espera"){
            return true;
        }
        return false;
    }

    public static function finalizar($id, $id_usuario) {

        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE venta SET estado = 'listo para servir' WHERE id_usuario = :id_usuario AND id = :id AND estado = 'en preparacion'");
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        
        if($consulta->rowCount() > 0){
            return json_encode(value: ['message' => 'Se ha actualizado el estado a listo para servir']);
        }
       return json_encode(value: ['Error' => 'No se ha actualizado el estado del pedido']);
    }

    public static function servir($codigo) {

        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE venta SET estado = 'entregado' WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();
        
        if($consulta->rowCount() > 0){
            return json_encode(['message' => 'Se ha actualizado el estado a entregado']);
        }
         return json_encode(['Error' => 'No se pudo actualizar el estado']);
    }

    public static function ObtenerPorCodigo($codigo){

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM venta WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();

        $ventas = $consulta->fetchAll(PDO::FETCH_CLASS, 'Venta');

        return $ventas;
    }

    public static function ObtenerCantidadesVendidas(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT producto, SUM(cantidad) AS total_vendido FROM venta GROUP BY producto");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
    