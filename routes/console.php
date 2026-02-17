<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\PluginInstaller;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('modules:install-zip {zip} {--key=} ', function () {
    $zip = $this->argument('zip');
    $key = $this->option('key');

    $installer = app(PluginInstaller::class);

    try {
        $result = $installer->installFromZip($zip, $key);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info("Plugin extraído en: {$result['path']}");
    $this->info("Paquete: {$result['package']}");
    $this->line('Siguiente pasos:');
    $this->line('1. composer update ' . $result['package']);
    $this->line('2. php artisan migrate');
    $this->line('3. Activar módulo en Configuración > Panel de Control');

    return 0;
})->purpose('Instala un plugin desde ZIP y prepara composer.json');
