<?php
// includes/teams_data.php — Los 48 equipos oficiales del Mundial 2026
// Fuente: FIFA.com (datos del PDF oficial)

function getTeamsData(): array {
    return [
        // GRUPO A
        ['name' => 'México',                'flag_code' => 'mx',     'group_name' => 'A', 'fifa_name' => 'Mexico'],
        ['name' => 'Sudáfrica',             'flag_code' => 'za',     'group_name' => 'A', 'fifa_name' => 'South Africa'],
        ['name' => 'República de Corea',    'flag_code' => 'kr',     'group_name' => 'A', 'fifa_name' => 'Korea Republic'],
        ['name' => 'Chequia',               'flag_code' => 'cz',     'group_name' => 'A', 'fifa_name' => 'Czech Republic'],
        // GRUPO B
        ['name' => 'Canadá',                'flag_code' => 'ca',     'group_name' => 'B', 'fifa_name' => 'Canada'],
        ['name' => 'Bosnia y Herzegovina',  'flag_code' => 'ba',     'group_name' => 'B', 'fifa_name' => 'Bosnia & Herzegovina'],
        ['name' => 'Catar',                 'flag_code' => 'qa',     'group_name' => 'B', 'fifa_name' => 'Qatar'],
        ['name' => 'Suiza',                 'flag_code' => 'ch',     'group_name' => 'B', 'fifa_name' => 'Switzerland'],
        // GRUPO C
        ['name' => 'Brasil',                'flag_code' => 'br',     'group_name' => 'C', 'fifa_name' => 'Brazil'],
        ['name' => 'Marruecos',             'flag_code' => 'ma',     'group_name' => 'C', 'fifa_name' => 'Morocco'],
        ['name' => 'Haití',                 'flag_code' => 'ht',     'group_name' => 'C', 'fifa_name' => 'Haiti'],
        ['name' => 'Escocia',               'flag_code' => 'gb-sct', 'group_name' => 'C', 'fifa_name' => 'Scotland'],
        // GRUPO D
        ['name' => 'EE. UU.',               'flag_code' => 'us',     'group_name' => 'D', 'fifa_name' => 'USA'],
        ['name' => 'Paraguay',              'flag_code' => 'py',     'group_name' => 'D', 'fifa_name' => 'Paraguay'],
        ['name' => 'Australia',             'flag_code' => 'au',     'group_name' => 'D', 'fifa_name' => 'Australia'],
        ['name' => 'Turquía',               'flag_code' => 'tr',     'group_name' => 'D', 'fifa_name' => 'Türkiye'],
        // GRUPO E
        ['name' => 'Alemania',              'flag_code' => 'de',     'group_name' => 'E', 'fifa_name' => 'Germany'],
        ['name' => 'Curazao',               'flag_code' => 'cw',     'group_name' => 'E', 'fifa_name' => 'Curaçao'],
        ['name' => 'Costa de Marfil',       'flag_code' => 'ci',     'group_name' => 'E', 'fifa_name' => "Côte d'Ivoire"],
        ['name' => 'Ecuador',               'flag_code' => 'ec',     'group_name' => 'E', 'fifa_name' => 'Ecuador'],
        // GRUPO F
        ['name' => 'Países Bajos',          'flag_code' => 'nl',     'group_name' => 'F', 'fifa_name' => 'Netherlands'],
        ['name' => 'Japón',                 'flag_code' => 'jp',     'group_name' => 'F', 'fifa_name' => 'Japan'],
        ['name' => 'Suecia',                'flag_code' => 'se',     'group_name' => 'F', 'fifa_name' => 'Sweden'],
        ['name' => 'Túnez',                 'flag_code' => 'tn',     'group_name' => 'F', 'fifa_name' => 'Tunisia'],
        // GRUPO G
        ['name' => 'Bélgica',               'flag_code' => 'be',     'group_name' => 'G', 'fifa_name' => 'Belgium'],
        ['name' => 'Egipto',                'flag_code' => 'eg',     'group_name' => 'G', 'fifa_name' => 'Egypt'],
        ['name' => 'RI de Irán',            'flag_code' => 'ir',     'group_name' => 'G', 'fifa_name' => 'IR Iran'],
        ['name' => 'Nueva Zelanda',         'flag_code' => 'nz',     'group_name' => 'G', 'fifa_name' => 'New Zealand'],
        // GRUPO H
        ['name' => 'España',                'flag_code' => 'es',     'group_name' => 'H', 'fifa_name' => 'Spain'],
        ['name' => 'Islas de Cabo Verde',   'flag_code' => 'cv',     'group_name' => 'H', 'fifa_name' => 'Cabo Verde'],
        ['name' => 'Arabia Saudí',          'flag_code' => 'sa',     'group_name' => 'H', 'fifa_name' => 'Saudi Arabia'],
        ['name' => 'Uruguay',               'flag_code' => 'uy',     'group_name' => 'H', 'fifa_name' => 'Uruguay'],
        // GRUPO I
        ['name' => 'Francia',               'flag_code' => 'fr',     'group_name' => 'I', 'fifa_name' => 'France'],
        ['name' => 'Senegal',               'flag_code' => 'sn',     'group_name' => 'I', 'fifa_name' => 'Senegal'],
        ['name' => 'Irak',                  'flag_code' => 'iq',     'group_name' => 'I', 'fifa_name' => 'Iraq'],
        ['name' => 'Noruega',               'flag_code' => 'no',     'group_name' => 'I', 'fifa_name' => 'Norway'],
        // GRUPO J
        ['name' => 'Argentina',             'flag_code' => 'ar',     'group_name' => 'J', 'fifa_name' => 'Argentina'],
        ['name' => 'Argelia',               'flag_code' => 'dz',     'group_name' => 'J', 'fifa_name' => 'Algeria'],
        ['name' => 'Austria',               'flag_code' => 'at',     'group_name' => 'J', 'fifa_name' => 'Austria'],
        ['name' => 'Jordania',              'flag_code' => 'jo',     'group_name' => 'J', 'fifa_name' => 'Jordan'],
        // GRUPO K
        ['name' => 'Portugal',              'flag_code' => 'pt',     'group_name' => 'K', 'fifa_name' => 'Portugal'],
        ['name' => 'RD Congo',              'flag_code' => 'cd',     'group_name' => 'K', 'fifa_name' => 'DR Congo'],
        ['name' => 'Uzbekistán',            'flag_code' => 'uz',     'group_name' => 'K', 'fifa_name' => 'Uzbekistan'],
        ['name' => 'Colombia',              'flag_code' => 'co',     'group_name' => 'K', 'fifa_name' => 'Colombia'],
        // GRUPO L
        ['name' => 'Inglaterra',            'flag_code' => 'gb-eng', 'group_name' => 'L', 'fifa_name' => 'England'],
        ['name' => 'Croacia',               'flag_code' => 'hr',     'group_name' => 'L', 'fifa_name' => 'Croatia'],
        ['name' => 'Ghana',                 'flag_code' => 'gh',     'group_name' => 'L', 'fifa_name' => 'Ghana'],
        ['name' => 'Panamá',                'flag_code' => 'pa',     'group_name' => 'L', 'fifa_name' => 'Panama'],
    ];
}

function seedTeams(): void {
    $db = getDB();
    $count = $db->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    if ($count > 0) return;

    $stmt = $db->prepare("INSERT INTO teams (name, flag_code, group_name, fifa_name) VALUES (?, ?, ?, ?)");
    foreach (getTeamsData() as $team) {
        $stmt->execute([$team['name'], $team['flag_code'], $team['group_name'], $team['fifa_name']]);
    }
}

function seedAdmin(): void {
    $db = getDB();
    $exists = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    if ($exists) return;

    $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
       ->execute(['Admin', 'admin@worldcup2026.com', password_hash('admin2026', PASSWORD_DEFAULT)]);
}

function flagUrl(string $code, int $width = 80): string {
    return "https://flagcdn.com/w{$width}/{$code}.png";
}
