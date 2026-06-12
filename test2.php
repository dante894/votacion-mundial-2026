<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Probando bootstrap...</h2>";

try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "<p style='color:green'>✅ Bootstrap OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color:red'>❌ Error fatal: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Probando DB directamente...</h2>";
try {
    define('DB_HOST', 'sql305.infinityfree.com');
    define('DB_NAME', 'if0_41932620_worldcup');
    define('DB_USER', 'if0_41932620');
    define('DB_PASS', '4tgpzR5IuSp1n');

    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Probar si las tablas existen
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tablas en la BD: " . (empty($tables) ? 'ninguna aún' : implode(', ', $tables)) . "</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ " . $e->getMessage() . "</p>";
}
