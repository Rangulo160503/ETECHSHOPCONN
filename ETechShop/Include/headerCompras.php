<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETechShop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Staatliches&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="preload" href="css/style.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/headerCompras.css">
    <link rel="stylesheet" href="css/camcontrasena.css">
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.css"
        integrity="sha512-nzSGtgaJWS79WO+bCKLhowPkjbM+WI18/K4T77XPB4QPMybSKLozMFzDNPEHcWxN1dOx1gvYGKP39nXLNNZ8qg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">


</head>

<body>
    <header class="headerclass">
    <nav class="navbar navbar-expand-lg bg-body-tertiary" style="width: 100%;">
    <div class="container-fluid">
         <a class="navbar-brand" href="Compras.php"><img src="img/icono.png" >&ensp;ETechshop</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
         </button>
      <div class="collapse navbar-collapse" id="navbarText">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
        <?php
    if (isset($_SESSION['usuario']) && $_SESSION['usuario'] == 'administrador@gmail.com') {
        echo '<a class="nav-link" data-bs-toggle="modal" data-bs-target="#exampleModal">Agregar Producto</a>';
    }
    ?>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="miscompras.php">Mis Compras</a>
        </li>
      </ul>
     
      <ul class="nav nav-pills">
       <li class="nav-item">
          <a class="nav-link" aria-current="page" href="perfil.php"><i class="fa-light fa-user"></i>&ensp;Perfil</a>
       </li>
       <li class="nav-item">
         <a class="nav-link" href="carritoCompras.php"><i class="fa-light fa-cart-shopping"></i>&ensp;Carrito Compras</a>
        </li>
        <?php
                    if (isset($_SESSION['usuario'])) {
                        echo '<li class="nav-item"><a class="nav-link" href="cerrar_sesion.php">Cerrar Sesión</a></li>';
                    }
                    ?>
      </ul>
      </div>
  </div>
</nav>
</header>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Agregar Producto</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <form action="agregarproductoAdmin.php" method="post">
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" autocomplete="off" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="detalle">Detalle:</label>
                            <textarea class="form-control" autocomplete="off" id="detalle" name="detalle" rows="3" required></textarea>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="precio">Precio:</label>
                            <input type="number" autocomplete="off" class="form-control" id="precio" name="precio" min="0" step="0.01" required>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="imagen">URL de la imagen:</label>
                            <input type="text" autocomplete="off" class="form-control" id="imagen" name="imagen" required>
                        </div>
                        <br>
                        <button type="submit" class="btn btn-primary">Guardar Producto</button>
                    </form>
      </div>
    </div>
  </div>
</div>

      <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"
        integrity="sha512-oFBfx20Vuw8reYrngBlvcrgBmDcEAPE0Vv7Rb9b7JYZNHmDFdxZhiOTGm0CePa7ouSwfty9qwHQck1aVGWK5tA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/4b2f294736.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/4b2f294736.js" crossorigin="anonymous"></script>
</body>