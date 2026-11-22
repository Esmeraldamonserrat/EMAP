<?php
// este archivo tiene la parte de arriba de todas las paginas
// usamos el grid de bootstrap para dividir la pantalla en dos columnas
// la columna izquierda es el menu lateral y la columna derecha es el contenido
// en celular las dos columnas ocupan todo el ancho y el menu queda arriba
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- esta linea hace que la pagina se adapte a la pantalla del celular -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Biblioteca Digital</title>

    <!-- cargamos bootstrap 5 desde internet -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- cargamos nuestra hoja de estilos personalizada -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<!-- container-fluid hace que el contenido ocupe todo el ancho disponible -->
<div class="container-fluid">
    <!-- row permite organizar el contenido en filas y columnas -->
    <div class="row">

        <!-- creamos la columna izquierda donde estara el menu lateral -->
        <!-- en celular ocupa todo el ancho y en computadora ocupa menos espacio -->
        <aside class="col-12 col-md-3 col-lg-2 menu-lateral shadow-sm p-3">
            <!-- sticky-top hace que el menu se quede fijo al bajar la pagina -->
            <div class="sticky-top pt-2">
                <!-- este enlace lleva a la pagina principal al hacer clic en el nombre -->
                <a href="index.php" class="d-block fw-bold fs-5 mb-4 text-decoration-none titulo-menu">
                     Mi Biblioteca
                </a>

                <!-- revisamos si el usuario ya inicio sesion -->
                <?php if (isset($_SESSION["id_usuario"])): ?>
                    <!-- si ya inicio sesion mostramos su nombre y su rol -->
                    <p class="small mb-3">
                        Hola, <strong><?= htmlspecialchars($_SESSION["nombre"]) ?></strong><br>
                        (<?= htmlspecialchars($_SESSION["rol"]) ?>)
                    </p>

                    <ul class="nav flex-column mb-4">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="libros.php"> Libros</a></li>
                        <li class="nav-item"><a class="nav-link" href="libro_crear.php"> Agregar libro</a></li>
                    </ul>

                    <a href="logout.php" class="btn btn-rosa btn-sm w-100">Cerrar sesión</a>
                <?php else: ?>
                    <!-- si nadie ha iniciado sesion solo mostramos estas dos opciones -->
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="login.php"> Iniciar sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="registro.php"> Registrarse</a></li>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

        <!-- creamos la columna derecha donde se mostrara el contenido de cada pagina -->
        <!-- en celular ocupa todo el ancho y en computadora deja espacio al menu -->
        <main class="col-12 col-md-9 col-lg-10 p-4">

<!-- revisamos si hay un mensaje guardado en la sesion por ejemplo libro agregado -->
<?php if (isset($_SESSION["mensaje"])): ?>
    <!-- mostramos el mensaje dentro de una alerta de bootstrap -->
    <div class="alert alert-info border-0 rounded-4"><?= htmlspecialchars($_SESSION["mensaje"]) ?></div>
    <!-- borramos el mensaje para que solo se vea una vez -->
    <?php unset($_SESSION["mensaje"]); ?>
<?php endif; ?>
