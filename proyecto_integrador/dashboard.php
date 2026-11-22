<?php
// este es el panel principal donde mostramos los tres indicadores
// el total de libros, los pendientes y los terminados
// el administrador ve los numeros de todos los libros
// el usuario normal solo ve los numeros de sus propios libros

// revisamos que el usuario ya haya iniciado sesion antes de dejarlo entrar
require "config/auth.php";
// incluimos la conexion para poder contar los libros
require "config/conexion.php";

// revisamos que rol tiene el usuario que inicio sesion
// en todas las consultas ponemos activo = 1 para no contar los libros que el admin elimino
if ($_SESSION["rol"] == "admin") {
    // si es administrador contamos todos los libros de la tabla
    $sql_total      = "SELECT COUNT(*) FROM libros WHERE activo = 1";
    $sql_pendientes = "SELECT COUNT(*) FROM libros WHERE estado = 'Pendiente' AND activo = 1";
    $sql_leyendo    = "SELECT COUNT(*) FROM libros WHERE estado = 'Leyendo' AND activo = 1";
    $sql_terminados = "SELECT COUNT(*) FROM libros WHERE estado = 'Terminado' AND activo = 1";
    // el administrador no necesita filtrar por usuario asi que no mandamos datos
    $parametros     = [];
} else {
    // si es usuario normal solo contamos los libros que tienen su id
    $sql_total      = "SELECT COUNT(*) FROM libros WHERE id_usuario = :id_usuario AND activo = 1";
    $sql_pendientes = "SELECT COUNT(*) FROM libros WHERE estado = 'Pendiente' AND id_usuario = :id_usuario AND activo = 1";
    $sql_leyendo    = "SELECT COUNT(*) FROM libros WHERE estado = 'Leyendo' AND id_usuario = :id_usuario AND activo = 1";
    $sql_terminados = "SELECT COUNT(*) FROM libros WHERE estado = 'Terminado' AND id_usuario = :id_usuario AND activo = 1";
    // aqui mandamos su id para que mysql sepa de quien son los libros que queremos contar
    $parametros     = ["id_usuario" => $_SESSION["id_usuario"]];
}

// ahora ejecutamos cada consulta y guardamos el numero que nos regresa
// prepare prepara la consulta, execute la ejecuta y fetchcolumn nos da el numero que conto
$stmt = $pdo->prepare($sql_total);
$stmt->execute($parametros);
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare($sql_pendientes);
$stmt->execute($parametros);
$pendientes = $stmt->fetchColumn();

$stmt = $pdo->prepare($sql_leyendo);
$stmt->execute($parametros);
$leyendo = $stmt->fetchColumn();

$stmt = $pdo->prepare($sql_terminados);
$stmt->execute($parametros);
$terminados = $stmt->fetchColumn();

// sacamos el porcentaje de cada estado para la grafica con una regla de tres
// multiplicamos por 100 y dividimos entre el total, round quita los decimales
// empezamos en cero por si todavia no hay libros
$porc_pendientes = 0;
$porc_leyendo    = 0;
$porc_terminados = 0;
// solo dividimos si hay libros porque no se puede dividir entre cero
if ($total > 0) {
    $porc_pendientes = round($pendientes * 100 / $total);
    $porc_leyendo    = round($leyendo * 100 / $total);
    $porc_terminados = round($terminados * 100 / $total);
}

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>

<h1 class="h3 mb-1">Dashboard</h1>
<p class="text-secondary mb-4">
    <!-- cambiamos el texto dependiendo del rol -->
    <?php if ($_SESSION["rol"] == "admin"): ?>
        Resumen de los libros de todos los usuarios.
    <?php else: ?>
        Resumen de tus libros.
    <?php endif; ?>
</p>

<!-- mostramos los tres indicadores en tarjetas -->
<!-- en celular quedan una debajo de otra y en pantalla grande en una sola fila -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-secondary">Total de libros</div>
                <div class="numero"><?= htmlspecialchars($total) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-secondary"> Libros pendientes</div>
                <div class="numero"><?= htmlspecialchars($pendientes) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-secondary"> Libros terminados</div>
                <div class="numero"><?= htmlspecialchars($terminados) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- grafica de libros por estado hecha con las barras de progreso de bootstrap -->
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Libros por estado</h2>

        <p class="mb-1">Pendiente: <?= htmlspecialchars($pendientes) ?></p>
        <div class="progress mb-3" style="height: 20px;">
            <!-- usamos el porcentaje que calculamos arriba como ancho de la barra de color -->
            <!-- int convierte el porcentaje a numero entero para que no tenga decimales -->
            <div class="progress-bar estado-pendiente" style="width: <?= (int) $porc_pendientes ?>%"><?= (int) $porc_pendientes ?>%</div>
        </div>

        <p class="mb-1">Leyendo: <?= htmlspecialchars($leyendo) ?></p>
        <div class="progress mb-3" style="height: 20px;">
            <div class="progress-bar estado-leyendo" style="width: <?= (int) $porc_leyendo ?>%"><?= (int) $porc_leyendo ?>%</div>
        </div>

        <p class="mb-1">Terminado: <?= htmlspecialchars($terminados) ?></p>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar estado-terminado" style="width: <?= (int) $porc_terminados ?>%"><?= (int) $porc_terminados ?>%</div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
