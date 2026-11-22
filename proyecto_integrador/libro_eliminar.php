<?php
// este archivo elimina un libro deshabilitandolo
// no lo borramos de la base de datos, solo cambiamos activo a 0
// asi ya no aparece en la lista ni en el dashboard
// solo el administrador puede hacerlo y solo por post

// revisamos que el usuario ya haya iniciado sesion
require "config/auth.php";
// incluimos la conexion para poder modificar el libro
require "config/conexion.php";

// revisamos el rol aqui en el servidor
// aunque el boton no se vea, alguien podria intentar abrir este archivo directo
if ($_SESSION["rol"] != "admin") {
    $_SESSION["mensaje"] = "No tienes permiso para eliminar libros.";
    header("Location: libros.php");
    exit;
}

// revisamos como llego la solicitud y solo aceptamos post
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: libros.php");
    exit;
}

// guardamos el id del libro que queremos eliminar y lo convertimos a numero entero
$id_libro = (int) ($_POST["id_libro"] ?? 0);

// en lugar de delete usamos update para que el libro se quede guardado pero con activo = 0
// el where es importante porque sin el se deshabilitarian todos los libros
$sql  = "UPDATE libros SET activo = 0 WHERE id_libro = :id_libro AND activo = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(["id_libro" => $id_libro]);

// rowcount nos dice cuantos libros cambio el update
// si cambio uno quiere decir que el libro si existia y quedo deshabilitado
// la portada y el pdf no se borran de la carpeta por si despues se quiere recuperar el libro
if ($stmt->rowCount() > 0) {
    $_SESSION["mensaje"] = "Libro eliminado (deshabilitado).";
}

// regresamos a la lista de libros
header("Location: libros.php");
exit;
