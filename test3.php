<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Probando index.php...</h2>";

// Simular lo que hace index.php
require_once __DIR__ . '/includes/bootstrap.php';

$db   = getDB();
$user = getUser();

echo "<p style='color:green'>✅ Bootstrap OK</p>";
echo "<p>Usuario: " . ($user ? htmlspecialchars($user['name']) : 'No logueado') . "</p>";

// Probar query de partidos
try {
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("
        SELECT m.*,
               ht.name AS home_name, ht.flag_code AS home_flag,
               at.name AS away_name, at.flag_code AS away_flag,
               (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id AND v.team_id=m.home_team_id) AS home_votes,
               (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id AND v.team_id=m.away_team_id) AS away_votes,
               (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id) AS total_votes,
               (SELECT team_id FROM votes v WHERE v.match_id=m.id AND v.user_id=?) AS user_voted
        FROM matches m
        JOIN teams ht ON ht.id = m.home_team_id
        JOIN teams at ON at.id = m.away_team_id
        WHERE m.vote_start <= ? AND m.vote_end >= ?
        ORDER BY m.vote_end ASC
    ");
    $stmt->execute([$user['id'] ?? 0, $now, $now]);
    $votable = $stmt->fetchAll();
    echo "<p style='color:green'>✅ Query partidos OK — " . count($votable) . " partidos activos</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error query: " . $e->getMessage() . "</p>";
}

// Probar query de equipos
try {
    $teams = $db->query("SELECT * FROM teams ORDER BY group_name, name")->fetchAll();
    echo "<p style='color:green'>✅ Equipos OK — " . count($teams) . " equipos</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error equipos: " . $e->getMessage() . "</p>";
}

echo "<h2>Todo OK — el problema está en el HTML del index.php</h2>";
echo "<p>Revisá si hay algún error de sintaxis PHP en index.php</p>";
