<?php
    include_once "base_datos/AccesoDatos.php";
class Mesa{

    public $id;
    public $estado; //“con cliente esperando pedido” ,”con cliente comiendo”, “con cliente pagando” y “cerrada”.
    public $numero;

    public function altaMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesa (estado, numero) VALUES (:estado, :numero)");
        $consulta->bindValue(':estado', $this->estado,  PDO::PARAM_STR);
        $consulta->bindValue(':numero', $this->numero, PDO::PARAM_INT);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarMesa($id_mesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM mesa WHERE id = :id_mesa");
        $consulta->bindValue(':id_mesa', $id_mesa, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function disponible($id_mesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id FROM mesa WHERE id = :id AND estado = 'abierta'");
        $consulta->bindValue(':id', $id_mesa, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerTodos(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, estado, numero FROM mesa");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function existeMesa($id_mesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT numero FROM mesa WHERE id = :id");
        $consulta->bindValue(':id', $id_mesa, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function modificarMesa($id_mesa, $estado){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesa SET estado = :estado WHERE id = :id_mesa");
        $consulta->bindValue(':id_mesa', $id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function estadoMesa($id_mesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT estado FROM mesa WHERE id = :id");
        $consulta->bindValue(':id', $id_mesa, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_COLUMN);
    }

    public static function existeNumeroMesa($numero){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT numero FROM mesa WHERE numero = :numero");
        $consulta->bindValue(':numero', $numero, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function ordenadasPorUso(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id_mesa, COUNT(*) AS usos FROM orden GROUP BY id_mesa ORDER BY usos DESC");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ordenadasPorFacturacion(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id_mesa, COALESCE(MIN(cuenta), 0) AS facturacionBarata FROM orden GROUP BY id_mesa ORDER BY facturacionBarata ASC");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function facturacionEntreFechas($fecha1, $fecha2, $id_mesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id_mesa, COALESCE(SUM(cuenta), 0) AS total_facturacion FROM orden WHERE id_mesa = :id_mesa AND fecha BETWEEN :fecha_inicio AND :fecha_fin GROUP BY id_mesa");
        $consulta->bindValue(':id_mesa', $id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_inicio', $fecha1);
        $consulta->bindValue(':fecha_fin', $fecha2);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
}    