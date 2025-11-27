<?php

require_once "conexion.php";

function getArray($sql)
{
    $retorno = array();

    try {
        $oConexion = Conecta();

        $stmt = oci_parse($oConexion, $sql);
        if (!$stmt) {
            $e = oci_error($oConexion);
            throw new Exception($e['message']);
        }

        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $retorno[] = $row;
            }
        }

        oci_free_statement($stmt);
    } catch (Throwable $th) {
        echo $th->getMessage();
    } finally {
        Desconecta($oConexion);
    }

    return $retorno;
}

function getObject($sql)
{
    $retorno = null;

    try {
        $oConexion = Conecta();

        $stmt = oci_parse($oConexion, $sql);
        if (!$stmt) {
            $e = oci_error($oConexion);
            throw new Exception($e['message']);
        }

        if (oci_execute($stmt)) {
            $retorno = oci_fetch_assoc($stmt);
        }

        oci_free_statement($stmt);
    } catch (Throwable $th) {
        echo $th->getMessage();
    } finally {
        Desconecta($oConexion);
    }

    return $retorno;
}

function DefinirContrasena($pCorreo, $pContrasena)
{
    $retorno = false;

    try {
        $oConexion = Conecta();

        $sql = "UPDATE alumno SET password = :pContrasena WHERE correo = :pCorreo";
        $stmt = oci_parse($oConexion, $sql);

        oci_bind_by_name($stmt, ":pCorreo", $pCorreo);
        oci_bind_by_name($stmt, ":pContrasena", $pContrasena);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            $retorno = true;
        }

        oci_free_statement($stmt);
    } catch (Throwable $th) {
        echo $th->getMessage();
    } finally {
        Desconecta($oConexion);
    }

    return $retorno;
}

function getAvailableProducts()
{
    $productos = array();

    try {
        $conexion = Conecta();
        $sql = "SELECT codigo, nombre, detalle, imagen, precio FROM productos";
        $stmt = oci_parse($conexion, $sql);

        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $productos[] = $row;
                if (is_object($row['DETALLE']) && $row['DETALLE'] instanceof OCI_Lob) {
                    $row['DETALLE'] = $row['DETALLE']->load();
                }
            }
        }

        oci_free_statement($stmt);
    } catch (Throwable $th) {
        echo $th->getMessage();
    } finally {
        Desconecta($conexion);
    }

    return $productos;
}

function getProductById($idProducto)
{
    $cn = Conecta();
    if (!$cn) return null;

    $sql = "SELECT CODIGO, NOMBRE, DETALLE, IMAGEN, PRECIO
            FROM PRODUCTOS
            WHERE CODIGO = :id";

    $stid = oci_parse($cn, $sql);
    oci_bind_by_name($stid, ":id", $idProducto);

    if (!oci_execute($stid)) {
        oci_free_statement($stid);
        Desconecta($cn);
        return null;
    }

    $producto = oci_fetch_assoc($stid);

    // Si el campo DETALLE es un CLOB, cargarlo
    if ($producto && is_object($producto['DETALLE']) && $producto['DETALLE'] instanceof OCI_Lob) {
        $producto['DETALLE'] = $producto['DETALLE']->load();
    }

    oci_free_statement($stid);
    Desconecta($cn);

    return $producto ?: null;
}


function obtenerInformacionUsuario($correo)
{
    $usuario = null;
    $conexion = null;

    try {
        $conexion = Conecta();

        $sql = "SELECT correo, contrasena, intentos, bloqueado
                FROM usuarios
                WHERE correo = UPPER(:correo)";

        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ":correo", $correo);

        if (oci_execute($stmt)) {
            $usuario = oci_fetch_assoc($stmt);
        } else {
            $e = oci_error($stmt);
            // opcional: loguear error real
            // echo htmlentities($e["message"], ENT_QUOTES);
        }

        oci_free_statement($stmt);

    } catch (Throwable $th) {
        echo $th->getMessage();
    } finally {
        if ($conexion) Desconecta($conexion);
    }

    return $usuario; // si no existe, vuelve null
}


function actualizarContrasena($correo, $nuevaContrasena)
{
    $resultado = false;

    try {
        $conexion = Conecta();
        $hashNuevaContrasena = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios SET contrasena = :hashNuevaContrasena WHERE correo = :correo";
        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ":hashNuevaContrasena", $hashNuevaContrasena);
        oci_bind_by_name($stmt, ":correo", $correo);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            $resultado = true;
        }

        oci_free_statement($stmt);
    } catch (Throwable $th) {
        echo $th->getMessage();
    } finally {
        Desconecta($conexion);
    }

    return $resultado;
}
