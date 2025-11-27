
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
<br>
<main>
<div class="card-container">
    <div class="row">
        <div class="col-md-4"></div>
        <div class="col-md-4">
        <div class="card">
            <h2 class="card-title text-center">Cambiar Contraseña</h2>
            <div class="card-body">
            <form method="POST" action="procesarContrasena.php">
                <div class="form-group">
                    <label for="nuevaContrasena">Nueva Contraseña:</label>
                    <input type="password" id="nuevaContrasena" name="nuevaContrasena" class="form-control" required>
                </div>
                <br>
                <button type="submit" class="btn btn-primary btn-contras">Cambiar Contraseña</button>
                <br>
                <br>
                <a href="perfil.php" class="btn btn-secondary btn-contras">Regresar a perfil</a>
            </form>
            </div>
            
        </div>
        </div>
        <div class="col-md-4"></div>
       
    </div>
    </div>
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