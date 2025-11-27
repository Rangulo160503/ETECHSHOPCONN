<?php
require_once "DAL/conexion.php";
require_once "DAL/recoge.php";
session_start();

$conn = Conecta();
if (!$conn) { die("No se pudo conectar a Oracle."); }

$mensajeValidacion = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $mensajeValidacion = "Debes completar correo y contraseña.";
    } else {

        // FN retorna ID y hash
        $sql = "BEGIN :v_id := fn_login_usuario(:email, :hash_out); END;";

        $stid = oci_parse($conn, $sql);
        $v_id = 0;
        $hash_db = '';

        oci_bind_by_name($stid, ":email", $email);
        oci_bind_by_name($stid, ":hash_out", $hash_db, 255);
        oci_bind_by_name($stid, ":v_id", $v_id, 32);

        oci_execute($stid);

        // Usuario bloqueado
        if ($v_id == -1) {
            $mensajeValidacion = "Cuenta bloqueada. Contacta al administrador.";
        }
        // Usuario no existe
        else if ($v_id == 0) {
            $mensajeValidacion = "Correo o contraseña incorrectos.";
        }
        else if ($v_id > 0) {

            // Validar bcrypt
            if (password_verify($password, $hash_db)) {

                // Login exitoso → resetear intentos
                $sqlEx = "BEGIN sp_login_exito(:id); END;";
                $stEx = oci_parse($conn, $sqlEx);
                oci_bind_by_name($stEx, ":id", $v_id);
                oci_execute($stEx);

                $_SESSION['usuario'] = $email;
                $_SESSION['id_usuario'] = $v_id;

                header("Location: Compras.php");
                exit();

            } else {
                // Contraseña incorrecta → sumar intentos
                $sqlFail = "BEGIN sp_login_fallo(:id); END;";
                $stFail = oci_parse($conn, $sqlFail);
                oci_bind_by_name($stFail, ":id", $v_id);
                oci_execute($stFail);

                // Mensaje dinámico según estado tras fallo
                $sqlEstado = "SELECT intentos, bloqueado 
                              FROM usuarios WHERE id = :id";
                $stEstado = oci_parse($conn, $sqlEstado);
                oci_bind_by_name($stEstado, ":id", $v_id);
                oci_execute($stEstado);
                $estado = oci_fetch_assoc($stEstado);

                if ($estado['BLOQUEADO'] === 'S') {
                    $mensajeValidacion = "Cuenta bloqueada por 3 intentos fallidos.";
                } else {
                    $left = 3 - (int)$estado['INTENTOS'];
                    $mensajeValidacion = "Contraseña incorrecta. Te quedan $left intento(s).";
                }
            }
        }

        oci_free_statement($stid);
    }

    Desconecta($conn);
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETechShop</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="shortcut icon" href="img/icono.png">
</head>

<body>
<header class="header">
    <div class="container">
        &ensp;<div class="col-12 columna-personalizada">
            <img src="img/icono.png" >&ensp;Inicio de Sesión
        </div>
    </div>
</header>

<main>
<div class="row">
    <div class="col-md-1"></div>

    <div class="col-xs-12 col-md-4 centrar">
        <h2 class="text-center">Bienvenidos a ETechshop</h2>
        <p class="text-center">Deseas comprar productos tecnológicos, EtechShop es tu plataforma!</p>
        <hr>
        <br>

        <form method="POST" action="">
            <div class="form-outline">
                <input type="email" class="form-control" id="floatingInput" name="email" required />
                <label class="form-label" for="floatingInput">Correo electrónico</label>
            </div>
            <br>

            <div class="form-outline">
                <input type="password" class="form-control" id="floatingPassword" name="password" required />
                <label class="form-label" for="floatingPassword">Contraseña</label>
            </div>

            <?php if ($mensajeValidacion !== ""): ?>
                <div class="alert alert-warning mt-3 text-center">
                    <?= htmlspecialchars($mensajeValidacion) ?>
                </div>
            <?php endif; ?>

            <br>
            <button class="btn btn-primary w-100">Iniciar Sesión</button>

            <br><br>
            <div class="row">
                <div class="col-md-5"><hr></div>
                <div class="col-md-2 text-center">
                    <a class="registro" href="registrar.php">Registro</a>
                </div>
                <div class="col-md-5"><hr></div>
            </div>
        </form>
    </div>

    <div class="col-md-1"></div>
    <div class="col-xs-12 col-md-6">
        <img src="img/img_login.png" class="img_login">
    </div>
</div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>