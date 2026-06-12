<?php
require_once __DIR__ . '/includes/bootstrap.php';

$db   = getDB();
$user = getUser();

// ── Cargar partidos activos para votar ─────────────────────────────────
$now = date('Y-m-d H:i:s');
$votableMatches = $db->prepare("
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
$votableMatches->execute([$user['id'] ?? 0, $now, $now]);
$votable = $votableMatches->fetchAll();

// ── Partidos por etapa (fase de grupos + eliminatorias) ─────────────────
$stageMatches = $db->prepare("
    SELECT m.*,
           ht.name AS home_name, ht.flag_code AS home_flag,
           at.name AS away_name, at.flag_code AS away_flag,
           (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id AND v.team_id=m.home_team_id) AS home_votes,
           (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id AND v.team_id=m.away_team_id) AS away_votes
    FROM matches m
    JOIN teams ht ON ht.id = m.home_team_id
    JOIN teams at ON at.id = m.away_team_id
    ORDER BY m.stage, m.match_date ASC
");
$stageMatches->execute();
$allMatches = $stageMatches->fetchAll();

$matchesByStage = [];
foreach ($allMatches as $m) {
    $matchesByStage[$m['stage']][] = $m;
}

// ── Equipos por grupo ───────────────────────────────────────────────────
$teamsStmt = $db->query("SELECT * FROM teams ORDER BY group_name, name");
$allTeams  = $teamsStmt->fetchAll();
$teamsByGroup = [];
foreach ($allTeams as $t) {
    $teamsByGroup[$t['group_name']][] = $t;
}

function pct(int $part, int $total): int {
    return $total > 0 ? (int)round($part / $total * 100) : 50;
}

function timeLeft(string $end): string {
    $diff = strtotime($end) - time();
    if ($diff <= 0) return 'Cerrada';
    $d = floor($diff / 86400);
    $h = floor(($diff % 86400) / 3600);
    $m = floor(($diff % 3600) / 60);
    if ($d > 0) return "{$d}d {$h}h";
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

$stageLabels = [
    'groups'       => 'Fase de Grupos',
    'round32'      => 'Dieciseisavos de Final',
    'round16'      => 'Octavos de Final',
    'quarterfinals'=> 'Cuartos de Final',
    'semifinals'   => 'Semifinal',
    'final'        => 'Final',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FIFA World Cup 2026 — Predicciones</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
<style>
:root {
  --bg:       #070a0f;
  --surface:  #0d1117;
  --card:     #111820;
  --border:   #1e2832;
  --accent:   #c9a227;
  --gold:     #f0b429;
  --green:    #22c55e;
  --blue:     #3b82f6;
  --red:      #ef4444;
  --text:     #e8edf3;
  --muted:    #5a6a7a;
  --radius:   14px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  background-image:
    radial-gradient(ellipse 80% 40% at 50% -20%, rgba(201,162,39,.08) 0%, transparent 70%),
    radial-gradient(ellipse 60% 30% at 80% 110%, rgba(59,130,246,.05) 0%, transparent 60%);
}

/* ── NAV ── */
nav {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 32px;
  border-bottom: 1px solid var(--border);
  backdrop-filter: blur(12px);
  position: sticky; top: 0; z-index: 100;
  background: rgba(7,10,15,.85);
}
.nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.nav-brand .trophy { font-size: 28px; }
.nav-brand h1 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 22px; letter-spacing: 2px;
  background: linear-gradient(135deg, var(--gold), #fff);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.nav-links { display: flex; align-items: center; gap: 16px; }
.nav-links a {
  color: var(--muted); text-decoration: none; font-size: 13px; font-weight: 500;
  transition: color .2s;
}
.nav-links a:hover { color: var(--text); }
.btn-login {
  background: linear-gradient(135deg, var(--gold), #d4911e);
  color: #000; border: none; border-radius: 8px;
  padding: 8px 20px; font-size: 13px; font-weight: 700;
  cursor: pointer; text-decoration: none; transition: opacity .2s;
}
.btn-login:hover { opacity: .85; }
.user-pill {
  display: flex; align-items: center; gap: 10px;
  background: var(--card); border: 1px solid var(--border);
  border-radius: 40px; padding: 6px 14px 6px 8px;
}
.user-pill img {
  width: 28px; height: 28px; border-radius: 50%; object-fit: cover;
}
.user-pill span { font-size: 13px; font-weight: 500; }

/* ── HERO ── */
.hero {
  text-align: center; padding: 64px 32px 48px;
  position: relative; overflow: hidden;
}
.hero::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse 60% 80% at 50% 50%, rgba(201,162,39,.06), transparent 70%);
  pointer-events: none;
}
.hero .year {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(80px, 15vw, 180px);
  line-height: 1;
  background: linear-gradient(180deg, rgba(240,180,41,.9) 0%, rgba(201,162,39,.3) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  letter-spacing: -4px;
  animation: fadeUp .8s ease both;
}
.hero h2 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(28px, 5vw, 52px); letter-spacing: 6px;
  color: var(--text); margin-top: -12px;
  animation: fadeUp .8s .1s ease both;
}
.hero p {
  color: var(--muted); font-size: 15px; margin-top: 10px;
  animation: fadeUp .8s .2s ease both;
}
.host-flags { display: flex; justify-content: center; gap: 12px; margin-top: 18px; animation: fadeUp .8s .3s ease both; }
.host-flags img { width: 40px; height: 27px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.5); }

@keyframes fadeUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }

/* ── SECTION TITLE ── */
.section-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(28px, 4vw, 44px); letter-spacing: 3px;
  color: var(--text); margin-bottom: 6px;
}
.section-sub { color: var(--muted); font-size: 13px; margin-bottom: 28px; }

.container { max-width: 1400px; margin: 0 auto; padding: 0 24px; }
section { padding: 48px 0; }
section + section { border-top: 1px solid var(--border); }

/* ── VOTE CARDS ── */
.vote-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }

