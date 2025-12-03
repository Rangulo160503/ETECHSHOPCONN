<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = (int)($_POST['id_usuario'] ?? 0);
    $rol = (int)($_POST['id_rol'] ?? 0);
    if ($usuario && $rol) {
        $plsql = 'BEGIN sp_asignar_rol(:p_user, :p_rol); :p_msg := ''Rol asignado''; EXCEPTION WHEN OTHERS THEN :p_msg := SQLERRM; END;';
        $stmt = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':p_user', $usuario);
        oci_bind_by_name($stmt, ':p_rol', $rol);
        oci_bind_by_name($stmt, ':p_msg', $mensaje, 4000);
        @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);
    } else {
        $mensaje = 'Seleccione usuario y rol.';
    }
}

// Usuarios con roles
$usuarios = [];
$sqlUsuarios = "SELECT u.id, u.nombre, u.apellido, LISTAGG(r.nombre_rol, ', ') WITHIN GROUP (ORDER BY r.nombre_rol) AS roles
                FROM usuarios u
                LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id
                LEFT JOIN rol r ON r.id_rol = ur.id_rol
                GROUP BY u.id, u.nombre, u.apellido
                ORDER BY u.nombre";
$stmtUsr = oci_parse($conn, $sqlUsuarios);
oci_execute($stmtUsr);
while (($row = oci_fetch_assoc($stmtUsr)) !== false) {
    $usuarios[] = $row;
}
oci_free_statement($stmtUsr);

// Roles
$roles = [];
$stmtRol = oci_parse($conn, "SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol");
oci_execute($stmtRol);
while (($row = oci_fetch_assoc($stmtRol)) !== false) {
    $roles[] = $row;
}
oci_free_statement($stmtRol);
Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios y roles</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Administración de roles</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <h2>Asignar rol</h2>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-5">
            <label class="form-label">Usuario</label>
            <select name="id_usuario" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= htmlspecialchars($u['ID']) ?>"><?= htmlspecialchars($u['NOMBRE'] . ' ' . $u['APELLIDO']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Rol</label>
            <select name="id_rol" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= htmlspecialchars($r['ID_ROL']) ?>"><?= htmlspecialchars($r['NOMBRE_ROL']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 align-self-end text-end">
            <button class="btn btn-primary" type="submit">Asignar</button>
        </div>
    </form>

    <h2>Usuarios</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Roles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['ID']) ?></td>
                        <td><?= htmlspecialchars($u['NOMBRE'] . ' ' . $u['APELLIDO']) ?></td>
                        <td><?= htmlspecialchars($u['ROLES']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="3" class="text-center">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>