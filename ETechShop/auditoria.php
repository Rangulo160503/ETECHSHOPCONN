<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

// Filtros desde el formulario (GET)
$tabla = trim($_GET['tabla'] ?? '');
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');

$auditoria = [];
$mensaje   = '';
$errores   = '';

$cond  = [];
$binds = [];

// Filtro por nombre de tabla
if ($tabla !== '') {
    $cond[]          = "UPPER(a.tabla) = UPPER(:tabla)";
    $binds[':tabla'] = $tabla;
}

// Filtro por fecha desde
if ($desde !== '') {
    // input type="date" ya viene en YYYY-MM-DD
    $cond[]           = "a.fch_evento >= TO_DATE(:desde, 'YYYY-MM-DD')";
    $binds[':desde']  = $desde;
}

// Filtro por fecha hasta
if ($hasta !== '') {
    $cond[]           = "a.fch_evento <= TO_DATE(:hasta, 'YYYY-MM-DD')";
    $binds[':hasta']  = $hasta;
}

// Construimos el WHERE dinámico
$where = '';
if (!empty($cond)) {
    $where = ' WHERE ' . implode(' AND ', $cond);
}

// Consulta final
$sql = "
    SELECT
        a.id_aud,
        a.tabla,
        a.operacion,
        a.id_registro,
        a.usuario_bd,
        a.fch_evento,
        a.detalle
    FROM auditoria a
    $where
    ORDER BY a.fch_evento DESC
";

$stmt = oci_parse($conn, $sql);

if (!$stmt) {
    $e = oci_error($conn);
    $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'Error preparando la consulta.';
} else {
    // Vincular parámetros
    foreach ($binds as $k => $v) {
        oci_bind_by_name($stmt, $k, $binds[$k]);
    }

    $ok = @oci_execute($stmt);

    if ($ok) {
        while (($row = oci_fetch_assoc($stmt)) !== false) {
            $auditoria[] = $row;
        }
        if (empty($auditoria)) {
            $mensaje = 'No hay registros que coincidan con los filtros.';
        }
    } else {
        $e = oci_error($stmt);
        $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'No se pudo obtener la auditoría.';
    }

    oci_free_statement($stmt);
}

Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - ETechShop</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Auditoría</h1>

    <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
            <label class="form-label">Tabla</label>
            <input type="text" name="tabla" class="form-control"
                   value="<?= htmlspecialchars($tabla) ?>" placeholder="PRODUCTOS, USUARIOS, ...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control"
                   value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control"
                   value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit">Filtrar</button>
        </div>
    </form>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($errores): ?>
        <div class="alert alert-danger"><?= $errores ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-sm align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Tabla</th>
                <th>Operación</th>
                <th>ID registro</th>
                <th>Usuario BD</th>
                <th>Fecha evento</th>
                <th>Detalle</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($auditoria as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ID_AUD']) ?></td>
                    <td><?= htmlspecialchars($row['TABLA']) ?></td>
                    <td><?= htmlspecialchars($row['OPERACION']) ?></td>
                    <td><?= htmlspecialchars($row['ID_REGISTRO']) ?></td>
                    <td><?= htmlspecialchars($row['USUARIO_BD']) ?></td>
                    <td><?= htmlspecialchars($row['FCH_EVENTO']) ?></td>
                    <td><?= htmlspecialchars($row['DETALLE']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($auditoria)): ?>
                <tr>
                    <td colspan="7" class="text-center">Sin datos</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
