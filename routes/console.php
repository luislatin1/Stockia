<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\PluginInstaller;
use Stockia\DteSvMh\Services\DteEmissionService;
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

    $servicio = app(DteEmissionService::class);
    $processed = $servicio->resendContingency($companyId);

    $this->info("DTE transmitidos desde contingencia: {$processed}");
    foreach ($servicio->avisosContingencia() as $aviso) {
        $this->warn($aviso);
    }

    return $servicio->avisosContingencia() === [] ? 0 : 1;
})->purpose('Regulariza las contingencias: transmite el evento y, con su sello, el lote de documentos');

Artisan::command('dte:reintentar-pendientes {--company_id=}', function () {
    $companyId = $this->option('company_id');
    $n = app(DteEmissionService::class)->reintentarPendientes($companyId !== null ? (int) $companyId : null);

    $this->info("DTE pendientes que cambiaron de estado: {$n}");
    return 0;
})->purpose('Retransmite los DTE sin respuesta de Hacienda (consulta antes de reenviar)');

Artisan::command('dte:revisar-identificadores {--limit=10 : ejemplos a mostrar por grupo}', function () {
    // Solo lectura. Cuenta y lista NIT/NRC/DUI que DTE 2.0 rechazaría, para corregirlos antes de emitir.
    $limit = (int) $this->option('limit');
    $checks = [
        ['companies', 'nit', 'NIT', fn ($v, $r) => \App\Support\FiscalId::nitDigits($v)],
        ['companies', 'nrc', 'NRC', fn ($v, $r) => \App\Support\FiscalId::nrcDigits($v)],
        ['providers', 'nit', 'NIT', fn ($v, $r) => \App\Support\FiscalId::nitDigits($v)],
        ['providers', 'nrc', 'NRC', fn ($v, $r) => \App\Support\FiscalId::nrcDigits($v)],
        ['providers', 'dui', 'DUI', fn ($v, $r) => \App\Support\FiscalId::duiDigits($v)],
        ['customers', 'numero_documento', 'Documento', fn ($v, $r) => \App\Support\FiscalId::documentoParaDte($r->tipo_documento, $v)],
        ['customers', 'nrc', 'NRC', fn ($v, $r) => \App\Support\FiscalId::nrcDigits($v)],
    ];

    $rows = [];
    $detalle = [];
    foreach ($checks as [$table, $col, $label, $fn]) {
        $ok = $empty = $bad = 0;
        foreach (\Illuminate\Support\Facades\DB::table($table)->select('id', $col, ...($table === 'customers' ? ['tipo_documento'] : []))->get() as $r) {
            $v = trim((string) $r->$col);
            if ($v === '') { $empty++; continue; }
            if ($fn($v, $r) !== null) { $ok++; continue; }
            $bad++;
            if (count($detalle["$table.$col"] ?? []) < $limit) {
                $detalle["$table.$col"][] = "#{$r->id} '{$v}'";
            }
        }
        $rows[] = ["$table.$col", $label, $ok, $empty, $bad];
    }

    $this->table(['Campo', 'Tipo', 'Válidos', 'Vacíos', 'Inválidos'], $rows);
    foreach ($detalle as $campo => $ej) {
        $this->line("<comment>{$campo}</comment> inválidos (ejemplos): ".implode(', ', $ej));
    }

    return 0;
})->purpose('Reporta NIT/NRC/DUI mal formados que DTE 2.0 rechazaría (solo lectura)');
