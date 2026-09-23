<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once "conexion.php";

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// -------------------------------------------------------------
// REGISTRO DE USUARIO
// -------------------------------------------------------------
if ($metodo === 'POST' && $accion === 'registro') {
    $datos = json_decode(file_get_contents("php://input"), true);
    
    if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['password'])) {
        echo json_encode(["status" => "error", "message" => "Completa todos los campos"]);
        exit;
    }

    $nombre = $datos['nombre'];
    $email = $datos['email'];
    $password = password_hash($datos['password'], PASSWORD_BCRYPT); // Encriptación segura

    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $password);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuario registrado con éxito",
            "user" => [
                "id" => $stmt->insert_id,
                "nombre" => $nombre,
                "email" => $email
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "El correo ya está registrado"]);
    }
    $stmt->close();
}

// -------------------------------------------------------------
// INICIO DE SESIÓN
// -------------------------------------------------------------
elseif ($metodo === 'POST' && $accion === 'login') {
    $datos = json_decode(file_get_contents("php://input"), true);

    if (empty($datos['email']) || empty($datos['password'])) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $stmt = $conexion->prepare("SELECT id, nombre, email, password FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $datos['email']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($usuario = $resultado->fetch_assoc()) {
        if (password_verify($datos['password'], $usuario['password'])) {
            unset($usuario['password']); // Ocultar contraseña hash
            echo json_encode(["status" => "success", "user" => $usuario]);
        } else {
            echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
    }
    $stmt->close();
}

// -------------------------------------------------------------
// OBTENER REGISTROS DE UN USUARIO ESPECÍFICO
// -------------------------------------------------------------
elseif ($metodo === 'GET' && $accion === 'registros') {
    $usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

    if ($usuario_id === 0) {
        echo json_encode(["status" => "error", "message" => "Usuario no especificado"]);
        exit;
    }

    $stmt = $conexion->prepare("SELECT * FROM registros WHERE usuario_id = ? ORDER BY fecha DESC, id DESC");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $registros = [];
    while ($fila = $resultado->fetch_assoc()) {
        $registros[] = $fila;
    }

    echo json_encode($registros);
    $stmt->close();
}

// -------------------------------------------------------------
// GUARDAR REGISTRO DE ENTRENAMIENTO
// -------------------------------------------------------------
elseif ($metodo === 'POST' && $accion === 'guardar_registro') {
    $datos = json_decode(file_get_contents("php://input"), true);

    if (isset($datos['usuario_id'], $datos['fecha'], $datos['musculo'], $datos['ejercicio'], $datos['peso'], $datos['repeticiones'])) {
        $usuario_id = intval($datos['usuario_id']);
        $fecha = $datos['fecha'];
        $musculo = $datos['musculo'];
        $ejercicio = $datos['ejercicio'];
        $peso = floatval($datos['peso']);
        $repeticiones = intval($datos['repeticiones']);
        $rpe = isset($datos['rpe']) ? intval($datos['rpe']) : null;
        $notas = isset($datos['notas']) ? $datos['notas'] : '';

        $stmt = $conexion->prepare("INSERT INTO registros (usuario_id, fecha, musculo, ejercicio, peso, repeticiones, rpe, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdiis", $usuario_id, $fecha, $musculo, $ejercicio, $peso, $repeticiones, $rpe, $notas);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Registro guardado correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al guardar el registro"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Campos obligatorios faltantes"]);
    }
}

$conexion->close();
?>