<?php
// db_conn.php - Dental Portal Database Connection
//
// Reads connection details from environment variables so the SAME file works
// locally (XAMPP) and in production (Render), without committing real
// credentials to GitHub. If an env var isn't set, it falls back to your local
// XAMPP defaults below.

$sName   = getenv('DB_HOST') ?: '127.0.0.1';
$dPort   = getenv('DB_PORT') ?: '3307';        // XAMPP default in this project was 3307
$uName   = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'defaultdb';

// Some managed MySQL hosts (e.g. Aiven) require SSL and provide a CA cert.
// Set DB_SSL_CA to the absolute path of that cert file if your host needs it.
$sslCa = getenv('DB_SSL_CA') ?: '';

try {
    $dsn = "mysql:host=$sName;port=$dPort;dbname=$db_name;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($sslCa && file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $conn = new PDO($dsn, $uName, $pass, $options);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}
?>
