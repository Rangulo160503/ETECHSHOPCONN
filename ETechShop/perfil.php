<?php
session_start();
require_once 'DAL/conexion.php';

$mensaje = '';
$errores = '';
$usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
if (!$usuarioId && isset($_SESSION['usuario'])) {
    $usuarioId = (int)$_SESSION['usuario'];
}

if (!$usuarioId) {
    header('Location: index.php');
    exit;
}
$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}
function obtenerUsuario($conn, $id)
{
    $sql = 'SELECT id, nombre, apellido, correo, telefono, direccion FROM usuarios WHERE id = :id';
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $data = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    return $data ?: null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

      <div class="card-footer text-body-secondary">
        <a href='cambiarContrasena.php' class='btn btn-primary'>Cambiar Contraseña</a>
      </div>
    if ($nombre === '' || $apellido === '' || $correo === '') {
        $errores = 'Nombre, apellido y correo son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores = 'Ingrese un correo válido.';
    } else {
        $plsql = 'BEGIN sp_actualizar_perfil(:p_id, :p_nombre, :p_apellido, :p_correo, :p_telefono, :p_direccion); END;';
        $stmt = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':p_id', $usuarioId);
        oci_bind_by_name($stmt, ':p_nombre', $nombre, 100);
        oci_bind_by_name($stmt, ':p_apellido', $apellido, 100);
        oci_bind_by_name($stmt, ':p_correo', $correo, 255);
        oci_bind_by_name($stmt, ':p_telefono', $telefono, 50);
        oci_bind_by_name($stmt, ':p_direccion', $direccion, 400);

        $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        if ($ok) {
            $mensaje = 'Perfil actualizado correctamente.';
        } else {
            $e = oci_error($stmt);
            $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'Error al actualizar el perfil.';
        }
        oci_free_statement($stmt);
    }
}

$usuario = obtenerUsuario($conn, $usuarioId);

$auditSql = 'SELECT campo_modificado, valor_anterior, valor_nuevo, fecha_cambio, usuario_evento
             FROM auditoria_usuario
             WHERE id_usuario = :id
             ORDER BY fecha_cambio DESC FETCH FIRST 10 ROWS ONLY';
$auditStmt = oci_parse($conn, $auditSql);
oci_bind_by_name($auditStmt, ':id', $usuarioId);
oci_execute($auditStmt);
$auditRows = [];
while (($row = oci_fetch_assoc($auditStmt)) !== false) {
    $auditRows[] = $row;
}
oci_free_statement($auditStmt);

Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi perfil - ETechShop</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/perfil.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Mi perfil</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <?php if ($errores): ?>
        <div class="alert alert-danger"><?= $errores ?></div>
    <?php endif; ?>

    <?php if ($usuario): ?>
    <form method="POST" class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['NOMBRE'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($usuario['APELLIDO'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($usuario['CORREO'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario['TELEFONO'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($usuario['DIRECCION'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
    <?php else: ?>
        <div class="alert alert-warning">No se encontró la información del usuario.</div>
    <?php endif; ?>

    <h2>Auditoría de perfil</h2>
    <?php if (count($auditRows) === 0): ?>
        <p>No hay cambios registrados.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Campo</th>
                    <th>Valor anterior</th>
                    <th>Valor nuevo</th>
                    <th>Usuario BD</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['FECHA_CAMBIO']) ?></td>
                        <td><?= htmlspecialchars($row['CAMPO_MODIFICADO']) ?></td>
                        <td><?= htmlspecialchars($row['VALOR_ANTERIOR']) ?></td>
                        <td><?= htmlspecialchars($row['VALOR_NUEVO']) ?></td>
                        <td><?= htmlspecialchars($row['USUARIO_EVENTO']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html>