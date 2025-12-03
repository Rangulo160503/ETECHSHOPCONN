<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$nombre = trim($_GET['nombre'] ?? '');
$idCategoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : null;
$precioMin = $_GET['precio_min'] !== '' ? (float)$_GET['precio_min'] : null;
$precioMax = $_GET['precio_max'] !== '' ? (float)$_GET['precio_max'] : null;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$tamanio = 10;

// Categorías
$categorias = [];
$catStmt = oci_parse($conn, "SELECT id_categoria, nombre FROM categoria WHERE estado = 'A' ORDER BY nombre");
oci_execute($catStmt);
while (($row = oci_fetch_assoc($catStmt)) !== false) {
    $categorias[] = $row;
}
oci_free_statement($catStmt);

// Llamar a pkg_busqueda
$productos = [];
$cursor = oci_new_cursor($conn);
$plsql = 'BEGIN pkg_busqueda.buscar_productos(:p_nombre, :p_id_categoria, :p_precio_min, :p_precio_max, :p_pagina, :p_tamanio, :p_cursor); END;';
$stmt = oci_parse($conn, $plsql);
oci_bind_by_name($stmt, ':p_nombre', $nombre, 255);
oci_bind_by_name($stmt, ':p_id_categoria', $idCategoria);
oci_bind_by_name($stmt, ':p_precio_min', $precioMin);
oci_bind_by_name($stmt, ':p_precio_max', $precioMax);
oci_bind_by_name($stmt, ':p_pagina', $pagina);
oci_bind_by_name($stmt, ':p_tamanio', $tamanio);
oci_bind_by_name($stmt, ':p_cursor', $cursor, -1, OCI_B_CURSOR);

$ok = @oci_execute($stmt);
if ($ok) {
    oci_execute($cursor);
    while (($row = oci_fetch_assoc($cursor)) !== false) {
        $productos[] = $row;
    }
}
oci_free_statement($stmt);
oci_free_statement($cursor);
Desconecta($conn);

$tieneAnterior = $pagina > 1;
$tieneSiguiente = count($productos) === $tamanio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda de productos</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Búsqueda</h1>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($nombre) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoría</label>
            <select name="categoria" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['ID_CATEGORIA']) ?>" <?= ($idCategoria == $cat['ID_CATEGORIA']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['NOMBRE']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Precio mínimo</label>
            <input type="number" name="precio_min" class="form-control" step="0.01" value="<?= htmlspecialchars($precioMin ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Precio máximo</label>
            <input type="number" name="precio_max" class="form-control" step="0.01" value="<?= htmlspecialchars($precioMax ?? '') ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['CODIGO']) ?></td>
                        <td><?= htmlspecialchars($p['NOMBRE']) ?></td>
                        <td><?= htmlspecialchars($p['DETALLE']) ?></td>
                        <td><?= htmlspecialchars($p['PRECIO']) ?></td>
                        <td><?= htmlspecialchars($p['STOCK']) ?></td>
                        <td><?= htmlspecialchars($p['ESTADO']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="6" class="text-center">No hay resultados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Paginación">
        <ul class="pagination">
            <li class="page-item <?= $tieneAnterior ? '' : 'disabled' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $pagina-1)])) ?>">Anterior</a>
            </li>
            <li class="page-item active"><span class="page-link">Página <?= $pagina ?></span></li>
            <li class="page-item <?= $tieneSiguiente ? '' : 'disabled' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagina+1])) ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
</div>
</body>
</html>