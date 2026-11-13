<?php
// TEMPORARY diagnostic file. Delete it after use.
header('Content-Type: text/plain');

$t0 = microtime(true);
$t  = $t0;
function lap(string $label, float &$t): void {
    $n = microtime(true);
    printf("%-32s %7.0f ms\n", $label, ($n - $t) * 1000);
    $t = $n;
}

$o = function_exists('opcache_get_status') ? opcache_get_status(false) : null;
echo 'opcache enabled (web): ' . var_export($o['opcache_enabled'] ?? false, true) . "\n";
echo 'validate_timestamps:   ' . ini_get('opcache.validate_timestamps') . "\n\n";

require __DIR__ . '/../vendor/autoload.php';
lap('vendor/autoload.php', $t);

try {
    $pdo = new PDO('mysql:host=db;dbname=task_manager', 'laravel', 'secret', [PDO::ATTR_TIMEOUT => 10]);
    lap('mysql connect', $t);
    $pdo->query('SELECT 1');
    lap('mysql query', $t);
} catch (Throwable $e) {
    echo 'DB error: ' . $e->getMessage() . "\n";
    lap('mysql failed after', $t);
}

$app = require __DIR__ . '/../bootstrap/app.php';
lap('bootstrap/app.php', $t);

$files = 0;
$s = microtime(true);
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app', FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->isFile()) { file_get_contents($f->getPathname()); $files++; }
}
printf("%-32s %7.0f ms\n", "read $files app files", (microtime(true) - $s) * 1000);

echo "\n";
lap('TOTAL', $t0);