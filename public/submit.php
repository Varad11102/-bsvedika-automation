<?php

declare(strict_types=1);

use BSVedika\Validation;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/src/Validation.php';

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

$configPath = getenv('BSV_CONFIG_PATH') ?: dirname(__DIR__) . '/config/config.php';
if (!is_file($configPath)) {
    respond(503, ['error' => 'Service is not configured']);
}

/** @var array<string, mixed> $config */
$config = require $configPath;
$requiredConfig = ['shared_secret', 'db_host', 'db_port', 'db_name', 'db_user', 'db_password', 'db_table'];
foreach ($requiredConfig as $key) {
    if (!isset($config[$key]) || $config[$key] === '') {
        respond(503, ['error' => 'Service is not configured']);
    }
}

if (($config['db_table'] ?? '') !== 'plus_signup_test') {
    respond(503, ['error' => 'Production writes are disabled']);
}

$rawBody = file_get_contents('php://input');
$timestamp = $_SERVER['HTTP_X_BSV_TIMESTAMP'] ?? '';
$signature = strtolower($_SERVER['HTTP_X_BSV_SIGNATURE'] ?? '');

if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
    respond(401, ['error' => 'Expired or invalid request timestamp']);
}

$expectedSignature = hash_hmac('sha256', $timestamp . '.' . $rawBody, (string) $config['shared_secret']);
if (!hash_equals($expectedSignature, $signature)) {
    respond(401, ['error' => 'Invalid request signature']);
}

$input = json_decode($rawBody, true);
if (!is_array($input)) {
    respond(400, ['error' => 'Invalid JSON']);
}

$errors = Validation::registration($input);
if ($errors !== []) {
    respond(422, ['error' => 'Validation failed', 'fields' => $errors]);
}

$table = (string) $config['db_table'];
if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
    respond(503, ['error' => 'Invalid service configuration']);
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        (int) $config['db_port'],
        $config['db_name']
    );
    $pdo = new PDO($dsn, (string) $config['db_user'], (string) $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $existing = $pdo->prepare("SELECT mem_id FROM `{$table}` WHERE userid = :userid LIMIT 1");
    $existing->execute(['userid' => (string) $input['userid']]);
    $existingId = $existing->fetchColumn();

    if ($existingId !== false) {
        respond(200, ['status' => 'already_processed', 'member_id' => (int) $existingId]);
    }

    $sql = "INSERT INTO `{$table}`
        (email, name, age, sect, subsect, gothram, fname, mobile, address, status, photo, aadhar, cdate, userid)
        VALUES
        (:email, :name, :age, :sect, :subsect, :gothram, :fname, :mobile, :address, :status, :photo, :aadhar, :cdate, :userid)";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'email' => trim((string) $input['email']),
        'name' => trim((string) $input['name']),
        'age' => (string) (int) $input['age'],
        'sect' => trim((string) $input['sect']),
        'subsect' => trim((string) $input['subsect']),
        'gothram' => trim((string) $input['gothram']),
        'fname' => trim((string) $input['fname']),
        'mobile' => trim((string) $input['mobile']),
        'address' => trim((string) $input['address']),
        'status' => 'no',
        'photo' => trim((string) $input['photo_url']),
        'aadhar' => trim((string) $input['aadhar_last4']),
        'cdate' => gmdate('Y-m-d'),
        'userid' => trim((string) $input['userid']),
    ]);

    respond(201, ['status' => 'created', 'member_id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $exception) {
    error_log('BSVedika test insert failed: database error');
    respond(500, ['error' => 'Database operation failed']);
}
