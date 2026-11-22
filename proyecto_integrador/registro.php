<?php
// en esta pagina cualquier persona puede crear su cuenta
// todas las cuentas nuevas se crean con el rol usuario

// iniciamos la sesion para poder guardar el mensaje de cuenta creada
session_start();
// incluimos la conexion para poder guardar al usuario en la base de datos
require "config/conexion.php";

// dejamos preparadas estas variables para conservar lo que escriba el usuario
// asi si hay un error no tiene que volver a escribir todo
$error    = "";
$nombre   = "";
$correo   = "";
$telefono = "";

// revisamos si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // guardamos los datos que escribio la persona
    // trim quita los espacios que pueda tener al inicio o al final
    $nombre    = trim($_POST["nombre"] ?? "");
    $correo    = trim($_POST["correo"] ?? "");
    $telefono  = trim($_POST["telefono"] ?? "");
    // guardamos las dos contrasenas para despues comparar que sean iguales
    $password  = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";

    // validamos los datos aqui en php porque las validaciones del navegador se pueden saltar
    if ($nombre == "" || $correo == "" || $telefono == "" || $password == "") {
        $error = "Todos los campos son obligatorios.";
    } elseif (mb_strlen($nombre) > 100) {
        // mb_strlen cuenta las letras del texto contando bien los acentos
        $error = "El nombre es demasiado largo (máximo 100 caracteres).";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
        // filter_var revisa que el correo tenga un formato valido como nombre arroba correo punto com
        $error = "El correo electrónico no es válido.";
    } elseif (strlen($telefono) != 10 || !ctype_digit($telefono)) {
        // revisamos que el telefono tenga 10 caracteres y que todos sean numeros
        $error = "El teléfono debe tener 10 dígitos.";
    } elseif (strlen($password) < 8) {
        // revisamos que la contrasena tenga al menos 8 caracteres
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password != $password2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // antes de registrar al usuario revisamos que su correo no exista ya
        // usamos una consulta preparada para hacer la busqueda de forma segura
        $sql  = "SELECT id_usuario FROM usuarios WHERE correo = :correo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["correo" => $correo]);

        // si encontramos a alguien con ese correo no dejamos crear otra cuenta
        if ($stmt->fetch()) {
            $error = "Ese correo ya está registrado.";
        } else {
            // si el correo esta libre protegemos la contrasena antes de guardarla
            // password_hash la convierte en un hash para no guardarla como texto normal
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // guardamos al nuevo usuario en la base de datos
            // el rol lo escribimos directo como usuario para que nadie pueda registrarse como administrador
            $sql  = "INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol)
                     VALUES (:nombre, :correo, :telefono, :password_hash, 'usuario')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "nombre"        => $nombre,
                "correo"        => $correo,
                "telefono"      => $telefono,
                "password_hash" => $password_hash,
            ]);

            // guardamos un mensaje y lo mandamos al login para que entre con su cuenta
            $_SESSION["mensaje"] = "¡Cuenta creada! Ya puedes iniciar sesión.";
            header("Location: login.php");
            exit;
        }
    }
}

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Crear cuenta</h1>

                <!-- si encontramos algun error durante el registro lo mostramos -->
                <?php if ($error != ""): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- los datos del formulario se envian a este mismo archivo por post -->
                <form action="registro.php" method="post">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" maxlength="100" required
                               value="<?= htmlspecialchars($nombre) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" id="correo" class="form-control" maxlength="150" required
                               value="<?= htmlspecialchars($correo) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono (10 dígitos)</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control" maxlength="10" required
                               value="<?= htmlspecialchars($telefono) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña (mínimo 8 caracteres)</label>
                        <input type="password" name="password" id="password" class="form-control" minlength="8" required>
                    </div>

                    <div class="mb-4">
                        <label for="password2" class="form-label">Confirmar contraseña</label>
                        <input type="password" name="password2" id="password2" class="form-control" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-lila w-100">Registrarme</button>
                </form>

                <p class="text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            </div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
