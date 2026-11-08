<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function validNumber($number): bool
{
    return is_string($number) && preg_match('/\A(?:006|002)[0-9]{7}\z/', $number) === 1;
}

function database(): PDO
{
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    if ($user === false || $password === false) {
        respond(503, ['error' => 'Falta configurar DB_USER y DB_PASSWORD en el servidor.']);
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'number_lookup';

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    if (!isset($_GET['numero'])) {
        respond(200, ['status' => 'ok']);
    }

    $number = $_GET['numero'];
    if (!validNumber($number)) {
        respond(422, ['error' => 'El número debe tener 10 dígitos y comenzar por 006 o 002.']);
    }

    try {
        $statement = database()->prepare(
            'SELECT numero, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido
             FROM registros WHERE numero = :numero'
        );
        $statement->execute(['numero' => $number]);
        $record = $statement->fetch();
    } catch (PDOException $error) {
        error_log('Database search failed: ' . $error->getMessage());
        respond(503, ['error' => 'No se pudo consultar la base de datos. Verifica la configuración del servidor.']);
    }

    respond(200, ['found' => $record !== false, 'record' => $record === false ? null : $record]);
}

if ($method === 'POST') {
    $rawBody = file_get_contents('php://input');
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        respond(400, ['error' => 'El contenido enviado no es JSON válido.']);
    }

    if (!is_array($data)) {
        respond(400, ['error' => 'Envía un objeto JSON con el número y los cuatro nombres.']);
    }

    $number = $data['numero'] ?? null;
    if (!validNumber($number)) {
        respond(422, ['error' => 'El número debe tener 10 dígitos y comenzar por 006 o 002.']);
    }

    $fields = ['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido'];
    $record = ['numero' => $number];
    foreach ($fields as $field) {
        $value = $data[$field] ?? null;
        if (!is_string($value)) {
            respond(422, ['error' => 'Completa los dos nombres y los dos apellidos.']);
        }

        $value = trim($value);
        if (preg_match('/\A.{1,100}\z/us', $value) !== 1) {
            respond(422, ['error' => 'Cada nombre y apellido debe tener entre 1 y 100 caracteres.']);
        }
        $record[$field] = $value;
    }

    try {
        $statement = database()->prepare(
            'INSERT INTO registros
                (numero, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido)
             VALUES
                (:numero, :primer_nombre, :segundo_nombre, :primer_apellido, :segundo_apellido)'
        );
        $statement->execute($record);
    } catch (PDOException $error) {
        if ($error->getCode() === '23000') {
            respond(409, ['error' => 'Ya existe un registro con ese número.']);
        }
        error_log('Database insert failed: ' . $error->getMessage());
        respond(503, ['error' => 'No se pudo guardar en la base de datos. Verifica la configuración del servidor.']);
    }

    respond(201, ['saved' => true, 'record' => $record]);
}

header('Allow: GET, POST');
respond(405, ['error' => 'Método no permitido.']);
