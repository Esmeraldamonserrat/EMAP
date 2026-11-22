<?php
// en esta pagina registramos un libro nuevo
// la portada y el pdf se guardan en la carpeta guarda_archivos
// y en la base de datos solo guardamos el nombre de cada archivo

// revisamos que el usuario ya haya iniciado sesion
require "config/auth.php";
// incluimos la conexion para poder guardar el libro
require "config/conexion.php";

// dejamos preparadas las variables vacias porque todavia no recibimos informacion
// asi si hay un error el formulario conserva lo que el usuario ya habia escrito
$error  = "";
$titulo = "";
$autor  = "";
$genero = "";
$anio   = "";
// el estado empieza como pendiente
$estado = "Pendiente";

// revisamos si el formulario fue enviado por post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // guardamos los datos del libro y quitamos los espacios de los lados con trim
    $titulo = trim($_POST["titulo"] ?? "");
    $autor  = trim($_POST["autor"] ?? "");
    $genero = trim($_POST["genero"] ?? "");
    $anio   = trim($_POST["anio"] ?? "");
    $estado = $_POST["estado"] ?? "";

    // la variable files tiene la informacion de los archivos que subio el usuario
    // error nos dice si el archivo se subio bien
    // upload_err_ok quiere decir que se subio bien
    // upload_err_no_file quiere decir que no eligio ningun archivo
    $error_imagen = $_FILES["imagen"]["error"] ?? UPLOAD_ERR_NO_FILE;
    $error_pdf    = $_FILES["archivo_pdf"]["error"] ?? UPLOAD_ERR_NO_FILE;

    // sacamos la extension de cada archivo por ejemplo jpg, png o pdf
    // pathinfo con pathinfo_extension nos da solo la extension del nombre del archivo
    // strtolower la pasa a minusculas para que una extension en mayusculas y otra en minusculas cuenten igual
    $ext_imagen = strtolower(pathinfo($_FILES["imagen"]["name"] ?? "", PATHINFO_EXTENSION));
    $ext_pdf    = strtolower(pathinfo($_FILES["archivo_pdf"]["name"] ?? "", PATHINFO_EXTENSION));

    // validamos los datos aqui en el servidor
    if ($titulo == "" || $autor == "" || $genero == "" || $anio == "") {
        $error = "Todos los campos son obligatorios.";
    } elseif (mb_strlen($titulo) > 200 || mb_strlen($autor) > 150 || mb_strlen($genero) > 100) {
        // mb_strlen cuenta cuantas letras tiene el texto
        $error = "El título, autor o género es demasiado largo.";
    } elseif (!ctype_digit($anio) || $anio < 1 || $anio > date("Y")) {
        // ctype_digit revisa que el anio tenga solamente numeros y que no sea mayor al anio actual
        $error = "El año debe ser un número entre 1 y " . date("Y") . ".";
    } elseif (!in_array($estado, ["Pendiente", "Leyendo", "Terminado"])) {
        // in_array revisa que el estado sea uno de los tres que permitimos
        $error = "Selecciona un estado válido.";
    } elseif ($error_imagen != UPLOAD_ERR_OK && $error_imagen != UPLOAD_ERR_NO_FILE) {
        // si eligio una imagen pero no se pudo subir mostramos el error
        $error = "No se pudo subir la imagen (máximo 2 MB).";
    } elseif ($error_imagen == UPLOAD_ERR_OK && !in_array($ext_imagen, ["jpg", "jpeg", "png", "gif", "webp"])) {
        // solo aceptamos imagenes para que no suban otro tipo de archivo
        $error = "La portada debe ser una imagen JPG, PNG, GIF o WEBP.";
    } elseif ($error_pdf != UPLOAD_ERR_OK && $error_pdf != UPLOAD_ERR_NO_FILE) {
        $error = "No se pudo subir el PDF (máximo 2 MB).";
    } elseif ($error_pdf == UPLOAD_ERR_OK && $ext_pdf != "pdf") {
        // solo aceptamos archivos pdf
        $error = "El libro digital debe ser un archivo PDF.";
    } else {
        // si todo esta bien guardamos los archivos
        // si no eligio imagen el nombre se queda vacio
        $imagen = "";
        if ($error_imagen == UPLOAD_ERR_OK) {
            // uniqid nos da un nombre unico para que dos archivos no se llamen igual
            // con el punto unimos el nombre con la extension original
            $imagen = uniqid("portada_") . "." . $ext_imagen;
            // move_uploaded_file mueve el archivo de su lugar temporal a nuestra carpeta
            move_uploaded_file($_FILES["imagen"]["tmp_name"], "guarda_archivos/" . $imagen);
        }

        // hacemos lo mismo con el pdf
        $archivo_pdf = "";
        if ($error_pdf == UPLOAD_ERR_OK) {
            $archivo_pdf = uniqid("libro_") . ".pdf";
            move_uploaded_file($_FILES["archivo_pdf"]["tmp_name"], "guarda_archivos/" . $archivo_pdf);
        }

        // guardamos el libro en la base de datos
        // los datos van con dos puntos como en titulo y despues les damos su valor en execute
        // asi los datos del usuario nunca se pegan directo en el sql
        $sql  = "INSERT INTO libros (titulo, autor, genero, anio, estado, imagen, archivo_pdf, id_usuario)
                 VALUES (:titulo, :autor, :genero, :anio, :estado, :imagen, :archivo_pdf, :id_usuario)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "titulo"      => $titulo,
            "autor"       => $autor,
            "genero"      => $genero,
            "anio"        => $anio,
            "estado"      => $estado,
            "imagen"      => $imagen,
            "archivo_pdf" => $archivo_pdf,
            // el duenio del libro es el usuario que inicio sesion
            "id_usuario"  => $_SESSION["id_usuario"],
        ]);

        // guardamos un mensaje y regresamos a la lista de libros
        $_SESSION["mensaje"] = "Libro agregado.";
        header("Location: libros.php");
        exit;
    }
}

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Agregar libro</h1>

                <!-- si hay un error lo mostramos en una alerta roja de bootstrap -->
                <?php if ($error != ""): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- enctype multipart form-data es necesario para poder enviar archivos -->
                <form action="libro_crear.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" maxlength="200" required
                               value="<?= htmlspecialchars($titulo) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="autor" class="form-label">Autor</label>
                        <input type="text" name="autor" id="autor" class="form-control" maxlength="150" required
                               value="<?= htmlspecialchars($autor) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="genero" class="form-label">Género</label>
                        <input type="text" name="genero" id="genero" class="form-control" maxlength="100" required
                               value="<?= htmlspecialchars($genero) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="anio" class="form-label">Año de publicación</label>
                        <input type="number" name="anio" id="anio" class="form-control" min="1" max="<?= date("Y") ?>" required
                               value="<?= htmlspecialchars($anio) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-select">
                            <!-- foreach recorre la lista de estados y crea una opcion por cada uno -->
                            <!-- si la opcion es igual al estado elegido le ponemos selected -->
                            <?php foreach (["Pendiente", "Leyendo", "Terminado"] as $opcion): ?>
                                <option value="<?= $opcion ?>" <?php if ($opcion == $estado) echo "selected"; ?>>
                                    <?= $opcion ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen de portada (JPG, PNG, GIF o WEBP, máx. 2 MB)</label>
                        <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-4">
                        <label for="archivo_pdf" class="form-label">Libro digital (PDF, máx. 2 MB)</label>
                        <input type="file" name="archivo_pdf" id="archivo_pdf" class="form-control" accept=".pdf">
                    </div>

                    <button type="submit" class="btn btn-lila">Guardar</button>
                    <a href="libros.php" class="btn btn-light">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
