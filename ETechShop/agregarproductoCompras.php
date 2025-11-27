<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/functions.php";

if (isset($_GET['id'])) {

    // ID DEL PRODUCTO SIN COMILLAS NI NADA RARO
    $product_id = (int) trim($_GET['id'], "'");

    // 1) Conectar
    $conexion = Conecta();

    // 2) Obtener datos del producto desde Oracle
    $sql = "SELECT codigo, nombre, detalle, imagen, precio, stock
            FROM productos
            WHERE codigo = :codigo";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ":codigo", $product_id);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        Desconecta($conexion);
        die("Error en consulta de producto: " . htmlentities($e['message'], ENT_QUOTES));
    }

    $producto = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if ($producto) {

        // ================================
        // A) CARRITO EN SESIÓN (para mostrarlo en la web)
        // ================================
        if (!isset($_SESSION['carritoCompras'])) {
            $_SESSION['carritoCompras'] = [];
        }

        if (!isset($_SESSION['carritoCompras'][$product_id])) {
            $producto["cantidad"] = 1;
            $_SESSION['carritoCompras'][$product_id] = $producto;
        } else {
            $_SESSION['carritoCompras'][$product_id]["cantidad"]++;
        }

        // ================================
        // B) CARRITO EN BASE DE DATOS
        // ================================
        if (isset($_SESSION['id_usuario'])) {
            $idUsuario = (int) $_SESSION['id_usuario'];
            $cantidad  = 1;

            $sql2 = "BEGIN sp_carrito_agregar(:p_user, :p_codigo, :p_cant); END;";
            $stmt2 = oci_parse($conexion, $sql2);

            oci_bind_by_name($stmt2, ":p_user", $idUsuario);
            oci_bind_by_name($stmt2, ":p_codigo", $product_id);
            oci_bind_by_name($stmt2, ":p_cant", $cantidad);

            if (!oci_execute($stmt2, OCI_COMMIT_ON_SUCCESS)) {
                $e = oci_error($stmt2);
                Desconecta($conexion);
                die("Error al agregar en BD: " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_free_statement($stmt2);
        }
    }

    Desconecta($conexion);
}

// 3) Regresar al catálogo
header("Location: Compras.php");
exit;
?>
