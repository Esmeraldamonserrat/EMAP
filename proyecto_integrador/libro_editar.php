<?php
// en esta pagina modificamos los datos de un libro
// el administrador puede editar cualquier libro
// el usuario normal solo puede editar sus propios libros

// revisamos que el usuario ya haya iniciado sesion
require "config/auth.php";
// incluimos la conexion para poder buscar y actualizar el libro
require "config/conexion.php";

// recibimos el id del libro que viene en la direccion por ejemplo cuando dice id igual a 14
// si no viene ningun id usamos 0
// int convierte el valor a numero entero para que no puedan mandar texto raro
$id_libro = (int) ($_GET["id"] ?? 0);
$error = "";

// hacemos la consulta para buscar el libro respetando los permisos
// activo = 1 impide editar un libro eliminado aunque escriban su id en la direccion
if ($_SESSION["rol"] == "admin") {
    // el administrador puede buscar cualquier libro
    $sql  = "SELECT * FROM libros WHERE id_libro = :id_libro AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["id_libro" => $id_libro]);
} else {
    // el usuario normal solo encuentra el libro si tiene su id, es decir si es suyo
    $sql  = "SELECT * FROM libros WHERE id_libro = :id_libro AND id_usuario = :id_usuario AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["id_libro" => $id_libro, "id_usuario" => $_SESSION["id_usuario"]]);
}

// fetch nos da el libro encontrado o false si no lo encontro
$libro = $stmt->fetch();

// si el libro no existe o no es suyo lo regresamos a la lista
// el exit detiene la pagina para que no pueda seguir y modificarlo
if (!$libro) {
    $_SESSION["mensaje"] = "Ese libro no existe o no tienes permiso para editarlo.";
    header("Location: libros.php");
    exit;
}

