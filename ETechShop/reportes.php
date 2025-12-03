<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$ventas = [];
$topProductos = [];
$errorVentas = '';
$errorTop = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'ventas') {
        $desde = $_POST['fecha_desde'] ?? '';
        $hasta = $_POST['fecha_hasta'] ?? '';
        if ($desde && $hasta) {
            $cursor = oci_new_cursor($conn);
            $stmt = oci_parse($conn, 'BEGIN pkg_reportes.reporte_ventas_por_fecha(:p_desde, :p_hasta, :p_cursor); END;');
            oci_bind_by_name($stmt, ':p_desde', $desde);
            oci_bind_by_name($stmt, ':p_hasta', $hasta);
            oci_bind_by_name($stmt, ':p_cursor', $cursor, -1, OCI_B_CURSOR);
            $ok = @oci_execute($stmt);
            if ($ok) {
                oci_execute($cursor);
                while (($row = oci_fetch_assoc($cursor)) !== false) {
                    $ventas[] = $row;
                }
            } else {
                $e = oci_error($stmt);
                $errorVentas = $e ? htmlentities($e['message'], ENT_QUOTES) : 'Error generando reporte.';
            }
            oci_free_statement($stmt);
            oci_free_statement($cursor);
        } else {
            $errorVentas = 'Seleccione el rango de fechas.';
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'top') {
        $topN = (int)($_POST['top_n'] ?? 5);
        $cursor = oci_new_cursor($conn);
        $stmt = oci_parse($conn, 'BEGIN pkg_reportes.reporte_top_productos(:p_top, :p_cursor); END;');
        oci_bind_by_name($stmt, ':p_top', $topN);
        oci_bind_by_name($stmt, ':p_cursor', $cursor, -1, OCI_B_CURSOR);
        $ok = @oci_execute($stmt);
        if ($ok) {
            oci_execute($cursor);
            while (($row = oci_fetch_assoc($cursor)) !== false) {
                $topProductos[] = $row;
            }
        } else {
            $e = oci_error($stmt);
            $errorTop = $e ? htmlentities($e['message'], ENT_QUOTES) : 'Error generando reporte.';
        }
        oci_free_statement($stmt);
        oci_free_statement($cursor);
    }
}

Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Reportes</h1>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Ventas por fecha</div>
                <div class="card-body">
                    <form method="POST" class="row g-2 mb-3">
                        <input type="hidden" name="accion" value="ventas">
                        <div class="col-6">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" required>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary" type="submit">Generar</button>
                        </div>
                    </form>
                    <?php if ($errorVentas): ?>
                        <div class="alert alert-danger"><?= $errorVentas ?></div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventas as $v): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($v['ID']) ?></td>
                                        <td><?= htmlspecialchars($v['ID_CLIENTE']) ?></td>
                                        <td><?= htmlspecialchars($v['ESTADO_ACTUAL']) ?></td>
                                        <td><?= htmlspecialchars($v['FECHA_CREACION']) ?></td>
                                        <td><?= htmlspecialchars($v['TOTAL']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ventas)): ?>
                                    <tr><td colspan="5" class="text-center">Sin datos</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Top productos</div>
                <div class="card-body">
                    <form method="POST" class="row g-2 mb-3">
                        <input type="hidden" name="accion" value="top">
                        <div class="col-8">
                            <label class="form-label">Cantidad</label>
                            <input type="number" min="1" name="top_n" class="form-control" value="5">
                        </div>
                        <div class="col-4 text-end align-self-end">
                            <button class="btn btn-primary" type="submit">Obtener</button>
                        </div>
                    </form>
                    <?php if ($errorTop): ?>
                        <div class="alert alert-danger"><?= $errorTop ?></div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID producto</th>
                                    <th>Nombre</th>
                                    <th>Total vendidas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProductos as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['CODIGO_PROD']) ?></td>
                                        <td><?= htmlspecialchars($p['NOMBRE']) ?></td>
                                        <td><?= htmlspecialchars($p['TOTAL_VENDIDA']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topProductos)): ?>
                                    <tr><td colspan="3" class="text-center">Sin datos</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>