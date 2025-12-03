<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = $_GET['mensaje'] ?? '';
$productos = [];
$sql = "SELECT p.codigo, p.nombre, p.detalle, p.precio, p.stock, p.estado, c.nombre AS categoria
        FROM productos p
        LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
        WHERE p.estado = 'A'";
$stmt = oci_parse($conn, $sql);
if (oci_execute($stmt)) {
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $productos[] = $row;
    }
} else {
    $mensaje = 'No se pudieron obtener los productos.';
}
oci_free_statement($stmt);
Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - ETechShop</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Productos</h1>
        <a class="btn btn-primary" href="productos_form.php">Nuevo producto</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['CODIGO']) ?></td>
                        <td><?= htmlspecialchars($p['NOMBRE']) ?></td>
                        <td><?= htmlspecialchars($p['CATEGORIA']) ?></td>
                        <td><?= htmlspecialchars($p['PRECIO']) ?></td>
                        <td><?= htmlspecialchars($p['STOCK']) ?></td>
                        <td><?= htmlspecialchars($p['ESTADO']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-secondary" href="productos_form.php?id=<?= urlencode($p['CODIGO']) ?>">Editar</a>
                            <a class="btn btn-sm btn-danger" href="productos_eliminar.php?id=<?= urlencode($p['CODIGO']) ?>" onclick="return confirm('¿Desea inactivar este producto?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="7" class="text-center">No hay productos activos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>