<?php
// este archivo cierra la sesion del usuario y lo regresa al login

// iniciamos la sesion para poder borrarla
session_start();

// session_unset borra todas las variables que guardamos en la sesion
// por ejemplo el id, el nombre y el rol del usuario
session_unset();

// session_destroy destruye por completo la sesion en el servidor
session_destroy();

// regresamos al login
header("Location: login.php");
exit;
