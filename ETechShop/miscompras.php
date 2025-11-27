<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/functions.php";
include_once "include/headerCompras.php";

/* ============================================================
   1) Usuario actual: usar el ID del usuario (NO el correo)
   ============================================================ */
$usuarioId = $_SESSION['id_usuario'] ?? null;

if (!$usuarioId) {
    echo "<p>Error: No se encontró la sesión del usuario.</p>";
    exit;
}

/* ============================================================
   2) Función para mostrar compras desde Oracle
   ============================================================ */
function mostrarComprasUsuario($usuarioId)
{
    global $cn;

    $cn = Conecta();
    if (!$cn) {
        echo "<p>Error: No se pudo conectar a Oracle.</p>";
        return;
    }

    // Consulta real: debe usar USUARIO_ID
    $sql = "
        SELECT ID, TOTAL, FECHA
        FROM COMPRAS
        WHERE ID_USUARIO = :id
        ORDER BY FECHA DESC
    ";

    $stid = oci_parse($cn, $sql);
    oci_bind_by_name($stid, ":id", $usuarioId);
    oci_execute($stid);

    echo "<h2 class='mt-4'>Mis compras</h2>";

    $hayResultados = false;

    while ($compra = oci_fetch_assoc($stid)) {
        $hayResultados = true;

        echo '<div class="card mt-3">';
        echo '  <div class="card-body">';
        echo "    <h5 class='card-title'>Compra #{$compra['ID']}</h5>";
        echo "    <p><strong>Total:</strong> ₡{$compra['TOTAL']}</p>";
        echo "    <p><strong>Fecha:</strong> {$compra['FECHA']}</p>";
        echo '  </div>';
        echo '</div>';
    }

    if (!$hayResultados) {
        echo "<p class='mt-3'>No has realizado compras.</p>";
    }

    oci_free_statement($stid);
    Desconecta($cn);
}

?>
<!DOCTYPE html>
<html lang="es">
<body>
    <main>
        <div class="container">
            <?php mostrarComprasUsuario($usuarioId); ?>
        </div>
    </main>
</body>
</html>
