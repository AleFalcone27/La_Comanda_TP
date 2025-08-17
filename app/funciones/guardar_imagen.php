<?php

function guardarImagen($ruta, $nombre)
{
    if (!isset($_FILES["imagen"]) || $_FILES["imagen"]["error"] != UPLOAD_ERR_OK) {
        throw new Exception("Error al subir la imagen");
    }

    if ($_FILES["imagen"]['size'] > 100000) {
        throw new Exception("La imagen es muy grande");
    }

    $tipoImagen = mime_content_type($_FILES["imagen"]["tmp_name"]);
    $tipos = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($tipoImagen, $tipos)) {
        throw new Exception("El archivo debe ser una imagen (jpeg, jpg, png).");
    }

    $extension = pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION);
    $ruta_foto = $ruta . $nombre . '.' . $extension;

    $directorio = dirname($ruta_foto);
    if (!is_dir($directorio) || !is_writable($directorio)) {
        throw new Exception('El directorio para guardar los archivos no existe o no es escribible!');
    }

    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $ruta_foto)) {
        return true;
    } else {
        throw new Exception("Error al mover el archivo subido.");
    }
}