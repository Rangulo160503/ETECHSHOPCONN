<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$catSeleccionada = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$estadoFiltro = $_GET['estado'] ?? null;

// Cargar categorías
$categorias = [];
$catStmt = oci_parse($conn, "SELECT id_categoria, nombre FROM categoria WHERE estado = 'A' ORDER BY nombre");
oci_execute($catStmt);
while (($row = oci_fetch_assoc($catStmt)) !== false) {
    $categorias[] = $row;
}
oci_free_statement($catStmt);

// Llamar a procedimiento de productos filtrados
$productos = [];
$cursor = oci_new_cursor($conn);
$plsql = 'BEGIN sp_categoria_filtrar_prod(:p_id_categoria, :p_estado, :p_cursor); END;';
$stmt = oci_parse($conn, $plsql);

if ($catSeleccionada) {
    oci_bind_by_name($stmt, ':p_id_categoria', $catSeleccionada);
} else {
    $null = null;
    oci_bind_by_name($stmt, ':p_id_categoria', $null);
}
if ($estadoFiltro) {
    oci_bind_by_name($stmt, ':p_estado', $estadoFiltro, 1);
} else {
    $null2 = null;
    oci_bind_by_name($stmt, ':p_estado', $null2);
}
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de productos</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Catálogo</h1>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Categoría</label>
            <select name="categoria" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['ID_CATEGORIA']) ?>" <?= ($catSeleccionada == $cat['ID_CATEGORIA']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['NOMBRE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="">Todos</option>
                <option value="A" <?= $estadoFiltro === 'A' ? 'selected' : '' ?>>Activos</option>
                <option value="I" <?= $estadoFiltro === 'I' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="row g-3">
        <?php foreach ($productos as $p): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($p['NOMBRE']) ?></h5>
                        <p class="card-text">Categoría: <?= htmlspecialchars($p['CATEGORIA']) ?></p>
                        <p class="card-text">Precio: ₡<?= htmlspecialchars($p['PRECIO']) ?></p>
                        <p class="card-text">Stock: <?= htmlspecialchars($p['STOCK']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($productos)): ?>
            <p>No hay productos con los filtros seleccionados.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>