.vote-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
  transition: transform .2s, border-color .2s, box-shadow .2s;
  position: relative; overflow: hidden;
}
.vote-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, var(--gold), transparent);
}
.vote-card:hover { transform: translateY(-3px); border-color: rgba(201,162,39,.3); box-shadow: 0 12px 40px rgba(0,0,0,.4); }

.vote-card .meta {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; font-size: 11px; color: var(--muted);
}
.group-badge {
  background: rgba(201,162,39,.15); color: var(--gold);
  border: 1px solid rgba(201,162,39,.3);
  border-radius: 6px; padding: 3px 8px;
  font-weight: 700; font-size: 11px; letter-spacing: 1px;
}
.timer {
  display: flex; align-items: center; gap: 4px; color: var(--red);
  font-weight: 600; font-size: 11px;
}

.matchup { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }

.team-side { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
.team-side img {
  width: 72px; height: 48px; object-fit: cover;
  border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.5);
  transition: transform .2s;
}
.team-side img:hover { transform: scale(1.05); }
.team-side .tname {
  font-size: 12px; font-weight: 600; text-align: center;
  line-height: 1.3; max-width: 90px;
}

.vs-block { text-align: center; flex-shrink: 0; }
.vs-block .vs { font-family: 'Bebas Neue', sans-serif; font-size: 28px; color: var(--muted); line-height: 1; }
.vs-block .score { font-family: 'Bebas Neue', sans-serif; font-size: 32px; color: var(--gold); line-height: 1; }

/* Vote bar */
.vote-bar-wrap { margin-bottom: 12px; }
.vote-bar-labels { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); margin-bottom: 6px; }
.vote-bar-labels b { color: var(--text); }
.vote-bar {
  height: 6px; border-radius: 99px; overflow: hidden;
  background: rgba(255,255,255,.07);
}
.vote-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--blue), var(--green));
  border-radius: 99px;
  transition: width .6s ease;
}
.vote-total { text-align: center; font-size: 11px; color: var(--muted); margin-bottom: 16px; }

