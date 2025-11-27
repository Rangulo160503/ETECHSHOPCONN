<?php
require_once "DAL/conexion.php";
require_once "DAL/recoge.php";

$mensajeValidacion = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = recogePost("email");
    $password = recogePost("password");

    if (empty($email) || empty($password)) {
        $mensajeValidacion = "Por favor, completa todos los campos.";
    } else {
        $conn = Conecta();

        // Hashear contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Llamada al procedimiento almacenado
        $sql = "BEGIN sp_registrar_usuario(:correo, :contrasena, :id_out); END;";
        $stid = oci_parse($conn, $sql);

        oci_bind_by_name($stid, ":correo", $email);
        oci_bind_by_name($stid, ":contrasena", $hashedPassword);
        oci_bind_by_name($stid, ":id_out", $id_out, 32);

        $result = @oci_execute($stid);

        if ($result) {
            $mensajeValidacion = "Registro exitoso. ¡Ahora puedes iniciar sesión!";
        } else {
            $e = oci_error($stid);
            $mensajeValidacion = htmlentities($e["message"], ENT_QUOTES);
        }

        oci_free_statement($stid);
        Desconecta($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="es">
 
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.css"
        integrity="sha512-nzSGtgaJWS79WO+bCKLhowPkjbM+WI18/K4T77XPB4QPMybSKLozMFzDNPEHcWxN1dOx1gvYGKP39nXLNNZ8qg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="shortcut icon" href="img/icono.png">
</head>
 
<body>
<header class="header">
    <div class="container">
        &ensp;<div class="col-12 columna-personalizada"> <img src="img/icono.png" >&ensp;Inicio de Sesión</div>
    </div>
</header>
<main>
<div class="row">
  <div class="col-md-1"></div>
    <div class="col-xs-12 col-md-4 centrar">
        <h2 class="text-center">Registrarme en ETechshop</h2>
        <p class="text-center">Regístrate y realiza tus compras en la mejor plataforma de productos tecnológicos.</p>
        <hr>
        <br>
        <form method="POST" action="" autocomplete="off">
            <div class="form-outline">
              <input type="email" class="form-control" id="floatingInput" name="email" autocomplete="off"/>
               <label class="form-label" for="floatingInput">Correo electrónico</label>
               <div class="valid-feedback">Información completa</div>
               <div class="invalid-feedback">Campo Obligatorio</div>
            </div>
            <br>
            <div class="form-outline">
              <input type="password" class="form-control" id="floatingPassword" name="password" autocomplete="off"/>
               <label class="form-label" for="floatingPassword">Contraseña</label>
               <div class="valid-feedback">Información completa</div>
               <div class="invalid-feedback">Campo Obligatorio</div>
            </div>
            <br>
            <div class="form-outline">
              <input type="password" class="form-control" id="floatingPasswordConfirm" name="password_confirm" autocomplete="off"/>
               <label class="form-label" for="floatingPasswordConfirm">Confirmar Contraseña</label>
               <div class="valid-feedback">Información completa</div>
               <div class="invalid-feedback">Campo Obligatorio</div>
            </div>
            <br>
            <span class="text-dark"><?php echo $mensajeValidacion; ?></span>
            <label><input type="checkbox">&ensp;He leído y acepto los <a class="terminos" href="Términos y Condiciones">Términos y Condiciones</a></label>
            <br>
            <br>
            <button type="submit" class="btn btn-primary">Registrarme</button>
            <br>
            <br>
            <div class="row">
              <div class="col-md-5"><hr></div>
              <div class="col-md-2"> <a class="registro" href="index.php">Inicio</a></div>
              <div class="col-md-5"><hr></div>
            </div>
        </form>
    </div>
    <div class="col-md-1"></div>
    <div class="col-xs-12 col-md-6">
        <img src="img/img_registro.png" class="img_login">
    </div>
</div>
</main>
<script src="~/lib/jquery/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"
        integrity="sha512-oFBfx20Vuw8reYrngBlvcrgBmDcEAPE0Vv7Rb9b7JYZNHmDFdxZhiOTGm0CePa7ouSwfty9qwHQck1aVGWK5tA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/4b2f294736.js" crossorigin="anonymous"></script>
</body>
</html>