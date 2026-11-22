<?php
// este archivo protege las paginas internas del sistema
// lo incluimos al inicio de cada pagina que solo puede ver alguien que ya inicio sesion

// iniciamos la sesion para poder leer los datos del usuario
session_start();

// revisamos si el usuario ya inicio sesion
// si no existe su id en la sesion quiere decir que es un usuario anonimo
// entonces lo mandamos al login y detenemos la pagina con exit
if (!isset($_SESSION["id_usuario"])) {
    $_SESSION["mensaje"] = "Debes iniciar sesión para entrar.";
    header("Location: login.php");
    exit;
}
