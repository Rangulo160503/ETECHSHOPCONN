<?php
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
exit;

require_once "DAL/conexion.php";
require_once "DAL/functions.php";

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $conexion = Conecta();

    // 1) Traer datos del producto
    $consulta = "SELECT codigo, nombre, detalle, imagen, precio, stock 
                 FROM productos 
                 WHERE codigo = :codigo";
    $stmt = oci_parse($conexion, $consulta);
    oci_bind_by_name($stmt, ":codigo", $product_id);

    if (oci_execute($stmt)) {
        $producto = oci_fetch_assoc($stmt);

        if ($producto) {
            // -----------------------------
            // A) Carrito en SESIÓN (como antes)
            // -----------------------------
            if (!isset($_SESSION['carritoCompras'])) {
                $_SESSION['carritoCompras'] = [];
            }

            if (!isset($_SESSION['carritoCompras'][$product_id])) {
                // cantidad por defecto = 1 (puedes cambiarlo si luego permites elegir cantidad)
                $producto['CANTIDAD'] = 1;
                $_SESSION['carritoCompras'][$product_id] = $producto;

                // -----------------------------
                // B) Carrito en BD usando SP_SP_CARRITO_AGREGAR
                // -----------------------------
                if (isset($_SESSION['id_usuario'])) {
                    $idUsuario = $_SESSION['id_usuario'];
                    $cantidad  = 1; // misma que pusimos en sesión

                    $sqlCarrito = "BEGIN sp_carrito_agregar(:p_id_usuario, :p_codigo, :p_cantidad); END;";
                    $stmtCarrito = oci_parse($conexion, $sqlCarrito);

                    oci_bind_by_name($stmtCarrito, ":p_id_usuario", $idUsuario);
                    oci_bind_by_name($stmtCarrito, ":p_codigo", $product_id);
                    oci_bind_by_name($stmtCarrito, ":p_cantidad", $cantidad);

                    if (!oci_execute($stmtCarrito, OCI_COMMIT_ON_SUCCESS)) {
                        $e = oci_error($stmtCarrito);
                        // Opcional: podrías guardar este error en log en lugar de mostrarlo
                        // echo "Error al agregar al carrito en BD: " . htmlentities($e['message'], ENT_QUOTES);
                    }

                    oci_free_statement($stmtCarrito);
                }

                // Mensaje opcional (ojo con headers)
                // echo "Producto agregado al carrito";
            } else {
                // Si ya estaba en la sesión, podrías incrementar cantidad en sesión
                // y también llamar al SP con +1 si quieres que todo quede alineado
                // echo "El producto ya está en el carrito";
            }
        } else {
            // echo "Producto no encontrado";
        }

        oci_free_statement($stmt);
    } else {
        $e = oci_error($stmt);
        // echo "Error en la consulta: " . $e['message'];
    }

    Desconecta($conexion);
}

header("Location: Compras.php");
exit;
?>
