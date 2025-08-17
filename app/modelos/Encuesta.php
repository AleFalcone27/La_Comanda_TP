<?php
    include_once "base_datos/AccesoDatos.php";
    
class Encuesta{

    public $codigo;
    public $id_mesa;
    public $mesa;
    public $restaurante;
    public $mozo;
    public $cocinero;
    public $comentario;

    public function altaEncuesta()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO encuesta (codigo, id_mesa, mesa, restaurante, mozo, cocinero, comentario) VALUES (:codigo, :id_mesa, :mesa, :restaurante, :mozo, :cocinero, :comentario)");
        $consulta->bindValue(':codigo', $this->codigo,  PDO::PARAM_STR);
        $consulta->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':mesa', $this->mesa, PDO::PARAM_INT);
        $consulta->bindValue(':restaurante', $this->restaurante, PDO::PARAM_INT);
        $consulta->bindValue(':mozo', $this->mozo, PDO::PARAM_INT);
        $consulta->bindValue(':cocinero', $this->cocinero, PDO::PARAM_INT);
        $consulta->bindValue(':comentario', $this->comentario, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function yaExiste($codigo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo FROM encuesta WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo,  PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function obtenerEncuestasOrdenadas(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo, comentario, (mesa + restaurante + mozo + cocinero) / 4 AS promedio FROM encuesta ORDER BY promedio DESC");
        $consulta->execute();
    
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}