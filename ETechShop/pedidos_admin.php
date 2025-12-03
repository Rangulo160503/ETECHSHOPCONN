<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = '';
$errores = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPedido = (int)($_POST['id_pedido'] ?? 0);
    $estadoNuevo = trim($_POST['estado'] ?? '');
    $usuario = $_POST['usuario_cambio'] ?? 'WEB';

    if ($idPedido && $estadoNuevo !== '') {
        $plsql = 'BEGIN sp_actualizar_estado_pedido(:p_id, :p_estado, :p_usuario); END;';
        $stmt = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':p_id', $idPedido);
        oci_bind_by_name($stmt, ':p_estado', $estadoNuevo, 30);
        oci_bind_by_name($stmt, ':p_usuario', $usuario, 100);
        $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        if ($ok) {
            $mensaje = 'Estado actualizado correctamente.';
        } else {
            $e = oci_error($stmt);
            $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'No se pudo actualizar el estado.';
        }
        oci_free_statement($stmt);
    } else {
        $errores = 'Seleccione un pedido y un estado válido.';
    }
}

$pedidos = [];
$sql = "SELECT p.id_pedido, p.id_cliente, u.nombre || ' ' || u.apellido AS cliente, p.estado_actual, p.fecha_creacion
        FROM pedido p
        LEFT JOIN usuarios u ON u.id = p.id_cliente
        ORDER BY p.fecha_creacion DESC";
$stmt = oci_parse($conn, $sql);
if (oci_execute($stmt)) {
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $pedidos[] = $row;
    }
}
oci_free_statement($stmt);
Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos - Administración</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Gestión de pedidos</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>
    <?php if ($errores): ?>
        <div class="alert alert-danger"><?= $errores ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado actual</th>
                    <th>Cambiar estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><?= htmlspecialchars($pedido['ID_PEDIDO']) ?></td>
                        <td><?= htmlspecialchars($pedido['CLIENTE']) ?></td>
                        <td><?= htmlspecialchars($pedido['FECHA_CREACION']) ?></td>
                        <td><?= htmlspecialchars($pedido['ESTADO_ACTUAL']) ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="id_pedido" value="<?= htmlspecialchars($pedido['ID_PEDIDO']) ?>">
                                <input type="hidden" name="usuario_cambio" value="admin">
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="PENDIENTE">PENDIENTE</option>
                                    <option value="EN_PROCESO">EN_PROCESO</option>
                                    <option value="ENVIADO">ENVIADO</option>
                                    <option value="ENTREGADO">ENTREGADO</option>
                                    <option value="CANCELADO">CANCELADO</option>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Actualizar</button>
                                <a class="btn btn-sm btn-secondary" href="pedido_detalle.php?id=<?= urlencode($pedido['ID_PEDIDO']) ?>">Ver detalle</a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pedidos)): ?>
                    <tr><td colspan="5" class="text-center">No hay pedidos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>