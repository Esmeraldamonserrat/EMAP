<?php
// en esta pagina la persona inicia sesion con su correo y su contrasena

// iniciamos la sesion para poder guardar los datos del usuario
session_start();
// incluimos la conexion para poder buscar al usuario en la base de datos
require "config/conexion.php";

// si ya inicio sesion no tiene caso mostrarle el login otra vez
if (isset($_SESSION["id_usuario"])) {
    header("Location: dashboard.php");
    exit;
}

// preparamos una variable para guardar el mensaje de error
// y otra para conservar el correo si se equivoca y no tenga que escribirlo de nuevo
$error  = "";
$correo = "";

// revisamos si la persona ya envio el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // guardamos el correo que escribio la persona y le quitamos los espacios de los lados con trim
    // el doble signo de interrogacion sirve para que si el dato no llego se quede como texto vacio
    $correo   = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";

    // revisamos que los campos no esten vacios
    if ($correo == "" || $password == "") {
        $error = "Escribe tu correo y tu contraseña.";
    } else {
        // hacemos la consulta para buscar al usuario que tenga ese correo
        // usamos una consulta preparada con los dos puntos antes de correo para que nadie pueda meter codigo sql
        $sql  = "SELECT * FROM usuarios WHERE correo = :correo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["correo" => $correo]);
        // fetch nos da el usuario encontrado o false si no existe
        $usuario = $stmt->fetch();

        // revisamos que el usuario exista y que su contrasena sea correcta
        // password_verify compara la contrasena que escribio con el hash que guardamos
        if ($usuario && password_verify($password, $usuario["password_hash"])) {
            // le damos un nuevo id a la sesion al entrar para que sea mas seguro
            session_regenerate_id(true);

            // guardamos en la sesion el id, el nombre y el rol
            // estos datos nos sirven para saber quien es en las demas paginas
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"]     = $usuario["nombre"];
            $_SESSION["rol"]        = $usuario["rol"];

            header("Location: dashboard.php");
            exit;
        } else {
            // si el correo o la contrasena no coinciden mostramos este mensaje
            $error = "Correo o contraseña incorrectos";
        }
    }
}

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>
<!-- formulario para iniciar sesion -->
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Iniciar sesión</h1>

                <!-- si hubo algun error lo mostramos en pantalla -->
                <?php if ($error != ""): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- los datos del formulario se envian a este mismo archivo por post -->
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" id="correo" class="form-control" required
                               value="<?= htmlspecialchars($correo) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-lila w-100">Entrar</button>
                </form>

                <!-- enlace para registrarse y enlace para recuperar la contrasena -->
                <p class="text-center mt-3 mb-1">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
                <p class="text-center mb-0"><a href="olvide.php">¿Olvidaste tu contraseña?</a></p>
            </div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
