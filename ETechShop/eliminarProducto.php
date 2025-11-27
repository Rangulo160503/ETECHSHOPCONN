<?php
session_start();

// Soporta GET o POST
$id = 0;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}

if ($id <= 0) {
    echo "Error: No se proporcionó un ID de producto";
    exit;
}

// Si existe en el carrito, se elimina
if (isset($_SESSION['carritoCompras'][$id])) {
    unset($_SESSION['carritoCompras'][$id]);
}

// Volvemos al carrito sin perder el estilo ni romper el flujo
header("Location: carritoCompras.php");
exit;
