<?php
/**
 * FEER Web – webowy instalator
 *
 * Umieść plik w katalogu public/ i otwórz w przeglądarce.
 * Zostanie automatycznie usunięty po zakończeniu instalacji.
 *
 * Wymagania: PHP 8.2+, rozszerzenia Laravel, Composer zainstalowany
 * (vendor/ musi istnieć lub zostanie uruchomiony composer install).
 */

declare(strict_types=1);

// ── klucz licencyjny ─────────────────────────────────────────────────────────
// Jednorazowy klucz dostępu do instalatora dla tej wersji wdrożenia.
// W przyszłości zastąpiony panelem licencji.
define('INSTALL_KEY', '123456');

// ── ochrona ──────────────────────────────────────────────────────────────────
$projectRoot = dirname(__DIR__);
$envFile     = $projectRoot . '/.env';

session_start();

// Sprawdź klucz — zanim cokolwiek innego
if (empty($_SESSION['licensed'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_key'])) {
        if (hash_equals(INSTALL_KEY, trim($_POST['install_key']))) {
            $_SESSION['licensed'] = true;
            header('Location: ?step=1'); exit;
        } else {
            $licenseError = 'Nieprawidłowy klucz licencyjny.';
        }
    }
    // Pokaż ekran klucza (przed _page, bo jeszcze nie zdefiniowane — inline)
    $err = isset($licenseError) ? '<p style="color:#c31432;margin-bottom:1rem">' . htmlspecialchars($licenseError, ENT_QUOTES, 'UTF-8') . '</p>' : '';
    echo <<<HTML
<!doctype html><html lang="pl"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FEER Web – Instalator</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#f0f4f8;color:#1a202c;min-height:100vh;display:grid;place-items:center;padding:1rem}
.box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:2.5rem;width:100%;max-width:420px}
.logo{font-size:1.4rem;font-weight:900;color:#c31432;margin-bottom:1.75rem}
.logo span{color:#1a202c;font-weight:400;font-size:.95rem;margin-left:.4rem}
label{display:block;font-size:.875rem;font-weight:600;margin-bottom:.35rem}
input{width:100%;padding:.55rem .75rem;border:1px solid #cbd5e0;border-radius:6px;font-size:1rem;font-family:inherit;outline:none;letter-spacing:.1em}
input:focus{border-color:#c31432;box-shadow:0 0 0 3px rgba(195,20,50,.12)}
.hint{font-size:.775rem;color:#718096;margin-top:.35rem}
.btn{display:block;width:100%;margin-top:1.25rem;background:#c31432;color:#fff;border:none;border-radius:8px;padding:.65rem;font-size:.95rem;font-weight:700;cursor:pointer}
.btn:hover{background:#a01027}
</style>
</head><body>
<div class="box">
    <p class="logo">FEER<span>Instalator</span></p>
    <p style="font-size:.875rem;color:#718096;margin-bottom:1.5rem">
        Podaj klucz licencyjny, aby uruchomić instalację.
    </p>
    $err
    <form method="POST" action="">
        <label for="ik">Klucz licencyjny</label>
        <input type="text" id="ik" name="install_key" autocomplete="off" autofocus
            placeholder="••••••" required>
        <p class="hint">Klucz jednorazowy dla tej wersji wdrożenia.</p>
        <button type="submit" class="btn">Weryfikuj i przejdź do instalacji</button>
    </form>
</div>
</body></html>
HTML;
    exit;
}

// Jeśli app już skonfigurowana (klucz ustawiony) – blokuj instalator
if (file_exists($envFile) && preg_match('/^APP_KEY=base64:/m', (string) file_get_contents($envFile))) {
    if (empty($_GET['reinstall'])) {
        http_response_code(403);
        echo '<p style="font-family:system-ui;padding:2rem">Aplikacja już zainstalowana. Usuń <code>public/install.php</code> z serwera. <a href="/">← Wróć do aplikacji</a></p>';
        exit;
    }
}

// ── stałe ────────────────────────────────────────────────────────────────────
define('ROOT',       $projectRoot);
define('ENV_FILE',   $envFile);
define('ENV_EXAMPLE', $projectRoot . '/.env.example');
define('ARTISAN',    $projectRoot . '/artisan');

$STEPS = [
    1 => 'Wymagania',
    2 => 'Baza danych',
    3 => 'Ustawienia',
    4 => 'Instalacja',
    5 => 'Gotowe',
];

$step = max(1, min(5, (int) ($_GET['step'] ?? 1)));

// Wymagane rozszerzenia PHP
$REQUIRED_EXT = ['bcmath','ctype','curl','fileinfo','json','mbstring','openssl','pdo','tokenizer','xml'];

// ── funkcje pomocnicze ───────────────────────────────────────────────────────

function env_read(): array {
    if (!file_exists(ENV_FILE)) return [];
    $out = [];
    foreach (file(ENV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $out[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
    return $out;
}

function env_write(array $values): void {
    $content = file_exists(ENV_FILE) ? file_get_contents(ENV_FILE) : file_get_contents(ENV_EXAMPLE);
    foreach ($values as $key => $val) {
        $val = (string) $val;
        // Jeśli wartość zawiera spacje lub specjalne znaki – otocz cudzysłowami
        $quoted = preg_match('/[\s#$"\'\\\\]/', $val) ? '"' . addslashes($val) . '"' : $val;
        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$quoted}", $content);
        } else {
            $content .= "\n{$key}={$quoted}";
        }
    }
    file_put_contents(ENV_FILE, $content);
}

function run(string $cmd, ?string $cwd = null): array {
    $cwd  = $cwd ?? ROOT;
    $desc = [1 => ['pipe','w'], 2 => ['pipe','w']];
    $proc = proc_open($cmd, $desc, $pipes, $cwd, null);
    if (!is_resource($proc)) return ['', 'Nie można uruchomić procesu.', -1];
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($proc);
    return [$out, $err, $code];
}

function php_bin(): string {
    static $bin = null;
    if ($bin) return $bin;
    foreach (['php85','php8.5','php8.4','php8.3','php8.2','php'] as $c) {
        $p = trim((string) shell_exec("which $c 2>/dev/null"));
        if ($p) {
            $ver = (int) shell_exec("$p -r 'echo PHP_VERSION_ID;'");
            if ($ver >= 80200) { $bin = $p; return $bin; }
        }
    }
    return 'php';
}

function composer_bin(): string {
    foreach (['composer','composer.phar'] as $c) {
        $p = trim((string) shell_exec("which $c 2>/dev/null"));
        if ($p) return $p;
    }
    return 'composer';
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_ok(): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '');
}

// ── step handlers ────────────────────────────────────────────────────────────

function handle_step2(): ?string {
    if (!csrf_ok()) return 'Błąd CSRF.';
    $_SESSION['db'] = [
        'type' => $_POST['db_type'] ?? 'sqlite',
        'host' => $_POST['db_host'] ?? '127.0.0.1',
        'port' => $_POST['db_port'] ?? '3306',
        'name' => $_POST['db_name'] ?? 'feer_web',
        'user' => $_POST['db_user'] ?? 'root',
        'pass' => $_POST['db_pass'] ?? '',
    ];
    if ($_SESSION['db']['type'] === 'mysql') {
        try {
            $dsn = "mysql:host={$_SESSION['db']['host']};port={$_SESSION['db']['port']}";
            $pdo = new PDO($dsn, $_SESSION['db']['user'], $_SESSION['db']['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['db']['name']}`
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            return 'Błąd połączenia z MySQL: ' . h($e->getMessage());
        }
    }
    return null;
}

function handle_step3(): ?string {
    if (!csrf_ok()) return 'Błąd CSRF.';
    $_SESSION['cfg'] = [
        'app_name' => $_POST['app_name'] ?? 'FEER Web',
        'app_url'  => rtrim($_POST['app_url'] ?? 'http://localhost', '/'),
        'app_env'  => ($_POST['app_env'] ?? 'local') === 'production' ? 'production' : 'local',
        'seed'     => !empty($_POST['seed']),
        'blog_db'  => !empty($_POST['blog_db']),
    ];
    return null;
}

function run_installation(): array {
    $log = [];
    $php  = php_bin();
    $comp = composer_bin();
    $db   = $_SESSION['db']  ?? ['type' => 'sqlite'];
    $cfg  = $_SESSION['cfg'] ?? [];

    // 1. Skopiuj .env.example → .env
    if (!file_exists(ENV_FILE)) {
        if (!copy(ENV_EXAMPLE, ENV_FILE)) {
            return [false, [['err', 'Nie można skopiować .env.example → .env']]];
        }
    }
    $log[] = ['ok', 'Plik .env przygotowany'];

    // 2. Zapisz konfigurację do .env
    $envVals = [
        'APP_NAME'  => $cfg['app_name'] ?? 'FEER Web',
        'APP_URL'   => $cfg['app_url']  ?? 'http://localhost',
        'APP_ENV'   => $cfg['app_env']  ?? 'local',
        'APP_DEBUG' => ($cfg['app_env'] ?? 'local') === 'production' ? 'false' : 'true',
    ];
    if ($db['type'] === 'mysql') {
        $envVals += [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $db['host'],
            'DB_PORT'       => $db['port'],
            'DB_DATABASE'   => $db['name'],
            'DB_USERNAME'   => $db['user'],
            'DB_PASSWORD'   => $db['pass'],
        ];
    } else {
        $sqlitePath = ROOT . '/database/database.sqlite';
        if (!file_exists($sqlitePath)) touch($sqlitePath);
        $envVals['DB_CONNECTION'] = 'sqlite';
    }
    if (!empty($cfg['blog_db'])) {
        $blogPath = ROOT . '/database/blog.sqlite';
        if (!file_exists($blogPath)) touch($blogPath);
        $envVals['BLOG_DB_DATABASE'] = $blogPath;
    }
    env_write($envVals);
    $log[] = ['ok', 'Konfiguracja zapisana w .env'];

    // 3. Composer install (jeśli vendor/ nie istnieje)
    if (!is_dir(ROOT . '/vendor')) {
        [$out, $err, $code] = run("$comp install --no-dev --optimize-autoloader --no-interaction 2>&1");
        if ($code !== 0) return [false, array_merge($log, [['err', 'composer install: ' . trim($err ?: $out)]])];
        $log[] = ['ok', 'Zależności PHP zainstalowane (Composer)'];
    } else {
        $log[] = ['ok', 'vendor/ już istnieje — pomijam composer install'];
    }

    // 4. Klucz aplikacji
    [$out, $err, $code] = run("$php artisan key:generate --force 2>&1");
    if ($code !== 0) return [false, array_merge($log, [['err', 'key:generate: ' . trim($err ?: $out)]])];
    $log[] = ['ok', 'Klucz aplikacji wygenerowany'];

    // 5. Migracje
    [$out, $err, $code] = run("$php artisan migrate --force 2>&1");
    if ($code !== 0) return [false, array_merge($log, [['err', 'migrate: ' . trim($err ?: $out)]])];
    $log[] = ['ok', 'Migracje bazy danych wykonane'];

    // Migracje bloga (opcjonalne)
    if (!empty($cfg['blog_db'])) {
        [$out, $err, $code] = run("$php artisan migrate --database=blog --path=database/migrations/blog --force 2>&1");
        $log[] = $code === 0
            ? ['ok', 'Migracje bloga wykonane']
            : ['warn', 'Migracje bloga: ' . trim($err ?: $out)];
    }

    // 6. Storage link
    [$out, $err, $code] = run("$php artisan storage:link --force 2>&1");
    $log[] = $code === 0 ? ['ok', 'Symlink storage/public utworzony'] : ['warn', 'storage:link: ' . trim($err ?: $out)];

    // 7. Seeder (opcjonalnie)
    if (!empty($cfg['seed'])) {
        [$out, $err, $code] = run("$php artisan db:seed --force 2>&1");
        if ($code !== 0) return [false, array_merge($log, [['err', 'db:seed: ' . trim($err ?: $out)]])];
        $log[] = ['ok', 'Dane demo załadowane'];
    }

    // 8. Cache konfiguracji na produkcji
    if (($cfg['app_env'] ?? '') === 'production') {
        run("$php artisan config:cache 2>&1");
        run("$php artisan route:cache 2>&1");
        run("$php artisan view:cache 2>&1");
        $log[] = ['ok', 'Cache konfiguracji/tras/widoków wygenerowany'];
    }

    return [true, $log];
}

// ── POST handlers ─────────────────────────────────────────────────────────────

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'step2':
            $error = handle_step2();
            if (!$error) { header('Location: ?step=3'); exit; }
            break;
        case 'step3':
            $error = handle_step3();
            if (!$error) { header('Location: ?step=4'); exit; }
            break;
        case 'install':
            if (!csrf_ok()) { $error = 'Błąd CSRF.'; break; }
            [$ok, $log] = run_installation();
            $_SESSION['install_log'] = $log;
            $_SESSION['install_ok']  = $ok;
            if ($ok) { header('Location: ?step=5'); exit; }
            $error = 'Instalacja nie powiodła się. Sprawdź log poniżej.';
            break;
        case 'delete_self':
            if (!csrf_ok()) break;
            @unlink(__FILE__);
            $url = env_read()['APP_URL'] ?? '/';
            header("Location: $url/admin"); exit;
    }
}

// ── HTML helper ───────────────────────────────────────────────────────────────

function _page(string $title, string $body): void {
    global $STEPS, $step;
    $activeStep = $step;
    $stepsHtml  = '';
    foreach ($STEPS as $n => $label) {
        $cls = $n === $activeStep ? 'active' : ($n < $activeStep ? 'done' : '');
        $stepsHtml .= "<li class=\"step-item $cls\"><span class=\"step-num\">$n</span><span class=\"step-label\">$label</span></li>";
    }
    echo <<<HTML
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FEER Web – Instalator</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, sans-serif; background: #f0f4f8; color: #1a202c; min-height: 100vh; }
.wrap { max-width: 680px; margin: 0 auto; padding: 2rem 1rem 4rem; }
.logo { font-size: 1.5rem; font-weight: 900; color: #c31432; letter-spacing: -.02em; margin-bottom: 2rem; }
.logo span { color: #1a202c; font-weight: 400; font-size: 1rem; margin-left: .5rem; }
.steps { display: flex; gap: 0; margin-bottom: 2rem; list-style: none; }
.step-item { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
.step-item:not(:last-child)::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 2px; background: #cbd5e0; z-index: 0; }
.step-item.done:not(:last-child)::after { background: #c31432; }
.step-num { width: 28px; height: 28px; border-radius: 50%; background: #cbd5e0; color: #718096; font-weight: 700; font-size: .8rem; display: grid; place-items: center; position: relative; z-index: 1; }
.step-item.active .step-num { background: #c31432; color: #fff; }
.step-item.done .step-num { background: #c31432; color: #fff; }
.step-item.done .step-num::before { content: '✓'; }
.step-item.done .step-num { font-size: 0; }
.step-item.done .step-num::before { font-size: .75rem; }
.step-label { font-size: .7rem; margin-top: .35rem; color: #718096; }
.step-item.active .step-label { color: #c31432; font-weight: 700; }
.card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; }
h2 { font-size: 1.25rem; margin-bottom: 1.25rem; }
h3 { font-size: 1rem; margin: 1.25rem 0 .5rem; }
label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: .35rem; margin-top: 1rem; }
label:first-of-type { margin-top: 0; }
input[type=text], input[type=url], input[type=password], select {
    width: 100%; padding: .5rem .75rem; border: 1px solid #cbd5e0; border-radius: 6px;
    font-size: .9rem; font-family: inherit; color: inherit;
    transition: border .15s; outline: none;
}
input:focus, select:focus { border-color: #c31432; box-shadow: 0 0 0 3px rgba(195,20,50,.12); }
.radio-group { display: flex; gap: 1rem; margin-bottom: 1rem; }
.radio-group label { display: flex; align-items: center; gap: .5rem; cursor: pointer; font-weight: 500; margin-top: 0; }
.radio-group input { width: auto; }
.hint { font-size: .775rem; color: #718096; margin-top: .25rem; }
.btn { display: inline-block; background: #c31432; color: #fff; border: none; border-radius: 8px;
    padding: .6rem 1.5rem; font-size: .95rem; font-weight: 700; cursor: pointer; text-decoration: none;
    transition: background .15s; }
.btn:hover { background: #a01027; }
.btn-sec { background: #e2e8f0; color: #4a5568; }
.btn-sec:hover { background: #cbd5e0; }
.actions { margin-top: 1.75rem; display: flex; gap: .75rem; align-items: center; }
.ok { color: #276749; }
.warn { color: #b7791f; }
.err { color: #c31432; }
.check-list { list-style: none; display: grid; gap: .5rem; margin-bottom: 1rem; }
.check-list li { display: flex; align-items: center; gap: .5rem; font-size: .9rem; padding: .4rem .75rem;
    border-radius: 6px; background: #f7fafc; border: 1px solid #e2e8f0; }
.check-list .pass { background: #f0fff4; border-color: #9ae6b4; }
.check-list .fail { background: #fff5f5; border-color: #fed7d7; }
.check-list .note { background: #fffbeb; border-color: #fbd38d; }
.icon { font-size: 1rem; }
.log-list { list-style: none; display: grid; gap: .4rem; margin-bottom: 1.25rem; }
.log-list li { display: flex; gap: .5rem; align-items: flex-start; font-size: .875rem;
    padding: .4rem .75rem; border-radius: 6px; }
.log-list .ok  { background: #f0fff4; }
.log-list .err { background: #fff5f5; }
.log-list .warn{ background: #fffbeb; }
.mysql-fields { display: none; }
.section-sep { border: none; border-top: 1px solid #e2e8f0; margin: 1.25rem 0; }
.demo-accounts { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; font-size: .85rem; margin-top: 1rem; }
.demo-accounts code { background: #e2e8f0; padding: .1em .4em; border-radius: 4px; font-family: monospace; }
@media (max-width: 480px) {
    .step-label { display: none; }
    .card { padding: 1.25rem; }
}
</style>
</head>
<body>
<div class="wrap">
    <p class="logo">FEER<span>Instalator</span></p>
    <ol class="steps" aria-label="Postęp instalacji">$stepsHtml</ol>
    $body
</div>
<script>
document.querySelectorAll('input[name="db_type"]').forEach(function(r){
    r.addEventListener('change', function(){
        document.querySelector('.mysql-fields').style.display = this.value === 'mysql' ? 'block' : 'none';
    });
});
var checked = document.querySelector('input[name="db_type"]:checked');
if (checked && checked.value === 'mysql') document.querySelector('.mysql-fields').style.display = 'block';
</script>
</body></html>
HTML;
}

// ═══════════════════════════════════════════════════════════════════════════════
// KROKI
// ═══════════════════════════════════════════════════════════════════════════════

// ── Krok 1: Wymagania ─────────────────────────────────────────────────────────
if ($step === 1) {
    $checks  = [];
    $allPass = true;

    // PHP version
    $phpOk = PHP_VERSION_ID >= 80200;
    $allPass = $allPass && $phpOk;
    $checks[] = [$phpOk ? 'pass' : 'fail', $phpOk ? '✓' : '✗', 'PHP ' . PHP_VERSION . ($phpOk ? '' : ' (wymagane 8.2+)')];

    // Extensions
    foreach ($REQUIRED_EXT as $ext) {
        $ok = extension_loaded($ext);
        $allPass = $allPass && $ok;
        $checks[] = [$ok ? 'pass' : 'fail', $ok ? '✓' : '✗', "Rozszerzenie PHP: $ext"];
    }

    // PDO drivers
    $hasSqlite = extension_loaded('pdo_sqlite');
    $hasMysql  = extension_loaded('pdo_mysql');
    $anyPdo    = $hasSqlite || $hasMysql;
    $allPass   = $allPass && $anyPdo;
    $pdo_label = trim(($hasSqlite ? 'SQLite ' : '') . ($hasMysql ? 'MySQL' : ''));
    $checks[] = [$anyPdo ? 'pass' : 'fail', $anyPdo ? '✓' : '✗', "PDO: $pdo_label" . ($anyPdo ? ' dostępne' : ' — brak sterowników PDO')];

    // .env.example
    $exOk = file_exists(ENV_EXAMPLE);
    $allPass = $allPass && $exOk;
    $checks[] = [$exOk ? 'pass' : 'fail', $exOk ? '✓' : '✗', '.env.example istnieje'];

    // artisan
    $artOk = file_exists(ARTISAN);
    $allPass = $allPass && $artOk;
    $checks[] = [$artOk ? 'pass' : 'fail', $artOk ? '✓' : '✗', 'artisan znaleziony (' . ROOT . ')'];

    // Uprawnienia zapisu
    foreach ([ROOT . '/storage', ROOT . '/bootstrap/cache'] as $dir) {
        $wrOk = is_writable($dir);
        $allPass = $allPass && $wrOk;
        $rel = str_replace(ROOT . '/', '', $dir);
        $checks[] = [$wrOk ? 'pass' : 'fail', $wrOk ? '✓' : '✗',
            $wrOk ? "$rel — zapis dozwolony" : "$rel — brak uprawnień do zapisu (chmod 775)"];
    }

    // proc_open / exec
    $procOk = function_exists('proc_open');
    $allPass = $allPass && $procOk;
    $checks[] = [$procOk ? 'pass' : 'fail', $procOk ? '✓' : '✗',
        $procOk ? 'proc_open dostępne' : 'proc_open wyłączone (wymagane do uruchomienia artisan)'];

    $liItems = '';
    foreach ($checks as [$cls, $ico, $txt]) {
        $liItems .= "<li class=\"$cls\"><span class=\"icon\">$ico</span> $txt</li>";
    }

    $btnNext = $allPass
        ? '<a href="?step=2" class="btn">Dalej →</a>'
        : '<span class="btn btn-sec" style="cursor:not-allowed;opacity:.6">Dalej →</span>';
    $statusMsg = $allPass
        ? '<p class="ok" style="margin-bottom:1rem">✓ Wszystkie wymagania spełnione.</p>'
        : '<p class="err" style="margin-bottom:1rem">✗ Niektóre wymagania nie są spełnione. Napraw je przed kontynuacją.</p>';

    ob_start();
    ?>
    <div class="card">
        <h2>Sprawdzanie wymagań systemowych</h2>
        <?= $statusMsg ?>
        <ul class="check-list"><?= $liItems ?></ul>
        <div class="actions"><?= $btnNext ?></div>
    </div>
    <?php
    _page('Wymagania', ob_get_clean()); exit;
}

// ── Krok 2: Baza danych ───────────────────────────────────────────────────────
if ($step === 2) {
    $db   = $_SESSION['db'] ?? [];
    $hasMysql = extension_loaded('pdo_mysql');
    ob_start();
    ?>
    <div class="card">
        <h2>Konfiguracja bazy danych</h2>
        <?php if ($error): ?><p class="err" style="margin-bottom:1rem"><?= h($error) ?></p><?php endif ?>

        <form method="POST" action="?step=2">
            <input type="hidden" name="_csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="step2">

            <label>Typ bazy danych</label>
            <div class="radio-group">
                <label><input type="radio" name="db_type" value="sqlite"
                    <?= ($db['type'] ?? 'sqlite') === 'sqlite' ? 'checked' : '' ?>> SQLite</label>
                <?php if ($hasMysql): ?>
                <label><input type="radio" name="db_type" value="mysql"
                    <?= ($db['type'] ?? '') === 'mysql' ? 'checked' : '' ?>> MySQL / MariaDB</label>
                <?php else: ?>
                <label style="opacity:.5" title="Rozszerzenie pdo_mysql niedostępne">
                    <input type="radio" disabled> MySQL (niedostępne)
                </label>
                <?php endif ?>
            </div>
            <p class="hint">SQLite – zalecane lokalnie i dla małych instalacji. MySQL – zalecane na produkcji.</p>

            <div class="mysql-fields">
                <hr class="section-sep">
                <h3>Dane połączenia MySQL</h3>
                <label for="db_host">Host</label>
                <input type="text" id="db_host" name="db_host" value="<?= h($db['host'] ?? '127.0.0.1') ?>">

                <label for="db_port">Port</label>
                <input type="text" id="db_port" name="db_port" value="<?= h($db['port'] ?? '3306') ?>" style="max-width:120px">

                <label for="db_name">Nazwa bazy danych</label>
                <input type="text" id="db_name" name="db_name" value="<?= h($db['name'] ?? 'feer_web') ?>">
                <p class="hint">Baza zostanie automatycznie utworzona, jeśli nie istnieje.</p>

                <label for="db_user">Użytkownik</label>
                <input type="text" id="db_user" name="db_user" value="<?= h($db['user'] ?? 'root') ?>">

                <label for="db_pass">Hasło</label>
                <input type="password" id="db_pass" name="db_pass" value="<?= h($db['pass'] ?? '') ?>" autocomplete="current-password">
            </div>

            <div class="actions">
                <a href="?step=1" class="btn btn-sec">← Wróć</a>
                <button type="submit" class="btn">Dalej →</button>
            </div>
        </form>
    </div>
    <?php
    _page('Baza danych', ob_get_clean()); exit;
}

// ── Krok 3: Ustawienia aplikacji ──────────────────────────────────────────────
if ($step === 3) {
    $cfg = $_SESSION['cfg'] ?? [];
    ob_start();
    ?>
    <div class="card">
        <h2>Ustawienia aplikacji</h2>
        <?php if ($error): ?><p class="err" style="margin-bottom:1rem"><?= h($error) ?></p><?php endif ?>

        <form method="POST" action="?step=3">
            <input type="hidden" name="_csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="step3">

            <label for="app_name">Nazwa organizacji / aplikacji</label>
            <input type="text" id="app_name" name="app_name"
                value="<?= h($cfg['app_name'] ?? 'FEER Web') ?>" required>
            <p class="hint">Wyświetlana w panelu, tytułach stron i stopce.</p>

            <label for="app_url">URL aplikacji</label>
            <input type="url" id="app_url" name="app_url"
                value="<?= h($cfg['app_url'] ?? 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?>"
                placeholder="https://twojadomena.pl" required>
            <p class="hint">Bez ukośnika na końcu. Używane do generowania linków.</p>

            <label for="app_env">Środowisko</label>
            <select id="app_env" name="app_env">
                <option value="local"      <?= ($cfg['app_env'] ?? 'local') === 'local'      ? 'selected' : '' ?>>local — lokalna instalacja / deweloperska</option>
                <option value="production" <?= ($cfg['app_env'] ?? '') === 'production' ? 'selected' : '' ?>>production — serwer produkcyjny</option>
            </select>
            <p class="hint">Na produkcji włączony cache konfiguracji i wyłączony tryb debug.</p>

            <hr class="section-sep">
            <h3>Dane startowe</h3>

            <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;cursor:pointer">
                <input type="checkbox" name="seed" value="1" style="width:auto"
                    <?= !empty($cfg['seed']) ? 'checked' : '' ?>>
                Załaduj przykładowe dane demo
            </label>
            <p class="hint" style="margin-left:1.5rem">Tworzy konta demo, ustawienia organizacji, przykładowe dokumenty BIP, FAQ i inne.</p>

            <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;cursor:pointer;margin-top:.75rem">
                <input type="checkbox" name="blog_db" value="1" style="width:auto"
                    <?= !empty($cfg['blog_db']) ? 'checked' : '' ?>>
                Utwórz bazę SQLite dla blogu "Wiem FEER"
            </label>
            <p class="hint" style="margin-left:1.5rem">Osobna baza danych dla bloga — możesz dodać później.</p>

            <div class="actions">
                <a href="?step=2" class="btn btn-sec">← Wróć</a>
                <button type="submit" class="btn">Dalej →</button>
            </div>
        </form>
    </div>
    <?php
    _page('Ustawienia', ob_get_clean()); exit;
}

// ── Krok 4: Instalacja ────────────────────────────────────────────────────────
if ($step === 4) {
    // Sprawdź czy mamy dane z poprzednich kroków
    if (empty($_SESSION['db']) || empty($_SESSION['cfg'])) {
        header('Location: ?step=2'); exit;
    }

    $cfg = $_SESSION['cfg'];
    $db  = $_SESSION['db'];
    ob_start();
    ?>
    <div class="card">
        <h2>Podsumowanie i instalacja</h2>
        <?php if ($error): ?><p class="err" style="margin-bottom:1rem"><?= h($error) ?></p><?php endif ?>

        <?php if (isset($_SESSION['install_log'])): ?>
            <ul class="log-list">
            <?php foreach ($_SESSION['install_log'] as [$type, $msg]): ?>
                <li class="<?= $type ?>"><span><?= $type === 'ok' ? '✓' : ($type === 'warn' ? '!' : '✗') ?></span> <?= h($msg) ?></li>
            <?php endforeach ?>
            </ul>
        <?php endif ?>

        <table style="font-size:.875rem;border-collapse:collapse;width:100%;margin-bottom:1.25rem">
            <tr><td style="padding:.3rem .5rem;color:#718096;white-space:nowrap">Nazwa aplikacji</td><td style="padding:.3rem .5rem;font-weight:600"><?= h($cfg['app_name']) ?></td></tr>
            <tr><td style="padding:.3rem .5rem;color:#718096">URL</td><td style="padding:.3rem .5rem"><?= h($cfg['app_url']) ?></td></tr>
            <tr><td style="padding:.3rem .5rem;color:#718096">Środowisko</td><td style="padding:.3rem .5rem"><?= h($cfg['app_env']) ?></td></tr>
            <tr><td style="padding:.3rem .5rem;color:#718096">Baza danych</td><td style="padding:.3rem .5rem"><?= $db['type'] === 'mysql' ? h("MySQL — {$db['host']}/{$db['name']}") : 'SQLite' ?></td></tr>
            <tr><td style="padding:.3rem .5rem;color:#718096">Dane demo</td><td style="padding:.3rem .5rem"><?= $cfg['seed'] ? 'Tak' : 'Nie' ?></td></tr>
        </table>

        <p style="font-size:.875rem;color:#718096;margin-bottom:1.25rem">
            Kliknij <strong>Zainstaluj</strong>, aby uruchomić proces. Może to potrwać kilkanaście sekund.
        </p>

        <form method="POST" action="?step=4">
            <input type="hidden" name="_csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="install">
            <div class="actions">
                <a href="?step=3" class="btn btn-sec">← Wróć</a>
                <button type="submit" class="btn">Zainstaluj</button>
            </div>
        </form>
    </div>
    <?php
    _page('Instalacja', ob_get_clean()); exit;
}

// ── Krok 5: Gotowe ────────────────────────────────────────────────────────────
if ($step === 5) {
    $cfg       = $_SESSION['cfg'] ?? [];
    $log       = $_SESSION['install_log'] ?? [];
    $seedDone  = $cfg['seed'] ?? false;
    $adminUrl  = rtrim($cfg['app_url'] ?? '', '/') . '/admin';

    ob_start();
    ?>
    <div class="card">
        <h2 style="color:#276749">✓ Instalacja zakończona!</h2>
        <p style="margin-bottom:1.25rem">FEER Web został pomyślnie zainstalowany i skonfigurowany.</p>

        <?php if ($log): ?>
        <ul class="log-list">
        <?php foreach ($log as [$type, $msg]): ?>
            <li class="<?= $type ?>"><span><?= $type === 'ok' ? '✓' : ($type === 'warn' ? '!' : '✗') ?></span> <?= h($msg) ?></li>
        <?php endforeach ?>
        </ul>
        <?php endif ?>

        <?php if ($seedDone): ?>
        <div class="demo-accounts">
            <strong>Konta demo</strong> (hasło: <code>demo12(@</code>):<br>
            <code>admin@demo.feer.org.pl</code> — Administrator<br>
            <code>redaktor@demo.feer.org.pl</code> — Edytor<br>
            <code>bip@demo.feer.org.pl</code> — Edytor BIP
        </div>
        <?php endif ?>

        <p style="margin-top:1.25rem;font-size:.875rem;background:#fff5f5;border:1px solid #fed7d7;border-radius:8px;padding:.75rem">
            <strong>⚠ Ważne:</strong> Usuń plik <code>install.php</code> z serwera — pozostawienie go stanowi zagrożenie bezpieczeństwa.
        </p>

        <div class="actions" style="margin-top:1.25rem;flex-wrap:wrap">
            <a href="<?= h($adminUrl) ?>" class="btn">Przejdź do panelu →</a>
            <form method="POST" action="?step=5" style="margin:0">
                <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="delete_self">
                <button type="submit" class="btn btn-sec"
                    onclick="return confirm('Usunąć plik install.php z serwera?')">
                    Usuń install.php i przejdź do panelu
                </button>
            </form>
        </div>
    </div>
    <?php
    // Wyczyść sesję instalatora
    unset($_SESSION['db'], $_SESSION['cfg'], $_SESSION['install_log'], $_SESSION['install_ok'], $_SESSION['csrf']);
    _page('Gotowe', ob_get_clean()); exit;
}
