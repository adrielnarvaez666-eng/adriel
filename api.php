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

function recordFields(): array
{
    return [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'madre_primer_nombre',
        'madre_segundo_nombre',
        'madre_primer_apellido',
        'madre_segundo_apellido',
        'padre_primer_nombre',
        'padre_segundo_nombre',
        'padre_primer_apellido',
        'padre_segundo_apellido',
    ];
}

function database(): PDO
{
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    if ($user === false || $user === '') {
        respond(503, ['error' => 'Falta configurar DB_USER en el servidor.']);
    }
    if ($password === false) {
        $password = '';
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'registro civil';

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

function exportRecords(): void
{
    try {
        $records = database()->query(
            'SELECT numero, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                    madre_primer_nombre, madre_segundo_nombre, madre_primer_apellido, madre_segundo_apellido,
                    padre_primer_nombre, padre_segundo_nombre, padre_primer_apellido, padre_segundo_apellido,
                    creado_en
             FROM registros
             ORDER BY creado_en, numero'
        )->fetchAll();
    } catch (PDOException $error) {
        error_log('Database export failed: ' . $error->getMessage());
        respond(503, ['error' => 'No se pudieron exportar los registros. Verifica la configuración de la base de datos.']);
    }

    $columns = [
        'numero' => 'Número',
        'primer_nombre' => 'Primer nombre',
        'segundo_nombre' => 'Segundo nombre',
        'primer_apellido' => 'Primer apellido',
        'segundo_apellido' => 'Segundo apellido',
        'madre_primer_nombre' => 'Madre: primer nombre',
        'madre_segundo_nombre' => 'Madre: segundo nombre',
        'madre_primer_apellido' => 'Madre: primer apellido',
        'madre_segundo_apellido' => 'Madre: segundo apellido',
        'padre_primer_nombre' => 'Padre: primer nombre',
        'padre_segundo_nombre' => 'Padre: segundo nombre',
        'padre_primer_apellido' => 'Padre: primer apellido',
        'padre_segundo_apellido' => 'Padre: segundo apellido',
        'creado_en' => 'Fecha de creación',
    ];

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="registros.xls"');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<?mso-application progid="Excel.Sheet"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
        . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
        . '<Worksheet ss:Name="Registros"><Table><Row>';

    foreach ($columns as $label) {
        echo '<Cell><Data ss:Type="String">'
            . htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</Data></Cell>';
    }

    echo '</Row>';
    foreach ($records as $record) {
        echo '<Row>';
        foreach (array_keys($columns) as $field) {
            echo '<Cell><Data ss:Type="String">'
                . htmlspecialchars((string) $record[$field], ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</Data></Cell>';
        }
        echo '</Row>';
    }
    echo '</Table></Worksheet></Workbook>';
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    if (isset($_GET['export'])) {
        if ($_GET['export'] !== 'excel') {
            respond(400, ['error' => 'El formato de exportación solicitado no es válido.']);
        }
        exportRecords();
        exit;
    }

    if (!isset($_GET['numero'])) {
        try {
            database()->query('SELECT numero FROM registros LIMIT 0');
        } catch (PDOException $error) {
            error_log('Database health check failed: ' . $error->getMessage());
            respond(503, [
                'status' => 'error',
                'database' => 'unavailable',
                'error' => 'No se pudo conectar con la base de datos.',
            ]);
        }

        respond(200, ['status' => 'ok', 'database' => 'connected']);
    }

    $number = $_GET['numero'];
    if (!validNumber($number)) {
        respond(422, ['error' => 'El número debe tener 10 dígitos y comenzar por 006 o 002.']);
    }

    try {
        $statement = database()->prepare(
            'SELECT numero, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                    madre_primer_nombre, madre_segundo_nombre, madre_primer_apellido, madre_segundo_apellido,
                    padre_primer_nombre, padre_segundo_nombre, padre_primer_apellido, padre_segundo_apellido
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
        $data = json_decode($rawBody, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        respond(400, ['error' => 'El contenido enviado no es JSON válido.']);
    }

    if (!($data instanceof stdClass)) {
        respond(400, ['error' => 'Envía un objeto JSON con el número, los datos de la persona y los de sus padres.']);
    }

    $number = $data->numero ?? null;
    if (!validNumber($number)) {
        respond(422, ['error' => 'El número debe tener 10 dígitos y comenzar por 006 o 002.']);
    }

    $record = ['numero' => $number];
    foreach (recordFields() as $field) {
        $value = $data->{$field} ?? null;
        if (!is_string($value)) {
            respond(422, ['error' => 'Completa los datos de la persona y los dos nombres y apellidos de cada padre.']);
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
                (numero, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                 madre_primer_nombre, madre_segundo_nombre, madre_primer_apellido, madre_segundo_apellido,
                 padre_primer_nombre, padre_segundo_nombre, padre_primer_apellido, padre_segundo_apellido)
             VALUES
                (:numero, :primer_nombre, :segundo_nombre, :primer_apellido, :segundo_apellido,
                 :madre_primer_nombre, :madre_segundo_nombre, :madre_primer_apellido, :madre_segundo_apellido,
                 :padre_primer_nombre, :padre_segundo_nombre, :padre_primer_apellido, :padre_segundo_apellido)'
        );
        $statement->execute($record);
    } catch (PDOException $error) {
        if ($error->getCode() === '23000') {
            respond(409, ['error' => 'Ya existe un registro con ese número.']);
        }
        error_log('Database insert failed: ' . $error->getMessage());
        respond(503, ['error' => 'No se pudo guardar en la base de datos. Verifica la configuración del servidor.']);
    }

    respond(201, ['saved' => true, 'database' => 'saved', 'record' => $record]);
}

header('Allow: GET, POST');
respond(405, ['error' => 'Método no permitido.']);
