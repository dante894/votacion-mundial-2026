<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de conexión MySQL</h2>";

define('DB_HOST', 'sql305.infinityfree.com');
define('DB_NAME', 'if0_41932620_worldcup');
define('DB_USER', 'if0_41932620');
define('DB_PASS', '4tgpzR5IuSp1n');

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS
    );
    echo "<p style='color:green'>✅ Conexión MySQL exitosa!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error MySQL: " . $e->getMessage() . "</p>";
}

echo "<h2>Versión PHP</h2>";
echo "<p>" . phpversion() . "</p>";

echo "<h2>Extensiones disponibles</h2>";
echo "<p>PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "</p>";
echo "<p>cURL: " . (extension_loaded('curl') ? '✅' : '❌') . "</p>";
echo "<p>SQLite: " . (extension_loaded('pdo_sqlite') ? '✅ disponible' : '❌ no disponible') . "</p>";
