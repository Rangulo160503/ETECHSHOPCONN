<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/recoge.php";
require_once "DAL/functions.php";

/* ========= 1) Validar sesión de usuario ========= */
$usuarioId = $_SESSION['id_usuario'] ?? 0;
if ($usuarioId == 0) {
    $_SESSION['mensaje'] = "Error: no hay sesión activa.";
    header("Location: carritoCompras.php");
    exit();
}

/* ========= 2) Validar carrito ========= */
/*
  Asegurate de usar el MISMO nombre de sesión
  que usás al agregar productos al carrito.
  (en tu proyecto lo correcto es 'carrito' o 'carritoCompras').
*/

$carrito = $_SESSION['carritoCompras'] ?? [];  // <-- si tu proyecto usa carritoCompras, cambiá aquí

if (empty($carrito)) {
    $_SESSION['mensaje'] = "Carrito vacío. No se puede procesar compra.";
    header("Location: carritoCompras.php");
    exit();
}

/* ========= 3) Calcular total real desde BD ========= */
$cn = Conecta();
if (!$cn) {
    $_SESSION['mensaje'] = "No se pudo conectar a Oracle.";
    header("Location: carritoCompras.php");
    exit();
}

$totalCompra = 0;

foreach ($carrito as $idProducto => $p) {
    $precio = (float)$p["PRECIO"];
    $cantidad = (int)($p["cantidad"] ?? 1);
    $totalCompra += $precio * $cantidad;
}

/* ========= 4) Insertar compra ========= */
$fechaCompra = date("Y-m-d H:i:s");

$sqlInsert = "INSERT INTO COMPRAS (ID_USUARIO, TOTAL, FECHA)
              VALUES (:idUsuario, :total, TO_DATE(:fecha, 'YYYY-MM-DD HH24:MI:SS'))
              RETURNING ID INTO :idCompra";

$stid = oci_parse($cn, $sqlInsert);
oci_bind_by_name($stid, ":idUsuario", $usuarioId);
oci_bind_by_name($stid, ":total", $totalCompra);
oci_bind_by_name($stid, ":fecha", $fechaCompra);
oci_bind_by_name($stid, ":idCompra", $idCompra, 32);

$execute = oci_execute($stid, OCI_COMMIT_ON_SUCCESS);

if (!$execute) {
    $e = oci_error($stid);
    $_SESSION['mensaje'] = "Error Oracle: " . $e['message'];
    oci_free_statement($stid);
    Desconecta($cn);
    header("Location: carritoCompras.php");
    exit();
}

/* ========= 5) Vaciar carrito ========= */
unset($_SESSION['carritoCompras']);  // <-- si tu proyecto usa carritoCompras, cambiá aquí también

$_SESSION['mensaje'] = "Compra procesada con éxito. ID Compra: $idCompra";

/* ========= 6) Ir a Mis Compras ========= */
oci_free_statement($stid);
Desconecta($cn);

header("Location: miscompras.php");
exit();
?>
