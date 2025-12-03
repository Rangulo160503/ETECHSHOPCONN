<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = (int)($_POST['id_producto'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $usuario = $_POST['usuario'] ?? 'WEB';

    if ($idProducto && $cantidad > 0 && in_array($tipo, ['E', 'S'])) {
        $plsql = 'BEGIN sp_movimiento_inventario(:p_id, :p_tipo, :p_cant, :p_user); :p_msg := ''Movimiento registrado''; EXCEPTION WHEN OTHERS THEN :p_msg := SQLERRM; END;';
        $stmt = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':p_id', $idProducto);
        oci_bind_by_name($stmt, ':p_tipo', $tipo, 1);
        oci_bind_by_name($stmt, ':p_cant', $cantidad);
        oci_bind_by_name($stmt, ':p_user', $usuario, 100);
        oci_bind_by_name($stmt, ':p_msg', $mensaje, 4000);
        @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);
    } else {
        $mensaje = 'Datos de movimiento inválidos.';
    }
}

$productos = [];
$stmtProd = oci_parse($conn, "SELECT codigo, nombre, stock, stock_minimo FROM productos WHERE estado = 'A' ORDER BY nombre");
oci_execute($stmtProd);
while (($row = oci_fetch_assoc($stmtProd)) !== false) {
    $productos[] = $row;
}
oci_free_statement($stmtProd);

$alertas = [];
$stmtAlerta = oci_parse($conn, "SELECT a.id_alerta, p.nombre, a.stock_actual, a.stock_minimo, a.fecha_alerta
                              FROM alerta_inventario a
                              LEFT JOIN productos p ON p.codigo = a.id_producto
                              ORDER BY a.fecha_alerta DESC");
oci_execute($stmtAlerta);
while (($row = oci_fetch_assoc($stmtAlerta)) !== false) {
    $alertas[] = $row;
}
oci_free_statement($stmtAlerta);
Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Inventario</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock actual</th>
                    <th>Stock mínimo</th>
                    <th>Registrar movimiento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['NOMBRE']) ?></td>
                        <td><?= htmlspecialchars($p['STOCK']) ?></td>
                        <td><?= htmlspecialchars($p['STOCK_MINIMO']) ?></td>
                        <td>
                            <form method="POST" class="row g-2 align-items-center">
                                <input type="hidden" name="id_producto" value="<?= htmlspecialchars($p['CODIGO']) ?>">
                                <div class="col-auto">
                                    <select name="tipo" class="form-select form-select-sm">
                                        <option value="E">Entrada</option>
                                        <option value="S">Salida</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input type="number" name="cantidad" class="form-control form-control-sm" min="1" required>
                                </div>
                                <div class="col-auto">
                                    <input type="text" name="usuario" class="form-control form-control-sm" placeholder="Usuario" value="admin">
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-primary" type="submit">Registrar</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="4" class="text-center">No hay productos activos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>Alertas de stock</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock actual</th>
                    <th>Stock mínimo</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['NOMBRE']) ?></td>
                        <td><?= htmlspecialchars($a['STOCK_ACTUAL']) ?></td>
                        <td><?= htmlspecialchars($a['STOCK_MINIMO']) ?></td>
                        <td><?= htmlspecialchars($a['FECHA_ALERTA']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($alertas)): ?>
                    <tr><td colspan="4" class="text-center">No hay alertas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>