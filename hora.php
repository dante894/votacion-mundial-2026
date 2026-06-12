	
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/bootstrap.php';
$db = getDB();

echo "<h2>Hora del servidor</h2>";
echo "<p>Fecha y hora: <b>" . date('Y-m-d H:i:s') . "</b></p>";
echo "<p>Zona horaria: <b>" . date_default_timezone_get() . "</b></p>";

echo "<h2>Creando tabla chat_messages...</h2>";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            user_avatar VARCHAR(500) DEFAULT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p style='color:green'>✅ Tabla chat_messages creada/verificada OK</p>";
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Tablas en la BD</h2>";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<p>" . implode(', ', $tables) . "</p>";

echo "<h2>Partidos</h2>";
$now = date('Y-m-d H:i:s');
$matches = $db->query("SELECT id, vote_start, vote_end FROM matches")->fetchAll();
if(empty($matches)) {
    echo "<p style='color:orange'>⚠️ No hay partidos en la BD</p>";
} else {
    foreach($matches as $m) {
        $abierta = $m['vote_start'] <= $now && $m['vote_end'] >= $now;
        echo "<p>ID {$m['id']} | start: {$m['vote_start']} | end: {$m['vote_end']} | <b style='color:" . ($abierta?'green':'red') . "'>" . ($abierta?'✅ ABIERTA':'❌ NO ABIERTA') . "</b></p>";
    }
}
