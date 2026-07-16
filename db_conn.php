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

// ── Odontogram helper ───────────────────────────────────────
// The odontogram widget (assets/odontogram.js) writes teeth_data as a JSON
// array of per-tooth records, e.g.:
//   [{"fdi":18,"status":"planned","shade":"A3","size":"64","notes":""}, ...]
// FDI quadrants 1-2 (codes 11-28) = upper arch, quadrants 3-4 (31-48) = lower
// arch. This derives [upper_count, lower_count] from that array so every
// dashboard, list, and analytics query that reads tooth_upper/tooth_lower
// keeps working unchanged.
//
// A legacy plain comma-separated format ("18,17,21,41") is also accepted as
// a fallback, in case any older records were saved before the JSON format
// existed.
if (!function_exists('odonto_counts')) {
    function odonto_counts(string $teethData): array {
        $teethData = trim($teethData);
        if ($teethData === '') return [0, 0];

        $upper = 0;
        $lower = 0;

        $decoded = json_decode($teethData, true);
        if (is_array($decoded)) {
            foreach ($decoded as $tooth) {
                $fdi = isset($tooth['fdi']) ? (int)$tooth['fdi'] : 0;
                if ($fdi >= 11 && $fdi <= 28) $upper++;
                elseif ($fdi >= 31 && $fdi <= 48) $lower++;
            }
            return [$upper, $lower];
        }

        // Legacy CSV fallback
        foreach (array_filter(explode(',', $teethData)) as $t) {
            $fdi = (int)trim($t);
            if ($fdi >= 11 && $fdi <= 28) $upper++;
            elseif ($fdi >= 31 && $fdi <= 48) $lower++;
        }
        return [$upper, $lower];
    }
}
?>
