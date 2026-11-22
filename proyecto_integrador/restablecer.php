<?php
// en esta pagina el usuario escribe su nueva contrasena
// solo funciona si el enlace existe, no se ha usado y no ha vencido

// iniciamos la sesion para poder guardar el mensaje de contrasena cambiada
session_start();
// incluimos la conexion para poder revisar el enlace y cambiar la contrasena
require "config/conexion.php";

// guardamos el token que viene en el enlace
// si no viene ningun token la variable se queda vacia
$token = $_GET["token"] ?? "";
$error = "";

// buscamos en la tabla recuperaciones si hay una solicitud con ese token
// tambien revisamos que no se haya usado y que todavia no venza
$sql  = "SELECT * FROM recuperaciones
         WHERE token_hash = :token_hash AND usado = 0 AND expira > NOW()";
$stmt = $pdo->prepare($sql);
// sacamos el hash del token para compararlo con el que guardamos en olvide.php
$stmt->execute(["token_hash" => hash("sha256", $token)]);
// fetch nos da la solicitud encontrada o false si el enlace no sirve
$solicitud = $stmt->fetch();

// revisamos que el enlace sea valido y que el usuario haya enviado el formulario
if ($solicitud && $_SERVER["REQUEST_METHOD"] == "POST") {
    // guardamos las dos contrasenas que escribio el usuario
    $password  = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";

    // validamos la contrasena igual que en el registro
    if (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password != $password2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // guardamos la nueva contrasena del usuario convertida en hash
        $sql  = "UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "password_hash" => password_hash($password, PASSWORD_DEFAULT),
            "id_usuario"    => $solicitud["id_usuario"],
        ]);

        // marcamos la solicitud como usada para que el mismo enlace no se pueda volver a usar
        $sql  = "UPDATE recuperaciones SET usado = 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["id" => $solicitud["id"]]);

        // guardamos un mensaje y lo mandamos al login para que entre con su nueva contrasena
        $_SESSION["mensaje"] = "Contraseña actualizada. Ya puedes iniciar sesión.";
        header("Location: login.php");
        exit;
    }
}

// incluimos la parte de arriba de la pagina con el menu
require "includes/header.php";
?>

<!-- tarjeta para cambiar la contrasena -->
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Nueva contraseña</h1>

                <!-- primero revisamos si el enlace es valido -->
                <?php if (!$solicitud): ?>
                    <!-- si el enlace no existe, ya se uso o ya vencio mostramos este mensaje -->
                    <div class="alert alert-danger">El enlace no es válido o ya venció.</div>
                    <!-- boton para pedir un nuevo enlace -->
                    <a href="olvide.php" class="btn btn-lila w-100">Pedir otro enlace</a>
                <?php else: ?>
                    <!-- si el enlace es valido revisamos si hay algun error -->
                    <?php if ($error != ""): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <!-- formulario para escribir la nueva contrasena -->
                    <!-- dejamos el token en la direccion para no perder la solicitud al enviar -->
                    <form action="restablecer.php?token=<?= htmlspecialchars($token) ?>" method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña nueva (mínimo 8 caracteres)</label>
                            <input type="password" name="password" id="password" class="form-control" minlength="8" required>
                        </div>
                        <div class="mb-4">
                            <label for="password2" class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password2" id="password2" class="form-control" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-lila w-100">Guardar contraseña</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require "includes/footer.php"; ?>
