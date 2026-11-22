-- este es el script de la base de datos de mi biblioteca digital
-- se importa desde phpmyadmin en la pestana importar
-- crea la base de datos, las tablas y los datos de prueba

-- creamos la base de datos si todavia no existe
-- usamos utf8mb4 para que se guarden bien los acentos y la letra enie
CREATE DATABASE IF NOT EXISTS biblioteca
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- le decimos a mysql que vamos a trabajar con esta base de datos
USE biblioteca;

-- borramos las tablas si ya existian para poder importar el script otra vez sin errores
-- primero borramos las tablas que dependen de usuarios y al final la de usuarios
DROP TABLE IF EXISTS recuperaciones;
DROP TABLE IF EXISTS libros;
DROP TABLE IF EXISTS usuarios;

-- aqui guardamos a las personas que pueden iniciar sesion
CREATE TABLE usuarios (
    -- numero unico de cada usuario, se genera solo
    id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    -- unique hace que no se puedan repetir los correos
    correo        VARCHAR(150) NOT NULL UNIQUE,
    telefono      VARCHAR(20)  NOT NULL,
    -- nunca guardamos la contrasena como texto normal, solo su hash
    password_hash VARCHAR(255) NOT NULL,
    -- enum solo acepta estos dos valores, si no se indica sera usuario
    rol           ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB;

-- aqui guardamos los libros que agrega cada usuario
CREATE TABLE libros (
    -- numero unico de cada libro, se genera solo
    id_libro    INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(200) NOT NULL,
    autor       VARCHAR(150) NOT NULL,
    genero      VARCHAR(100) NOT NULL,
    -- anio de publicacion
    anio        INT NOT NULL,
    -- el estado solo puede ser uno de estos tres, si no se indica sera pendiente
    estado      ENUM('Pendiente', 'Leyendo', 'Terminado') NOT NULL DEFAULT 'Pendiente',
    -- nombre del archivo de la portada, el archivo esta en la carpeta guarda_archivos
    imagen      VARCHAR(255) NOT NULL DEFAULT '',
    -- nombre del archivo pdf del libro, tambien esta en guarda_archivos
    archivo_pdf VARCHAR(255) NOT NULL DEFAULT '',
    -- 1 quiere decir que el libro se ve y 0 que el admin lo elimino
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    -- id del usuario duenio del libro
    id_usuario  INT NOT NULL,

    -- relacionamos la tabla libros con la tabla usuarios
    -- asi sabemos a quien pertenece cada libro, un usuario puede tener muchos libros
    -- on delete cascade quiere decir que si se borra un usuario tambien se borran sus libros
    CONSTRAINT fk_libros_usuarios
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- aqui guardamos las solicitudes para recuperar la contrasena
-- cada solicitud tiene un enlace que vence en 30 minutos
CREATE TABLE recuperaciones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    -- id del usuario que pidio cambiar su contrasena
    id_usuario  INT NOT NULL,
    -- guardamos el hash del token y no el token tal cual
    token_hash  CHAR(64) NOT NULL UNIQUE,
    -- fecha y hora en que vence el enlace
    expira      DATETIME NOT NULL,
    -- 0 quiere decir que el enlace todavia sirve y 1 que ya se uso
    usado       TINYINT(1) NOT NULL DEFAULT 0,

    -- el usuario de la solicitud debe existir en la tabla usuarios
    -- si se borra el usuario tambien se borran sus solicitudes
    CONSTRAINT fk_recuperaciones_usuarios
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- cuenta del administrador
-- correo esme@gmail.com y contrasena 12345678
-- la contrasena ya esta guardada como hash
INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES
('Administrador', 'esme@gmail.com', '1234567891',
 '$2y$10$CalEq6vB1cGPJvEIJGee.Ox8.9IuC8Kqy.lmxEb61j0reoCHXxk3q', 'admin');

-- cuenta de un usuario normal para demostrar los permisos
-- correo miau@gmail.com y contrasena 123456789
INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES
('Miau', 'miau@gmail.com', '1234567891',
 '$2y$10$gyFsOOY8R.8Dre2PIl158uG42Hvx8svojV5WzV/ANfiARdybMKypm', 'usuario');


-- libros de prueba, dos del administrador que tiene el id 1 y uno de miau que tiene el id 2
-- asi al entrar como miau se ve que solo aparece su libro
INSERT INTO libros (titulo, autor, genero, anio, estado, id_usuario) VALUES
('miau',  'miau',  'Novela', 2002, 'Terminado', 1),
('Ariel', 'Ariel', 'ariel',  1943, 'Leyendo',   1),
('El principito', 'Antoine de Saint-Exupéry', 'Fábula', 1943, 'Pendiente', 2);
