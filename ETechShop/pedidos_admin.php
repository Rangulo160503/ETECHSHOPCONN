<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = '';
$errores = '';

// Aquí ya no hay POST para cambiar estado porque no existe esa columna ni SP.
// Solo listamos las compras (= pedidos).

$pedidos = [];
$sql = "SELECT c.id,
               c.id_usuario,
               u.correo,
               c.fecha,
               c.total
        FROM compras c
        JOIN usuarios u ON u.id = c.id_usuario
        ORDER BY c.fecha DESC";

$stmt = oci_parse($conn, $sql);
if (oci_execute($stmt)) {
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $pedidos[] = $row;
    }
} else {
    $e = oci_error($stmt);
    $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'No se pudieron obtener los pedidos.';
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
                <th>Usuario (correo)</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td><?= htmlspecialchars($pedido['ID']) ?></td>
                    <td><?= htmlspecialchars($pedido['CORREO']) ?></td>
                    <td><?= htmlspecialchars($pedido['FECHA']) ?></td>
                    <td><?= htmlspecialchars($pedido['TOTAL']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-secondary"
                           href="pedido_detalle.php?id=<?= urlencode($pedido['ID']) ?>">
                            Ver detalle
                        </a>
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
