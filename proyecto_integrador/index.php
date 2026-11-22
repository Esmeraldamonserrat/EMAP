<?php
// esta es la pagina de entrada del proyecto
// si la persona ya inicio sesion la mandamos al dashboard y si no al login

// iniciamos la sesion para poder revisar si ya hay un usuario guardado
session_start();

// revisamos si el usuario ya inicio sesion
if (isset($_SESSION["id_usuario"])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
