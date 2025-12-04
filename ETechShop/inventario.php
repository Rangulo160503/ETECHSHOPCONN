<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = '';
$errores = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = (int)($_POST['id_producto'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $usuario = $_POST['usuario'] ?? 'WEB';

    if ($idProducto && $cantidad > 0 && in_array($tipo, ['E', 'S'], true)) {

        // Llamamos al SP (cuando exista en la BD)
        $plsql = "BEGIN sp_movimiento_inventario(:p_id, :p_tipo, :p_cant, :p_user, :p_msg); END;";
        $stmt  = oci_parse($conn, $plsql);

        oci_bind_by_name($stmt, ':p_id',   $idProducto);
        oci_bind_by_name($stmt, ':p_tipo', $tipo, 1);
        oci_bind_by_name($stmt, ':p_cant', $cantidad);
        oci_bind_by_name($stmt, ':p_user', $usuario, 100);
        oci_bind_by_name($stmt, ':p_msg',  $mensaje, 4000);

        $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if ($ok && $mensaje === '') {
            $mensaje = 'Movimiento registrado.';
        } elseif (!$ok) {
            $e = oci_error($stmt);
            $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'Error aplicando movimiento.';
        }

        oci_free_statement($stmt);

    } else {
        $mensaje = 'Datos de movimiento inválidos.';
    }
}

// Listado de productos para mostrar en la tabla
$productos = [];
$sqlList = "SELECT codigo, nombre, stock FROM productos ORDER BY codigo";
$stList = oci_parse($conn, $sqlList);
if (oci_execute($stList)) {
    while (($row = oci_fetch_assoc($stList)) !== false) {
        $productos[] = $row;
    }
}
oci_free_statement($stList);

Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - ETechShop</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Inventario</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>
    <?php if ($errores): ?>
        <div class="alert alert-danger"><?= $errores ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-2 mb-4">
        <div class="col-md-3">
            <label class="form-label">Producto (ID)</label>
            <input type="number" name="id_producto" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select" required>
                <option value="">Seleccione...</option>
                <option value="E">Entrada (+)</option>
                <option value="S">Salida (-)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Cantidad</label>
            <input type="number" name="cantidad" min="1" class="form-control" required>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit">Registrar movimiento</button>
        </div>
    </form>

    <h2 class="h5">Stock actual</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Stock</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['CODIGO']) ?></td>
                    <td><?= htmlspecialchars($p['NOMBRE']) ?></td>
                    <td><?= htmlspecialchars($p['STOCK']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <tr><td colspan="3" class="text-center">No hay productos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