/* Vote buttons */
.vote-btns { display: flex; gap: 8px; }
.vote-btn {
  flex: 1; padding: 10px 8px;
  border: 1px solid var(--border);
  border-radius: 10px; background: rgba(255,255,255,.04);
  color: var(--text); font-size: 12px; font-weight: 600;
  cursor: pointer; transition: all .2s; text-align: center;
}
.vote-btn:hover { background: rgba(59,130,246,.15); border-color: var(--blue); color: var(--blue); }
.vote-btn.voted-home { background: rgba(59,130,246,.2); border-color: var(--blue); color: var(--blue); }
.vote-btn.voted-away { background: rgba(34,197,94,.2); border-color: var(--green); color: var(--green); }
.vote-btn.voted-home.right,
.vote-btn.voted-away.left { opacity: .4; cursor: default; }
.vote-btn:disabled { opacity: .4; cursor: not-allowed; }
.dates-row { display: flex; justify-content: space-between; font-size: 10px; color: var(--muted); margin-top: 10px; border-top: 1px solid var(--border); padding-top: 10px; }

/* ── GROUPS GRID ── */
.groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.group-card {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden;
}
.group-header {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid var(--border);
  background: rgba(255,255,255,.02);
}
.group-letter {
  font-family: 'Bebas Neue', sans-serif; font-size: 28px;
  color: var(--gold); line-height: 1; min-width: 24px;
}
.group-title { font-size: 12px; color: var(--muted); font-weight: 500; letter-spacing: 1px; text-transform: uppercase; }
.group-table { width: 100%; border-collapse: collapse; }
.group-table th {
  font-size: 10px; color: var(--muted); text-transform: uppercase;
  letter-spacing: .5px; padding: 6px 10px 4px; text-align: center; font-weight: 600;
}
.group-table th:first-child { text-align: left; }
.group-table td { padding: 8px 10px; font-size: 12px; text-align: center; border-top: 1px solid rgba(255,255,255,.04); }
.group-table td:first-child { text-align: left; }
.team-cell { display: flex; align-items: center; gap: 8px; }
.team-cell img { width: 26px; height: 17px; object-fit: cover; border-radius: 2px; flex-shrink: 0; }
.team-cell span { font-weight: 500; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
.qualify-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--green); margin-right: 4px; flex-shrink: 0; }

/* ── BRACKET ── */
.bracket-stage { margin-bottom: 40px; }
.bracket-label {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 26px; letter-spacing: 2px; color: var(--gold);
  margin-bottom: 14px; display: flex; align-items: center; gap: 10px;
}
.bracket-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.bracket-matches { display: flex; flex-wrap: wrap; gap: 12px; }
.bracket-card {
  background: var(--card); border: 1px solid var(--border); border-radius: 10px;
  padding: 14px 16px; min-width: 220px; flex: 1; max-width: 300px;
}
.bracket-team {
  display: flex; align-items: center; gap: 8px; padding: 6px 0;
}
.bracket-team + .bracket-team { border-top: 1px solid var(--border); }
.bracket-team img { width: 28px; height: 19px; object-fit: cover; border-radius: 3px; }
.bracket-team .bname { flex: 1; font-size: 13px; font-weight: 500; }
.bracket-team .bscore { font-family: 'Bebas Neue', sans-serif; font-size: 18px; color: var(--gold); min-width: 20px; text-align: center; }
.bracket-team .bpct { font-size: 11px; color: var(--muted); min-width: 32px; text-align: right; }
.bracket-date { font-size: 10px; color: var(--muted); margin-top: 8px; text-align: center; padding-top: 8px; border-top: 1px solid var(--border); }

/* ── FOOTER ── */
footer {
  text-align: center; padding: 32px;
  color: var(--muted); font-size: 12px;
  border-top: 1px solid var(--border);
}

/* Flash */
.flash {
  position: fixed; top: 80px; right: 24px; z-index: 999;
  background: var(--card); border: 1px solid var(--border);
  border-left: 3px solid var(--green);
  padding: 12px 20px; border-radius: 10px;
  font-size: 13px; font-weight: 500;
  animation: slideIn .3s ease, fadeOut .5s 2.5s ease forwards;
}
@keyframes slideIn { from { transform: translateX(40px); opacity:0; } to { transform: translateX(0); opacity:1; } }
@keyframes fadeOut { to { opacity: 0; transform: translateX(40px); } }

