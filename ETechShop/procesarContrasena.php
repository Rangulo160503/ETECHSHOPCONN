<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/recoge.php";

// Si no hay sesión, fuera
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$idUsuario = (int)$_SESSION['id_usuario'];
$nuevaContrasena = recogePost("nuevaContrasena");

if ($nuevaContrasena === "") {
    $_SESSION['mensaje'] = "La nueva contraseña no puede ir vacía.";
    header("Location: cambiarContrasena.php");
    exit();
}

$conn = Conecta();
if (!$conn) {
    $_SESSION['mensaje'] = "No se pudo conectar a Oracle.";
    header("Location: cambiarContrasena.php");
    exit();
}

// ✅ 1) generar bcrypt
$hashNuevo = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

// ✅ 2) llamar SP
$sql = "BEGIN sp_cambiar_contrasena(:id, :hash); END;";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $idUsuario);
oci_bind_by_name($stid, ":hash", $hashNuevo);

$ok = oci_execute($stid);

if ($ok) {
    $_SESSION['mensaje'] = "Contraseña actualizada con éxito.";
} else {
    $e = oci_error($stid);
    $_SESSION['mensaje'] = "Error al actualizar contraseña: " . $e["message"];
}

oci_free_statement($stid);
Desconecta($conn);

header("Location: perfil.php");
exit();
?>
