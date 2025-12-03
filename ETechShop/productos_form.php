<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$mensaje = '';
$errores = '';
$producto = null;

function cargarCategorias($conn)
{
    $categorias = [];
    $sql = "SELECT id_categoria, nombre FROM categoria WHERE estado = 'A' ORDER BY nombre";
    $stmt = oci_parse($conn, $sql);
    if (oci_execute($stmt)) {
        while (($row = oci_fetch_assoc($stmt)) !== false) {
            $categorias[] = $row;
        }
    }
    oci_free_statement($stmt);
    return $categorias;
}

function cargarProducto($conn, $id)
{
    $sql = "SELECT codigo, nombre, detalle, precio, stock, id_categoria, estado FROM productos WHERE codigo = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $data = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    return $data ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $idCategoria = $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;
    $estado = $_POST['estado'] ?? 'A';

    if ($nombre === '' || !$idCategoria) {
        $errores = 'Nombre e id de categoría son obligatorios.';
    } else {
        if ($id) {
            $plsql = 'BEGIN sp_actualizar_producto(:p_id, :p_nombre, :p_desc, :p_precio, :p_stock, :p_cat, :p_estado, :p_mensaje); END;';
        } else {
            $plsql = 'BEGIN sp_crear_producto(:p_nombre, :p_desc, :p_precio, :p_stock, :p_cat, :p_estado, :p_mensaje); END;';
        }
        $stmt = oci_parse($conn, $plsql);
        if ($id) {
            oci_bind_by_name($stmt, ':p_id', $id);
        }
        oci_bind_by_name($stmt, ':p_nombre', $nombre, 200);
        oci_bind_by_name($stmt, ':p_desc', $descripcion, 4000);
        oci_bind_by_name($stmt, ':p_precio', $precio);
        oci_bind_by_name($stmt, ':p_stock', $stock);
        oci_bind_by_name($stmt, ':p_cat', $idCategoria);
        oci_bind_by_name($stmt, ':p_estado', $estado, 1);
        oci_bind_by_name($stmt, ':p_mensaje', $mensaje, 4000);

        $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        if ($ok) {
            header('Location: productos_listar.php?mensaje=' . urlencode($mensaje));
            exit;
        } else {
            $e = oci_error($stmt);
            $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'Error al guardar el producto.';
        }
        oci_free_statement($stmt);
    }
}

if ($id && !$producto) {
    $producto = cargarProducto($conn, $id);
}
$categorias = cargarCategorias($conn);
Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Editar' : 'Nuevo' ?> producto - ETechShop</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3"><?= $id ? 'Editar' : 'Nuevo' ?> producto</h1>

    <?php if ($errores): ?>
        <div class="alert alert-danger"><?= $errores ?></div>
    <?php endif; ?>

    <form method="POST" class="card">
        <div class="card-body">
            <?php if ($id): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['NOMBRE'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($producto['DETALLE'] ?? '') ?></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Precio</label>
                    <input type="number" step="0.01" name="precio" class="form-control" value="<?= htmlspecialchars($producto['PRECIO'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($producto['STOCK'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoría</label>
                    <select name="id_categoria" class="form-select" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['ID_CATEGORIA']) ?>" <?= isset($producto['ID_CATEGORIA']) && $producto['ID_CATEGORIA'] == $cat['ID_CATEGORIA'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['NOMBRE']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="A" <?= ($producto['ESTADO'] ?? 'A') === 'A' ? 'selected' : '' ?>>Activo</option>
                    <option value="I" <?= ($producto['ESTADO'] ?? '') === 'I' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="productos_listar.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
</body>
</html>