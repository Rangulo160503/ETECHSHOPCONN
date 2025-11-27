<?php

function Conecta() {
    $user = "etechShopConn";
    $password = "Pass123$";
    $connectionString = "localhost:1521/XEPDB1";  // <-- ESTA ES LA CORRECTA

    $conexion = oci_connect($user, $password, $connectionString, "AL32UTF8");

    if (!$conexion) {
        $e = oci_error();
        echo "Ocurrió un error al establecer la conexión: " . htmlentities($e['message'], ENT_QUOTES);
        return null;
    }

    return $conexion;
}

function Desconecta($conexion) {
    if ($conexion) {
        oci_close($conexion);
    }
}
?>