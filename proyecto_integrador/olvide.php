<?php
// en esta pagina el usuario pide un enlace para cambiar su contrasena si la olvido
// esta es una funcionalidad extra que vale puntos adicionales

// iniciamos la sesion porque el menu de arriba la necesita
session_start();
// incluimos la conexion para poder buscar al usuario y guardar la solicitud
require "config/conexion.php";

// preparamos las variables que vamos a usar
$error = "";
// esta variable nos dice si el formulario ya fue enviado
$enviado = false;
// aqui guardaremos el enlace para cambiar la contrasena
$enlace = "";
$correo = "";
$telefono = "";

// revisamos si el usuario envio el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // guardamos el correo y el telefono que escribio el usuario
    // trim quita los espacios que pueda tener al principio o al final
    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");

    // revisamos que el correo tenga un formato valido
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Escribe un correo válido.";
    } elseif (strlen($telefono) != 10 || !ctype_digit($telefono)) {
        // revisamos que el telefono tenga 10 numeros igual que en el registro
        $error = "El teléfono debe tener 10 dígitos.";
    } else {
        // buscamos al usuario que tenga ese correo y tambien ese telefono
        // como no mandamos un correo de verdad, pedir el telefono sirve para comprobar
        // que quien pide el cambio es el duenio de la cuenta y no alguien que solo sabe el correo
        // tambien pedimos el rol para saber si es administrador
        $sql = "SELECT id_usuario, rol FROM usuarios WHERE correo = :correo AND telefono = :telefono";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["correo" => $correo, "telefono" => $telefono]);
        $usuario = $stmt->fetch();

        // solo los usuarios normales pueden recuperar su contrasena desde aqui
        // la contrasena del administrador se cambia desde phpmyadmin
        if ($usuario && $usuario["rol"] == "usuario") {

            // creamos un codigo al azar para el enlace, a este codigo le llamamos token
            // random_bytes crea datos al azar y bin2hex los convierte en letras y numeros
            $token = bin2hex(random_bytes(32));

            // guardamos la solicitud en la tabla recuperaciones
            // date_add le suma 30 minutos a la hora actual para que el enlace venza
            $sql = "INSERT INTO recuperaciones (id_usuario, token_hash, expira, usado)
                    VALUES (:id, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                // guardamos el id del usuario que pidio cambiar su contrasena
                "id" => $usuario["id_usuario"],
                // no guardamos el token tal cual, guardamos su hash con sha256
                // asi si alguien ve la base de datos no puede usar el enlace
                "token" => hash("sha256", $token)
            ]);

            // armamos el enlace que lleva a la pagina donde se escribe la nueva contrasena
            // le pegamos el token para saber de que solicitud se trata
            $enlace = "restablecer.php?token=" . $token;
        }

        // marcamos que el formulario ya fue enviado
        // mostramos el mismo mensaje aunque el correo no exista o el telefono no coincida
        // asi nadie puede usar esta pagina para adivinar que correos estan registrados
        $enviado = true;
    }
}

// revisamos si estamos trabajando desde nuestra propia computadora
// las dos direcciones que revisamos son las de la computadora local
// solo en ese caso mostramos el enlace en pantalla porque pues en si no mandamos correos de verdad
$cont_local = $_SERVER["REMOTE_ADDR"] == "127.0.0.1" || $_SERVER["REMOTE_ADDR"] == "::1";

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <h1 class="h3 mb-3 text-center">Recuperar contraseña</h1>

                <?php if ($enviado): ?>
                    <!-- si el formulario fue enviado mostramos este mensaje -->
                    <div class="alert alert-success">
                         Si el correo y el teléfono coinciden con una cuenta recibiras el enlace para cambiar tu contraseña da click al enlace
                        vence en 30 minutos TIC TAC TIC TAC
                    </div>

                    <!-- si se creo un enlace y estamos en la computadora local lo mostramos aqui -->
                    <!-- este recuadro hace como si fuera el correo que le llegaria al usuario -->
                    <?php if ($enlace != "" && $cont_local): ?>
                        <div class="alert alert-warning">
                            <strong>Correo</strong><br>
                            <a href="<?= htmlspecialchars($enlace) ?>">
                                <?= htmlspecialchars($enlace) ?>
                            </a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- si hay un error mostramos el mensaje -->
                    <?php if ($error != ""): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- formulario para escribir el correo y el telefono -->
                    <form action="olvide.php" method="post">
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo electrónico</label>
                            <input type="email" name="correo" id="correo" class="form-control"
                                   required value="<?= htmlspecialchars($correo) ?>">
                        </div>

                        <!-- pedimos el telefono con el que se registro la cuenta -->
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono registrado (10 dígitos)</label>
                            <input type="tel" name="telefono" id="telefono" class="form-control" maxlength="10"
                                   required value="<?= htmlspecialchars($telefono) ?>">
                        </div>

                        <button type="submit" class="btn btn-lila w-100">Enviar enlace</button>
                    </form>

                <?php endif; ?>

                <p class="text-center mt-3 mb-0">
                    <a href="login.php">Volver a iniciar sesión</a>
                </p>

            </div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
