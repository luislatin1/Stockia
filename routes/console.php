<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\PluginInstaller;
use App\Services\Dte\DteEmissionService;
use Illuminate\Support\Facades\File;

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

Artisan::command('modules:export {module} {--dest=}', function () {
    $module = $this->argument('module');
    $dest = $this->option('dest') ?: base_path('ExportPlugins');
    $sourceDir = base_path('packages' . DIRECTORY_SEPARATOR . $module);

    if (! File::isDirectory($sourceDir)) {
        $this->error("No existe el modulo en: {$sourceDir}");
        return 1;
    }

    File::makeDirectory($dest, 0755, true, true);
    $zipPath = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $module . '.zip';

    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        $this->error('No se pudo crear el ZIP.');
        return 1;
    }

    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        $zip->addFile($filePath, $relativePath);
    }

    $zip->close();

    $this->info("Plugin exportado en: {$zipPath}");
    return 0;
})->purpose('Exporta un plugin a ZIP en la carpeta indicada');

Artisan::command('dte:reenviar-contingencia {--company_id=}', function () {
    $companyId = $this->option('company_id');
    $companyId = $companyId !== null ? (int) $companyId : null;

    $processed = app(DteEmissionService::class)->resendContingency($companyId);

    $this->info("DTE reenviados desde contingencia: {$processed}");
    return 0;
})->purpose('Reenvía DTE en estado CONTINGENCIA usando el flujo configurado');
