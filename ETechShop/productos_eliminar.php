<?php
require_once 'DAL/conexion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensaje = '';

if ($id > 0) {
    $conn = getConnection();
    if ($conn) {
        $plsql = 'BEGIN sp_eliminar_producto(:p_id, :p_mensaje); END;';
        $stmt = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':p_id', $id);
        oci_bind_by_name($stmt, ':p_mensaje', $mensaje, 4000);
        $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        if (!$ok) {
            $e = oci_error($stmt);
            $mensaje = $e ? htmlentities($e['message'], ENT_QUOTES) : 'No se pudo eliminar el producto.';
        }
        oci_free_statement($stmt);
        Desconecta($conn);
    } else {
        $mensaje = 'No se pudo conectar a la base de datos.';
    }
} else {
    $mensaje = 'Identificador inválido.';
}

header('Location: productos_listar.php?mensaje=' . urlencode($mensaje));
exit;