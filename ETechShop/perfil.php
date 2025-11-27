
<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/functions.php";
include_once "include/headerCompras.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETechShop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Staatliches&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="preload" href="css/perfil.css">
    <link rel="stylesheet" href="css/perfil.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>

<main>
    <br>
<?php
// Obtener información del usuario
$usuario = obtenerInformacionUsuario($_SESSION['usuario']);

// Validación: si no existe el usuario, detener y mostrar mensaje
if (!$usuario || !is_array($usuario)) {
    echo "<div class='alert alert-danger text-center'>
            No se pudo cargar la información del usuario.
          </div>";
    exit;
}
?>

<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-6">
    <div class="card ">
      <div class="card-header text-center">
        <h2>Mi perfil</h2>
      </div>

      <div class="card-body">
        <?php
            // CORREO en mayúsculas desde Oracle
            echo "<strong>Correo: </strong>" . $usuario['CORREO'] . "<br><br>";
        ?>

        <p>Por motivos de seguridad su contraseña no será mostrada.  
           Si desea cambiar su contraseña puede hacerlo.</p>
      </div>

      <div class="card-footer text-body-secondary">
        <a href='cambiarContrasena.php' class='btn btn-primary'>Cambiar Contraseña</a>
      </div>

    </div>
    </div>
    <div class="col-md-3"></div>
</div>



</main>
<br>
<br>
<br>
<br>
<br>
<br>


<?php
include_once "./Include/footer.php"
?>