// revisamos si el formulario fue enviado para validar y guardar los cambios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // guardamos los datos nuevos que escribio el usuario
    $titulo = trim($_POST["titulo"] ?? "");
    $autor  = trim($_POST["autor"] ?? "");
    $genero = trim($_POST["genero"] ?? "");
    $anio   = trim($_POST["anio"] ?? "");
    $estado = $_POST["estado"] ?? "";

    // revisamos los archivos igual que en libro_crear.php
    // error nos dice si se subio bien y pathinfo nos da la extension
    $error_imagen = $_FILES["imagen"]["error"] ?? UPLOAD_ERR_NO_FILE;
    $error_pdf    = $_FILES["archivo_pdf"]["error"] ?? UPLOAD_ERR_NO_FILE;
    $ext_imagen   = strtolower(pathinfo($_FILES["imagen"]["name"] ?? "", PATHINFO_EXTENSION));
    $ext_pdf      = strtolower(pathinfo($_FILES["archivo_pdf"]["name"] ?? "", PATHINFO_EXTENSION));

    // hacemos las mismas validaciones que cuando se crea un libro
    if ($titulo == "" || $autor == "" || $genero == "" || $anio == "") {
        $error = "Todos los campos son obligatorios.";
    } elseif (mb_strlen($titulo) > 200 || mb_strlen($autor) > 150 || mb_strlen($genero) > 100) {
        $error = "El título, autor o género es demasiado largo.";
    } elseif (!ctype_digit($anio) || $anio < 1 || $anio > date("Y")) {
        $error = "El año debe ser un número entre 1 y " . date("Y") . ".";
    } elseif (!in_array($estado, ["Pendiente", "Leyendo", "Terminado"])) {
        $error = "Selecciona un estado válido.";
    } elseif ($error_imagen != UPLOAD_ERR_OK && $error_imagen != UPLOAD_ERR_NO_FILE) {
        $error = "No se pudo subir la imagen (máximo 2 MB).";
    } elseif ($error_imagen == UPLOAD_ERR_OK && !in_array($ext_imagen, ["jpg", "jpeg", "png", "gif", "webp"])) {
        $error = "La portada debe ser una imagen JPG, PNG, GIF o WEBP.";
    } elseif ($error_pdf != UPLOAD_ERR_OK && $error_pdf != UPLOAD_ERR_NO_FILE) {
        $error = "No se pudo subir el PDF (máximo 2 MB).";
    } elseif ($error_pdf == UPLOAD_ERR_OK && $ext_pdf != "pdf") {
        $error = "El libro digital debe ser un archivo PDF.";
    } else {
        // primero conservamos la portada que ya tenia el libro
        $imagen = $libro["imagen"];
        // si eligio una portada nueva borramos la anterior y guardamos la nueva
        if ($error_imagen == UPLOAD_ERR_OK) {
            // file_exists revisa que el archivo exista y unlink lo borra
            if ($libro["imagen"] != "" && file_exists("guarda_archivos/" . $libro["imagen"])) {
                unlink("guarda_archivos/" . $libro["imagen"]);
            }
            $imagen = uniqid("portada_") . "." . $ext_imagen;
            move_uploaded_file($_FILES["imagen"]["tmp_name"], "guarda_archivos/" . $imagen);
        }

        // hacemos lo mismo con el pdf, primero conservamos el que ya tenia
        $archivo_pdf = $libro["archivo_pdf"];
        // si eligio otro pdf borramos el anterior y guardamos el nuevo
        if ($error_pdf == UPLOAD_ERR_OK) {
            if ($libro["archivo_pdf"] != "" && file_exists("guarda_archivos/" . $libro["archivo_pdf"])) {
                unlink("guarda_archivos/" . $libro["archivo_pdf"]);
            }
            $archivo_pdf = uniqid("libro_") . ".pdf";
            move_uploaded_file($_FILES["archivo_pdf"]["tmp_name"], "guarda_archivos/" . $archivo_pdf);
        }

        // actualizamos el libro en la base de datos
        // update dice que tabla vamos a modificar, set los campos que cambian y where cual libro
        // el where es muy importante porque sin el se cambiarian todos los libros
        // aqui ya no revisamos el rol porque arriba ya comprobamos que este libro si se puede editar
        $sql = "UPDATE libros SET titulo = :titulo, autor = :autor, genero = :genero, anio = :anio,
                estado = :estado, imagen = :imagen, archivo_pdf = :archivo_pdf
                WHERE id_libro = :id_libro";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "titulo"      => $titulo,
            "autor"       => $autor,
            "genero"      => $genero,
            "anio"        => $anio,
            "estado"      => $estado,
            "imagen"      => $imagen,
            "archivo_pdf" => $archivo_pdf,
            "id_libro"    => $id_libro,
        ]);

        // guardamos un mensaje y regresamos a la lista de libros
        $_SESSION["mensaje"] = "Libro actualizado.";
        header("Location: libros.php");
        exit;
    }

    // si hubo un error mostramos en el formulario lo que el usuario escribio
    // asi no pierde los cambios que ya habia hecho
    $libro["titulo"] = $titulo;
    $libro["autor"]  = $autor;
    $libro["genero"] = $genero;
    $libro["anio"]   = $anio;
    $libro["estado"] = $estado;
}

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Editar libro</h1>

                <!-- si hay un error lo mostramos en una alerta roja -->
                <?php if ($error != ""): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- mandamos el id en la direccion para saber que libro estamos editando -->
                <!-- enctype multipart form-data es necesario para poder enviar archivos -->
                <form action="libro_editar.php?id=<?= htmlspecialchars($id_libro) ?>" method="post" enctype="multipart/form-data">
                    <!-- en cada campo ponemos el dato que ya tenia el libro para poder cambiarlo -->
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" maxlength="200" required
                               value="<?= htmlspecialchars($libro["titulo"]) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="autor" class="form-label">Autor</label>
                        <input type="text" name="autor" id="autor" class="form-control" maxlength="150" required
                               value="<?= htmlspecialchars($libro["autor"]) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="genero" class="form-label">Género</label>
                        <input type="text" name="genero" id="genero" class="form-control" maxlength="100" required
                               value="<?= htmlspecialchars($libro["genero"]) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="anio" class="form-label">Año de publicación</label>
                        <input type="number" name="anio" id="anio" class="form-control" min="1" max="<?= date("Y") ?>" required
                               value="<?= htmlspecialchars($libro["anio"]) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-select">
                            <!-- creamos una opcion por cada estado y marcamos la que ya tenia el libro -->
                            <?php foreach (["Pendiente", "Leyendo", "Terminado"] as $opcion): ?>
                                <option value="<?= $opcion ?>" <?php if ($opcion == $libro["estado"]) echo "selected"; ?>>
                                    <?= $opcion ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen de portada (JPG, PNG, GIF o WEBP, máx. 2 MB)</label>
                        <!-- si el libro ya tiene portada la mostramos pequena -->
                        <?php if ($libro["imagen"] != ""): ?>
                            <div class="mb-2">
                                <img src="guarda_archivos/<?= htmlspecialchars($libro["imagen"]) ?>" alt="Portada actual"
                                     class="rounded-3 shadow-sm" style="height: 120px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                        <div class="form-text">Déjalo vacío para conservar la portada actual.</div>
                    </div>

                    <div class="mb-4">
                        <label for="archivo_pdf" class="form-label">Libro digital (PDF, máx. 2 MB)</label>
                        <!-- si el libro ya tiene pdf mostramos un enlace para abrirlo en otra pestana -->
                        <?php if ($libro["archivo_pdf"] != ""): ?>
                            <div class="mb-2">
                                <a href="guarda_archivos/<?= htmlspecialchars($libro["archivo_pdf"]) ?>" target="_blank">Ver PDF actual</a>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="archivo_pdf" id="archivo_pdf" class="form-control" accept=".pdf">
                        <div class="form-text">Déjalo vacío para conservar el PDF actual.</div>
                    </div>

                    <button type="submit" class="btn btn-lila">Guardar cambios</button>
                    <a href="libros.php" class="btn btn-light">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