/* Login required msg */
.login-nudge {
  text-align: center; padding: 10px;
  background: rgba(201,162,39,.08);
  border: 1px solid rgba(201,162,39,.2);
  border-radius: 10px; font-size: 12px; color: var(--gold); margin-top: 10px;
}
.login-nudge a { color: var(--gold); font-weight: 700; }

@media (max-width: 640px) {
  nav { padding: 12px 16px; }
  .container { padding: 0 14px; }
  .hero { padding: 40px 16px 32px; }
  .vote-grid { grid-template-columns: 1fr; }
  .groups-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="index.php" class="nav-brand">
    <span class="trophy">🏆</span>
    <h1>WORLD CUP 2026</h1>
  </a>
  <div class="nav-links">
    <a href="#votar">Votar</a>
    <a href="#grupos">Grupos</a>
    <a href="#eliminatorias">Eliminatorias</a>
    <?php if(isAdmin()): ?>
    <a href="admin/index.php" style="color:var(--gold)">⚙ Admin</a>
    <?php endif; ?>
    <?php if($user): ?>
      <div class="user-pill">
        <?php if($user['avatar']): ?>
          <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
        <?php else: ?>
          <span style="font-size:20px">👤</span>
        <?php endif; ?>
        <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
      </div>
      <a href="auth/logout.php" style="font-size:12px; color:var(--muted)">Salir</a>
    <?php else: ?>
      <a href="login.php" class="btn-login">Iniciar sesión</a>
    <?php endif; ?>
  </div>
</nav>

<?php if(!empty($_GET['voted'])): ?>
<div class="flash">✅ Voto registrado correctamente</div>
<?php endif; ?>

<!-- HERO -->
<div class="hero">
  <div class="year">2026</div>
  <h2>FIFA WORLD CUP</h2>
  <p>Canadá · México · Estados Unidos — 48 selecciones · 104 partidos</p>
  <div class="host-flags">
    <img src="https://flagcdn.com/w40/ca.png" alt="Canadá">
    <img src="https://flagcdn.com/w40/mx.png" alt="México">
    <img src="https://flagcdn.com/w40/us.png" alt="EE.UU.">
  </div>
</div>

<div class="container">

<!-- ══ SECCIÓN: VOTAR ══════════════════════════════════════════════════ -->
<section id="votar">
  <div class="section-title">🗳 Partidos para Votar</div>
  <div class="section-sub">Votaciones abiertas — cierre automático al vencer la fecha</div>

  <?php if(empty($votable)): ?>
    <div style="text-align:center; padding:40px; color:var(--muted); border:1px dashed var(--border); border-radius:var(--radius)">
      <div style="font-size:32px; margin-bottom:12px">⏳</div>
      <div style="font-size:15px; font-weight:600">No hay votaciones abiertas en este momento</div>
      <div style="font-size:13px; margin-top:6px">El administrador habilitará partidos para votar próximamente</div>
    </div>
  <?php else: ?>
    <div class="vote-grid">
    <?php foreach($votable as $m):
      $total    = (int)$m['total_votes'];
      $homePct  = pct((int)$m['home_votes'], $total);
      $awayPct  = 100 - $homePct;
      $userVote = $m['user_voted'];
      $votedHome = $userVote == $m['home_team_id'];
      $votedAway = $userVote == $m['away_team_id'];
    ?>
    <div class="vote-card">
      <!-- Meta -->
      <div class="meta">
        <?php if($m['group_name']): ?>
          <span class="group-badge">GRUPO <?= $m['group_name'] ?></span>
        <?php else: ?>
          <span class="group-badge"><?= htmlspecialchars($stageLabels[$m['stage']] ?? strtoupper($m['stage'])) ?></span>
        <?php endif; ?>
        <span class="timer">⏱ <?= timeLeft($m['vote_end']) ?></span>
      </div>

      <!-- Equipos -->
      <div class="matchup">
        <div class="team-side">
          <img src="https://flagcdn.com/w80/<?= $m['home_flag'] ?>.png"
               alt="<?= htmlspecialchars($m['home_name']) ?>"
               onerror="this.src='https://flagcdn.com/w80/un.png'">
          <div class="tname"><?= htmlspecialchars($m['home_name']) ?></div>
        </div>

        <div class="vs-block">
          <?php if($m['home_score'] !== null): ?>
            <div class="score"><?= $m['home_score'] ?> – <?= $m['away_score'] ?></div>
          <?php else: ?>
            <div class="vs">VS</div>
          <?php endif; ?>
        </div>

        <div class="team-side">
          <img src="https://flagcdn.com/w80/<?= $m['away_flag'] ?>.png"
               alt="<?= htmlspecialchars($m['away_name']) ?>"
               onerror="this.src='https://flagcdn.com/w80/un.png'">
          <div class="tname"><?= htmlspecialchars($m['away_name']) ?></div>
        </div>
      </div>

      <!-- Barra de votos -->
      <div class="vote-bar-wrap">
        <div class="vote-bar-labels">
          <span><b><?= $homePct ?>%</b> <?= htmlspecialchars($m['home_name']) ?></span>
          <span><?= htmlspecialchars($m['away_name']) ?> <b><?= $awayPct ?>%</b></span>
        </div>
        <div class="vote-bar">
          <div class="vote-bar-fill" style="width:<?= $homePct ?>%"></div>
        </div>
      </div>
      <div class="vote-total"><?= $total ?> voto<?= $total !== 1 ? 's' : '' ?> totales</div>

      <!-- Botones -->
      <?php if($user): ?>
        <?php if($userVote): ?>
          <div class="vote-btns">
            <div class="vote-btn <?= $votedHome ? 'voted-home' : '' ?>" style="cursor:default">
              <?= $votedHome ? '✓ ' : '' ?><?= htmlspecialchars($m['home_name']) ?>
              <div style="font-size:16px;font-weight:800"><?= $homePct ?>%</div>
            </div>
            <div class="vote-btn <?= $votedAway ? 'voted-away' : '' ?>" style="cursor:default">
              <?= $votedAway ? '✓ ' : '' ?><?= htmlspecialchars($m['away_name']) ?>
              <div style="font-size:16px;font-weight:800"><?= $awayPct ?>%</div>
            </div>
          </div>
          <div style="text-align:center;font-size:11px;color:var(--muted);margin-top:8px">
            ✅ Ya votaste en este partido
          </div>
        <?php else: ?>
          <form method="POST" action="api/vote.php" style="display:contents">
            <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="vote-btns">
              <button type="submit" name="team_id" value="<?= $m['home_team_id'] ?>" class="vote-btn left">
                <?= htmlspecialchars($m['home_name']) ?>
              </button>
              <button type="submit" name="team_id" value="<?= $m['away_team_id'] ?>" class="vote-btn right">
                <?= htmlspecialchars($m['away_name']) ?>
              </button>
            </div>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <div class="login-nudge">
          <a href="login.php">Iniciá sesión</a> para votar en este partido
        </div>
      <?php endif; ?>

      <!-- Fechas -->
      <div class="dates-row">
        <span>🟢 Inicio: <?= date('d/m H:i', strtotime($m['vote_start'])) ?></span>
        <span>🔴 Cierre: <?= date('d/m H:i', strtotime($m['vote_end'])) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ══ SECCIÓN: GRUPOS ════════════════════════════════════════════════ -->
<section id="grupos">
  <div class="section-title">🌍 Clasificación — Fase de Grupos</div>
  <div class="section-sub">Mundial 2026 · 12 grupos · 48 selecciones</div>
  <div class="groups-grid">
  <?php foreach($teamsByGroup as $groupLetter => $teams): ?>
    <div class="group-card">
      <div class="group-header">
        <div class="group-letter"><?= $groupLetter ?></div>
        <div class="group-title">Grupo <?= $groupLetter ?></div>
      </div>
      <table class="group-table">
        <thead>
          <tr>
            <th>Equipo</th>
            <th>PJ</th><th>G</th><th>E</th><th>P</th>
            <th>GF</th><th>GC</th><th>Pts</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($teams as $i => $team): ?>
          <tr>
            <td>
              <div class="team-cell">
                <?php if($i < 2): ?><span class="qualify-dot"></span><?php endif; ?>
                <img src="https://flagcdn.com/w40/<?= $team['flag_code'] ?>.png"
                     alt="<?= htmlspecialchars($team['name']) ?>"
                     onerror="this.src='https://flagcdn.com/w40/un.png'">
                <span><?= htmlspecialchars($team['name']) ?></span>
              </div>
            </td>
            <td>0</td><td>0</td><td>0</td><td>0</td>
            <td>0</td><td>0</td>
            <td style="font-weight:700; color:var(--gold)">0</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:16px;margin-top:14px;font-size:12px;color:var(--muted)">
    <span><span class="qualify-dot" style="display:inline-block"></span> Clasificado a siguiente fase</span>
  </div>
</section>

<!-- ══ SECCIÓN: ELIMINATORIAS ════════════════════════════════════════ -->
<section id="eliminatorias">
  <div class="section-title">⚡ Eliminatorias</div>
  <div class="section-sub">Dieciseisavos · Octavos · Cuartos · Semifinal · Final</div>

  <?php
  $bracketStages = ['round32', 'round16', 'quarterfinals', 'semifinals', 'final'];
  $bracketIcons  = ['round32'=>'32','round16'=>'16','quarterfinals'=>'⊕','semifinals'=>'★','final'=>'🏆'];
  $hasAny = false;
  foreach($bracketStages as $st) {
    if(!empty($matchesByStage[$st])) { $hasAny = true; break; }
  }
  ?>

  <?php if(!$hasAny): ?>
    <div style="text-align:center;padding:48px;color:var(--muted);border:1px dashed var(--border);border-radius:var(--radius)">
      <div style="font-size:36px;margin-bottom:14px">🏆</div>
      <div style="font-size:15px;font-weight:600">Las eliminatorias se publicarán tras la fase de grupos</div>
    </div>
  <?php else: ?>
    <?php foreach($bracketStages as $stage):
      if(empty($matchesByStage[$stage])) continue;
    ?>
    <div class="bracket-stage">
      <div class="bracket-label">
        <?= $bracketIcons[$stage] ?? '' ?> <?= $stageLabels[$stage] ?>
      </div>
      <div class="bracket-matches">
        <?php foreach($matchesByStage[$stage] as $m):
          $total   = (int)$m['home_votes'] + (int)$m['away_votes'];
          $homePct = pct((int)$m['home_votes'], $total);
          $awayPct = 100 - $homePct;
        ?>
        <div class="bracket-card">
          <div class="bracket-team">
            <img src="https://flagcdn.com/w40/<?= $m['home_flag'] ?>.png"
                 alt="<?= htmlspecialchars($m['home_name']) ?>"
                 onerror="this.src='https://flagcdn.com/w40/un.png'">
            <span class="bname"><?= htmlspecialchars($m['home_name']) ?></span>
            <span class="bscore"><?= $m['home_score'] ?? '—' ?></span>
            <span class="bpct"><?= $homePct ?>%</span>
          </div>
          <div class="bracket-team">
            <img src="https://flagcdn.com/w40/<?= $m['away_flag'] ?>.png"
                 alt="<?= htmlspecialchars($m['away_name']) ?>"
                 onerror="this.src='https://flagcdn.com/w40/un.png'">
            <span class="bname"><?= htmlspecialchars($m['away_name']) ?></span>
            <span class="bscore"><?= $m['away_score'] ?? '—' ?></span>
            <span class="bpct"><?= $awayPct ?>%</span>
          </div>
          <?php if($m['match_date']): ?>
          <div class="bracket-date">📅 <?= date('d/m/Y H:i', strtotime($m['match_date'])) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

</div><!-- /container -->

<footer>
  <p>⚽ FIFA World Cup 2026 — Canadá · México · Estados Unidos</p>
  <p style="margin-top:6px">Banderas vía <a href="https://flagcdn.com" style="color:var(--muted)">flagcdn.com</a> · Datos oficiales FIFA</p>
</footer>

</body>
</html>
