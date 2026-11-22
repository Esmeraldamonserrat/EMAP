<?php
// en esta pagina mostramos la lista de libros en tarjetas y el buscador por titulo
// el administrador ve todos los libros y el usuario normal solo ve los suyos

// revisamos que el usuario ya haya iniciado sesion
require "config/auth.php";
// incluimos la conexion para poder consultar los libros
require "config/conexion.php";

// guardamos el texto que la persona escribio en el buscador
// si no escribio nada se queda vacio y entonces se muestran todos los libros
$busqueda = trim($_GET["busqueda"] ?? "");

// hacemos la consulta para buscar los libros
// el signo de porcentaje en el like significa cualquier texto antes o despues
// asi encontramos los libros que tengan la palabra buscada en cualquier parte del titulo
// activo = 1 sirve para no mostrar los libros que el admin elimino
if ($_SESSION["rol"] == "admin") {
    // el administrador ve todos los libros
    // usamos inner join con la tabla usuarios para mostrar tambien el nombre de quien registro cada libro
    $sql = "SELECT libros.*, usuarios.nombre AS duenio
            FROM libros
            INNER JOIN usuarios ON usuarios.id_usuario = libros.id_usuario
            WHERE libros.titulo LIKE :titulo AND libros.activo = 1
            ORDER BY libros.titulo";
    $stmt = $pdo->prepare($sql);
    // mandamos la busqueda como parametro y no pegada en el sql para evitar la inyeccion sql
    $stmt->execute(["titulo" => "%" . $busqueda . "%"]);
} else {
    // el usuario normal solo ve los libros que tienen su id
    $sql = "SELECT * FROM libros
            WHERE titulo LIKE :titulo AND id_usuario = :id_usuario AND activo = 1
            ORDER BY titulo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "titulo"     => "%" . $busqueda . "%",
        "id_usuario" => $_SESSION["id_usuario"],
    ]);
}

// fetchall nos da todos los libros que encontro la consulta y los guardamos en una lista
$libros = $stmt->fetchAll();

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Libros</h1>
    <a href="libro_crear.php" class="btn btn-lila">+ Agregar libro</a>
</div>

<!-- buscador por titulo -->
<!-- usamos get para que la busqueda viaje en la direccion y se envie a este mismo archivo -->
<form action="libros.php" method="get" class="row g-2 mb-4">
    <div class="col">
        <!-- campo donde escribimos el titulo, en value dejamos lo que ya se habia buscado -->
        <input type="text" name="busqueda" class="form-control rounded-pill" placeholder="Buscar por titulo"
               value="<?= htmlspecialchars($busqueda) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-verde rounded-pill">Buscar</button>
        <!-- limpiar solo vuelve a abrir la pagina sin busqueda -->
        <a href="libros.php" class="btn btn-light rounded-pill">Limpiar</a>
    </div>
</form>

<!-- count nos dice cuantos libros hay, si es cero avisamos que no se encontro nada -->
<?php if (count($libros) == 0): ?>
    <p class="text-secondary">No se encontraron libros.</p>
<?php endif; ?>

<!-- tarjetas de los libros, una por fila en celular, dos en tablet y tres o cuatro en pantalla grande -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
    <!-- este ciclo recorre la lista de libros y en cada vuelta la variable libro es un solo libro -->
    <?php foreach ($libros as $libro): ?>
        <div class="col">
            <div class="card h-100 shadow-sm">

                <!-- revisamos si el libro tiene una portada guardada -->
                <?php if ($libro["imagen"] != ""): ?>
                    <!-- si tiene mostramos la imagen que esta en la carpeta guarda_archivos -->
                    <img src="guarda_archivos/<?= htmlspecialchars($libro["imagen"]) ?>" class="card-img-top portada"
                         alt="Portada de <?= htmlspecialchars($libro["titulo"]) ?>">
                <?php else: ?>
                    <!-- si no tiene mostramos un recuadro gris -->
                    <div class="sin-portada">Sin imagen</div>
                <?php endif; ?>

                <!-- mostramos los datos del libro -->
                <!-- usamos htmlspecialchars para que si alguien escribio codigo html no se ejecute -->
                <div class="card-body">
                    <h2 class="h5 card-title"><?= htmlspecialchars($libro["titulo"]) ?></h2>
                    <p class="card-text mb-1"><?= htmlspecialchars($libro["autor"]) ?></p>
                    <!-- aqui mostramos el genero y el anio de publicacion -->
                    <p class="card-text small text-secondary mb-2">
                        <?= htmlspecialchars($libro["genero"]) ?> · <?= htmlspecialchars($libro["anio"]) ?>
                    </p>
                    <!-- mostramos el estado del libro con su color -->
                    <!-- strtolower pasa el estado a minusculas para armar el nombre de la clase por ejemplo estado-leyendo -->
                    <span class="badge rounded-pill estado-<?= htmlspecialchars(strtolower($libro["estado"])) ?>">
                        <?= htmlspecialchars($libro["estado"]) ?>
                    </span>

                    <!-- el nombre del duenio del libro solo lo ve el administrador -->
                    <?php if ($_SESSION["rol"] == "admin"): ?>
                        <p class="small text-secondary mt-2 mb-0">Dueño: <?= htmlspecialchars($libro["duenio"]) ?></p>
                    <?php endif; ?>
                </div>

                <!-- botones de cada libro -->
                <!-- flex-wrap hace que los botones pasen a otra linea si no caben -->
                <div class="card-footer bg-white border-0 d-flex flex-wrap gap-2 pb-3">
                    <!-- si el libro tiene pdf mostramos el boton para leerlo -->
                    <!-- target _blank abre el pdf en otra pestana -->
                    <?php if ($libro["archivo_pdf"] != ""): ?>
                        <a href="guarda_archivos/<?= htmlspecialchars($libro["archivo_pdf"]) ?>" target="_blank"
                           class="btn btn-sm btn-verde">Leer libro</a>
                    <?php endif; ?>

                    <!-- mandamos el id del libro en la direccion para saber cual vamos a editar -->
                    <a href="libro_editar.php?id=<?= htmlspecialchars($libro["id_libro"]) ?>"
                       class="btn btn-sm btn-lila">Editar</a>

                    <!-- solo el administrador ve el boton de eliminar -->
                    <?php if ($_SESSION["rol"] == "admin"): ?>
                        <!-- eliminamos por post  -->
                        <!-- confirm muestra una ventanita para preguntar si de verdad lo quiere eliminar -->
                        <form action="libro_eliminar.php" method="post" onsubmit="return confirm('¿Eliminar este libro?');">
                            <!-- mandamos el id del libro escondido dentro del formulario -->
                            <input type="hidden" name="id_libro" value="<?= htmlspecialchars($libro["id_libro"]) ?>">
                            <button type="submit" class="btn btn-sm btn-rosa">Eliminar</button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require "includes/footer.php"; ?>
