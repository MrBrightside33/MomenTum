<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $response = $httpKernel->handle(\Illuminate\Http\Request::create('/projects', 'GET'));
    file_put_contents(__DIR__.'/debug_project_output.html', (string) $response->getContent());
    echo $response->getStatusCode(), "\n";
    echo substr((string) $response->getContent(), 0, 400), "\n";
} catch (Throwable $e) {
    echo get_class($e), "\n";
    echo $e->getMessage(), "\n";
    echo $e->getTraceAsString(), "\n";
}
