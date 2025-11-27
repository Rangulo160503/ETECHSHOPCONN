<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/functions.php";
include_once "include/headerCompras.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETechShop</title>

    <link rel="stylesheet" href="./css/compras.css">
    <link rel="stylesheet" href="./css/normalize.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Staatliches&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.css"
          integrity="sha512-nzSGtgaJWS79WO+bCKLhowPkjbM+WI18/K4T77XPB4QPMybSKLozMFzDNPEHcWxN1dOx1gvYGKP39nXLNNZ8qg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM"
          crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<main>
    <br>
    <div class="contenedor1">
        <div class="contenedor1-A">
            <h1>Descuento de 50% en compras mayores a 4 articulos</h1>
            <p>Revisa la información de los artículos</p>
            <button class="btn btn_comprar">Comprar</button>
        </div>
        <div class="contenedor1-B">
            <img src="./img/fondo.jpg" class="img_mac">
        </div>
    </div>

    <br>
    <h4>Novedades de hoy</h4>
    <br>

    <div class="objetos">
        <?php
        $nombreUsuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
        $productos = getAvailableProducts();

        foreach ($productos as $product) {
            echo '
            <div class="card" style="border-color:transparent">
                <img src="' . $product['IMAGEN'] . '" alt="' . $product['NOMBRE'] . '" />
                <hr>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <p style="color:#39496B"><strong>' . $product['NOMBRE'] . '</strong></p>
                        </div>
                        <div class="col-md-5">
                            <p class="price">₡' . $product['PRECIO'] . '</p>
                        </div>
                    </div>

                    <p>' . $product['DETALLE'] . '</p>

                    <div class="text-center">
                        <a class="btn btn-outline-secondary"
                           href="agregarproductoCompras.php?id=' . $product['CODIGO'] . '"
                           onclick="showNotification()">
                           <i class="fa-light fa-cart-plus"></i>&ensp;Añadir al carrito
                        </a>
                    </div>

                </div>
            </div>
            ';
        }
        ?>
    </div>
</main>

<?php include_once "./Include/footer.php" ?>

<script>
    function showNotification() {
        Swal.fire({
            icon: 'success',
            title: 'Agregado correctamente al carrito',
            showConfirmButton: false,
            timer: 1500
        });
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"
        crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>

<script src="https://kit.fontawesome.com/4b2f294736.js" crossorigin="anonymous"></script>

</body>
</html>
