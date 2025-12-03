<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$tabla = trim($_GET['tabla'] ?? '');
$operacion = trim($_GET['operacion'] ?? '');
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$pagina = max(1, (int)($_GET['page'] ?? 1));
$tamanio = 20;
$inicio = (($pagina - 1) * $tamanio) + 1;
$fin = $inicio + $tamanio - 1;

$where = 'WHERE 1=1';
$binds = [];
if ($tabla !== '') {
    $where .= ' AND nombre_tabla = :tabla';
    $binds[':tabla'] = $tabla;
}
if ($operacion !== '') {
    $where .= ' AND operacion = :op';
    $binds[':op'] = $operacion;
}
if ($desde !== '') {
    $where .= ' AND fecha >= TO_DATE(:desde, ''YYYY-MM-DD'')';
    $binds[':desde'] = $desde;
}
if ($hasta !== '') {
    $where .= ' AND fecha <= TO_DATE(:hasta, ''YYYY-MM-DD'')';
    $binds[':hasta'] = $hasta;
}

$sql = "SELECT * FROM (
            SELECT a.*, ROW_NUMBER() OVER (ORDER BY fecha DESC) rn
            FROM auditoria_general a
            $where
        )
        WHERE rn BETWEEN :ini AND :fin";
$stmt = oci_parse($conn, $sql);
foreach ($binds as $key => $val) {
    oci_bind_by_name($stmt, $key, $binds[$key]);
}
oci_bind_by_name($stmt, ':ini', $inicio);
oci_bind_by_name($stmt, ':fin', $fin);

$registros = [];
$ok = oci_execute($stmt);
if ($ok) {
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $registros[] = $row;
    }
}
oci_free_statement($stmt);
Desconecta($conn);

$tieneAnterior = $pagina > 1;
$tieneSiguiente = count($registros) === $tamanio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Auditoría general</h1>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Tabla</label>
            <input type="text" name="tabla" class="form-control" value="<?= htmlspecialchars($tabla) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Operación</label>
            <select name="operacion" class="form-select">
                <option value="">Todas</option>
                <option value="INSERT" <?= $operacion === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                <option value="UPDATE" <?= $operacion === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                <option value="DELETE" <?= $operacion === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button class="btn btn-primary" type="submit">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tabla</th>
                    <th>Operación</th>
                    <th>ID Registro</th>
                    <th>Usuario BD</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['FECHA']) ?></td>
                        <td><?= htmlspecialchars($r['NOMBRE_TABLA']) ?></td>
                        <td><?= htmlspecialchars($r['OPERACION']) ?></td>
                        <td><?= htmlspecialchars($r['ID_REGISTRO']) ?></td>
                        <td><?= htmlspecialchars($r['USUARIO_BD']) ?></td>
                        <td><?= htmlspecialchars($r['DETALLE']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="6" class="text-center">No hay registros para los filtros indicados.</td></tr>
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