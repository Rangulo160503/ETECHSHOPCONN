<?php
require_once 'DAL/conexion.php';

$idPedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idPedido <= 0) {
    die('Pedido inválido');
}

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$pedido = null;
$detalle = [];
$historial = [];

// Datos principales del pedido
$sqlPedido = "SELECT p.id_pedido, p.id_cliente, u.nombre || ' ' || u.apellido AS cliente,
                     p.estado_actual, p.fecha_creacion, p.fecha_ultimo_cambio
              FROM pedido p LEFT JOIN usuarios u ON u.id = p.id_cliente
              WHERE p.id_pedido = :id";
$stmt = oci_parse($conn, $sqlPedido);
oci_bind_by_name($stmt, ':id', $idPedido);
oci_execute($stmt);
$pedido = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

// Historial de estados
$sqlHist = "SELECT estado_anterior, estado_nuevo, fecha_cambio, usuario_cambio
            FROM pedido_estado_historico
            WHERE id_pedido = :id
            ORDER BY fecha_cambio DESC";
$stmtHist = oci_parse($conn, $sqlHist);
oci_bind_by_name($stmtHist, ':id', $idPedido);
oci_execute($stmtHist);
while (($row = oci_fetch_assoc($stmtHist)) !== false) {
    $historial[] = $row;
}
oci_free_statement($stmtHist);

Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de pedido</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <a href="pedidos_admin.php" class="btn btn-link">&larr; Volver</a>
    <h1 class="mb-3">Pedido #<?= htmlspecialchars($idPedido) ?></h1>

    <?php if ($pedido): ?>
        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['CLIENTE']) ?></p>
                <p><strong>Estado actual:</strong> <?= htmlspecialchars($pedido['ESTADO_ACTUAL']) ?></p>
                <p><strong>Fecha creación:</strong> <?= htmlspecialchars($pedido['FECHA_CREACION']) ?></p>
                <p><strong>Último cambio:</strong> <?= htmlspecialchars($pedido['FECHA_ULTIMO_CAMBIO']) ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Pedido no encontrado.</div>
    <?php endif; ?>

    <h2>Historial de estados</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Estado anterior</th>
                    <th>Estado nuevo</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['ESTADO_ANTERIOR']) ?></td>
                        <td><?= htmlspecialchars($h['ESTADO_NUEVO']) ?></td>
                        <td><?= htmlspecialchars($h['FECHA_CAMBIO']) ?></td>
                        <td><?= htmlspecialchars($h['USUARIO_CAMBIO']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($historial)): ?>
                    <tr><td colspan="4" class="text-center">No hay historial registrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>