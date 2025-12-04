<?php
require_once 'DAL/conexion.php';

$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

$mensaje = '';
$errores = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = (int)($_POST['id_usuario'] ?? 0);

    if ($idUsuario > 0) {
        // Usamos el SP_LOGIN_EXITO para resetear intentos y desbloquear
        $plsql = "BEGIN sp_login_exito(:p_id); END;";
        $stmt  = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':p_id', $idUsuario);

        $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if ($ok) {
            $mensaje = 'Usuario desbloqueado correctamente.';
        } else {
            $e       = oci_error($stmt);
            $errores = $e ? htmlentities($e['message'], ENT_QUOTES) : 'No se pudo desbloquear el usuario.';
        }

        oci_free_statement($stmt);
    } else {
        $errores = 'Usuario inválido.';
    }
}


// Listado de usuarios
$usuarios = [];
$sql = "SELECT id, correo, intentos, bloqueado, fch_crea
        FROM usuarios
        ORDER BY id";
$stmt = oci_parse($conn, $sql);
if (oci_execute($stmt)) {
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $usuarios[] = $row;
    }
}
oci_free_statement($stmt);
Desconecta($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Administración</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3">Usuarios</h1>

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
                <th>Correo</th>
                <th>Intentos</th>
                <th>Bloqueado</th>
                <th>Creado</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['ID']) ?></td>
                    <td><?= htmlspecialchars($u['CORREO']) ?></td>
                    <td><?= htmlspecialchars($u['INTENTOS']) ?></td>
                    <td><?= htmlspecialchars($u['BLOQUEADO']) ?></td>
                    <td><?= htmlspecialchars($u['FCH_CREA']) ?></td>
                    <td>
                        <?php if ($u['BLOQUEADO'] === 'S'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="id_usuario"
                                       value="<?= htmlspecialchars($u['ID']) ?>">
                                <button class="btn btn-sm btn-warning" type="submit">
                                    Desbloquear
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="6" class="text-center">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
