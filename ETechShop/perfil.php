<?php
session_start();
require_once 'DAL/conexion.php';

$mensaje = '';
$errores = '';

// 1. Obtener ID de usuario desde la sesión (única fuente de verdad)
$usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;

if ($usuarioId <= 0) {
    header('Location: index.php');
    exit;
}

// 2. Conectar a Oracle
$conn = getConnection();
if (!$conn) {
    die('No se pudo conectar a la base de datos.');
}

// 3. Función para obtener los datos del usuario
function obtenerUsuario($conn, $id)
{
    $sql = 'SELECT id, correo, contrasena, intentos, bloqueado, fch_crea 
            FROM usuarios 
            WHERE id = :id';
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $data = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    return $data ?: null;
}

// 4. Procesar cambio de contraseña (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['contrasena_actual'] ?? '';
    $nueva  = $_POST['contrasena_nueva'] ?? '';
    $conf   = $_POST['contrasena_confirma'] ?? '';

    if ($actual === '' || $nueva === '' || $conf === '') {
        $errores = 'Debe completar todos los campos de contraseña.';
    } elseif ($nueva !== $conf) {
        $errores = 'La nueva contraseña y la confirmación no coinciden.';
    } else {
        // Leer hash actual desde BD
        $sqlHash = 'SELECT contrasena FROM usuarios WHERE id = :id';
        $stmtHash = oci_parse($conn, $sqlHash);
        oci_bind_by_name($stmtHash, ':id', $usuarioId);
        oci_execute($stmtHash);
        $rowHash = oci_fetch_assoc($stmtHash);
        oci_free_statement($stmtHash);

        if (!$rowHash) {
            $errores = 'No se encontró el usuario en la base de datos.';
        } else {
            $hashActual = $rowHash['CONTRASENA'];

            // Verificar contraseña actual (bcrypt)
            if (!password_verify($actual, $hashActual)) {
                $errores = 'La contraseña actual no es correcta.';
            } else {
                // Generar nuevo hash
                $hashNuevo = password_hash($nueva, PASSWORD_BCRYPT);

                // Llamar SP_CAMBIAR_CONTRASENA(p_id, p_hash_nuevo)
                $plsql = 'BEGIN sp_cambiar_contrasena(:p_id, :p_hash_nuevo); END;';
                $stmt = oci_parse($conn, $plsql);
                oci_bind_by_name($stmt, ':p_id', $usuarioId);
                oci_bind_by_name($stmt, ':p_hash_nuevo', $hashNuevo, 255);

                $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
                if ($ok) {
                    $mensaje = 'Contraseña actualizada correctamente.';
                } else {
                    $e = oci_error($stmt);
                    $errores = $e ? htmlentities($e['message'], ENT_QUOTES)
                                  : 'Error al actualizar la contraseña.';
                }
                oci_free_statement($stmt);
            }
        }
    }
}

// 5. Volver a cargar datos del usuario para pintar la vista
$usuario = obtenerUsuario($conn, $usuarioId);

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
        <!-- Datos generales del usuario (solo lectura) -->
        <div class="card mb-4">
            <div class="card-body">
                <p><strong>ID:</strong> <?= htmlspecialchars($usuario['ID']) ?></p>
                <p><strong>Correo:</strong> <?= htmlspecialchars($usuario['CORREO']) ?></p>
                <p><strong>Intentos fallidos:</strong> <?= htmlspecialchars($usuario['INTENTOS']) ?></p>
                <p><strong>Bloqueado:</strong> <?= htmlspecialchars($usuario['BLOQUEADO']) ?></p>
                <p><strong>Fecha de creación:</strong> <?= htmlspecialchars($usuario['FCH_CREA']) ?></p>
            </div>
        </div>

        <!-- Formulario de cambio de contraseña -->
        <form method="POST" class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Cambiar contraseña</h2>
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" name="contrasena_actual" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" name="contrasena_nueva" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" name="contrasena_confirma" class="form-control" required>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">No se encontró la información del usuario.</div>
    <?php endif; ?>
</div>
</body>
</html>
