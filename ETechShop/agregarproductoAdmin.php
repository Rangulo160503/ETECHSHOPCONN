<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/functions.php";
require_once "DAL/recoge.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre  = recogePost("nombre");
    $detalle = recogePost("detalle");
    $precio  = recogePost("precio");
    $imagen  = recogePost("imagen");

    if (!empty($nombre) && !empty($detalle) && is_numeric($precio) && !empty($imagen)) {

        $conexion = Conecta();
        if (!$conexion) {
            $_SESSION['mensaje'] = "danger|No se pudo conectar a Oracle.";
            header("Location: Compras.php");
            exit();
        }

        $sql  = "BEGIN sp_insert_producto(:nombre, :detalle, :precio, :imagen); END;";
        $stmt = oci_parse($conexion, $sql);

        // Si detalle es CLOB, usar descriptor temporal
        $clob = oci_new_descriptor($conexion, OCI_D_LOB);
        $clob->writeTemporary($detalle, OCI_TEMP_CLOB);

        oci_bind_by_name($stmt, ":nombre",  $nombre);
        oci_bind_by_name($stmt, ":detalle", $clob, -1, SQLT_CLOB);
        oci_bind_by_name($stmt, ":precio",  $precio);
        oci_bind_by_name($stmt, ":imagen",  $imagen);

        $resultado = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if ($resultado) {
            $_SESSION['mensaje'] = "success|Producto guardado con éxito.";
        } else {
            $e = oci_error($stmt);
            $_SESSION['mensaje'] = "danger|Error al guardar el producto: " . $e['message'];
        }

        // liberar recursos
        $clob->free();
        oci_free_statement($stmt);
        Desconecta($conexion);

    } else {
        $_SESSION['mensaje'] = "warning|Debes completar todos los campos correctamente.";
    }

    header("Location: Compras.php");
    exit();
}
?>
