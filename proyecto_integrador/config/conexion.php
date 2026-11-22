<?php
// este archivo sirve para conectar nuestro proyecto con la base de datos
// asi las demas paginas pueden consultar, guardar o modificar informacion
// para usar la conexion solo necesitamos incluir este archivo con require

// guardamos los datos que necesitamos para conectarnos a mysql
$servidor = "127.0.0.1";
$puerto = "3306";
$base_datos = "biblioteca";
$usuario_bd = "root";
// en mi wamp el usuario root tiene contrasena, si no tuviera se dejan las comillas vacias
$clave_bd = "root";

// try intenta hacer la conexion
// si ocurre algun error pasa directo al catch
try {
    // creamos la conexion usando pdo
    // le indicamos el servidor, el puerto, la base de datos y la codificacion utf8mb4 para los acentos
    $pdo = new PDO(
        "mysql:host=$servidor;port=$puerto;dbname=$base_datos;charset=utf8mb4",
        $usuario_bd,
        $clave_bd
    );

    // le pedimos a pdo que si una consulta falla nos avise con un error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // le pedimos que los resultados se guarden como arreglos
    // asi podemos leer cada dato usando el nombre de la columna
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // si no se pudo conectar mostramos un mensaje sencillo y detenemos todo
    // no mostramos el error completo para no ensenar datos internos del servidor
    die("Error: no se pudo conectar a la base de datos.");
}
?>
