<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if(!isLoggedIn()) { header('Location: /worldcup2026/login.php'); exit; }
if(!csrf_verify($_POST['csrf'] ?? '')) { die('CSRF error'); }

$matchId = (int)($_POST['match_id'] ?? 0);
$teamId  = (int)($_POST['team_id']  ?? 0);
$userId  = getUser()['id'];
$now     = date('Y-m-d H:i:s');

$db = getDB();

// Verificar que el partido existe y la votación está abierta
$match = $db->prepare("SELECT * FROM matches WHERE id=? AND vote_start<=? AND vote_end>=?");
$match->execute([$matchId, $now, $now]);
$m = $match->fetch();

if(!$m) {
    header('Location: /worldcup2026/index.php?error=closed');
    exit;
}

// Verificar que el team_id pertenece al partido
if($teamId != $m['home_team_id'] && $teamId != $m['away_team_id']) {
    header('Location: /worldcup2026/index.php?error=invalid');
    exit;
}

// Verificar que no haya votado ya
$existing = $db->prepare("SELECT id FROM votes WHERE user_id=? AND match_id=?");
$existing->execute([$userId, $matchId]);
if($existing->fetch()) {
    header('Location: /worldcup2026/index.php?error=already_voted');
    exit;
}

// Registrar voto
$db->prepare("INSERT INTO votes (user_id, match_id, team_id) VALUES (?,?,?)")
   ->execute([$userId, $matchId, $teamId]);

header('Location: /worldcup2026/index.php?voted=1#votar');
exit;
