<?php
session_start();
require_once "DAL/conexion.php";
require_once "DAL/functions.php";
include_once "include/headerCompras.php";

// Carrito REAL que estamos usando
$carrito = $_SESSION['carritoCompras'] ?? [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de compras</title>

    <!-- ✅ TUS ESTILOS ORIGINALES -->
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="preload" href="css/style.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/compras.css">

    <!-- Bootstrap + MDB como en Compras.php -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<main class="container mt-4">
    <h2 class="mb-3">Carrito de compras</h2>
    <hr>

    <?php if (empty($carrito)): ?>
        <div class="alert alert-info">El carrito está vacío.</div>

    <?php else: ?>

        <table class="table align-middle table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="width:50%">Producto</th>
                    <th style="width:15%">Precio</th>
                    <th style="width:10%">Cantidad</th>
                    <th style="width:15%">Subtotal</th>
                    <th style="width:10%">Acción</th>
                </tr>
            </thead>
            <tbody>

            <?php 
            $total = 0;
            foreach ($carrito as $id => $p):

                // ✅ Arreglo rápido: si no existe cantidad, usar 1
                $cantidad = $p["cantidad"] ?? 1;

                $subtotal = $p["PRECIO"] * $cantidad;
                $total += $subtotal;
            ?>
                <tr>
                    <td>
                        <div class="d-flex gap-3 align-items-center">
                            <img src="<?= $p['IMAGEN'] ?>" style="width:90px; border-radius:8px;">
                            <div>
                                <strong class="d-block"><?= $p['NOMBRE'] ?></strong>
                                <small><?= $p['DETALLE'] ?></small>
                            </div>
                        </div>
                    </td>

                    <td>₡<?= number_format($p['PRECIO'], 2) ?></td>
                    <td><?= $cantidad ?></td>
                    <td>₡<?= number_format($subtotal, 2) ?></td>

                    <!-- ✅ ELIMINAR POR GET -->
                    <td class="text-center">
                        <a href="eliminarProducto.php?id=<?= $id ?>" 
                           class="btn btn-outline-danger btn-sm">
                           <i class="fa-regular fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

        <h3 class="text-end mt-3">Total: ₡<?= number_format($total, 2) ?></h3>

        <!-- ✅ Botones como tu arte (clases de compras.css / style.css) -->
        <div class="mt-4 d-flex flex-column gap-2">
            <a href="Compras.php" class="btn btn_comprar w-100">SEGUIR COMPRANDO</a>

            <!-- este sí procesa -->
            <form action="procesarCompra.php" method="post">
                <input type="hidden" name="nombreUsuario" value="<?= $_SESSION['usuario'] ?? '' ?>">
                <input type="hidden" name="totalCompra" value="<?= $total ?>">
                <button type="submit" class="btn btn-dark w-100">PROCESAR COMPRA</button>
            </form>
        </div>

    <?php endif; ?>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/4b2f294736.js" crossorigin="anonymous"></script>

</body>
</html>
