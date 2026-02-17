<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PluginInstaller
{
    public function installFromZip(string $zipPath, ?string $targetFolder = null, bool $force = false): array
    {
        if (! File::exists($zipPath)) {
            throw new \RuntimeException("ZIP no encontrado: {$zipPath}");
        }

        $tmpRoot = storage_path('app/tmp/plugins/' . Str::uuid()->toString());
        File::makeDirectory($tmpRoot, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el ZIP.');
        }

        $zip->extractTo($tmpRoot);
        $zip->close();

        $root = $this->resolvePluginRoot($tmpRoot);
        $composerPath = $root . DIRECTORY_SEPARATOR . 'composer.json';

        if (! File::exists($composerPath)) {
            throw new \RuntimeException('El ZIP no contiene composer.json en la raíz del paquete.');
        }

        $composer = json_decode(File::get($composerPath), true);
        if (! is_array($composer) || empty($composer['name'])) {
            throw new \RuntimeException('composer.json inválido o sin "name".');
        }

        $packageName = $composer['name'];
        $moduleKey = $composer['extra']['stockia']['module_key'] ?? $this->slugFromPackage($packageName);
        $provider = $composer['extra']['laravel']['providers'][0] ?? null;
        $version = $composer['version'] ?? '0.1.0';
        $description = $composer['description'] ?? null;
        $slug = $targetFolder ?: $this->slugFromPackage($packageName);
        $targetDir = base_path('packages' . DIRECTORY_SEPARATOR . $slug);

        if (File::exists($targetDir)) {
            if (! $force) {
            throw new \RuntimeException("El destino ya existe: {$targetDir}");
            }
            File::deleteDirectory($targetDir);
        }

        File::makeDirectory(base_path('packages'), 0755, true, true);
        File::moveDirectory($root, $targetDir);
        File::deleteDirectory($tmpRoot);

        $this->updateRootComposer($packageName, "packages/{$slug}");

        return [
            'package' => $packageName,
            'path' => $targetDir,
            'slug' => $slug,
            'module_key' => $moduleKey,
            'provider' => $provider,
            'version' => $version,
            'description' => $description,
        ];
    }

    private function resolvePluginRoot(string $tmpRoot): string
    {
        $entries = collect(File::directories($tmpRoot));

        if ($entries->count() === 1 && ! File::exists($tmpRoot . DIRECTORY_SEPARATOR . 'composer.json')) {
            return $entries->first();
        }

        return $tmpRoot;
    }

    private function slugFromPackage(string $packageName): string
    {
        $parts = explode('/', $packageName);
        return end($parts) ?: Str::slug($packageName);
    }

    private function updateRootComposer(string $packageName, string $path): void
    {
        $composerFile = base_path('composer.json');
        $composer = json_decode(File::get($composerFile), true);

        $composer['repositories'] = $composer['repositories'] ?? [];
        $hasRepo = collect($composer['repositories'])->contains(function ($repo) use ($path) {
            return ($repo['type'] ?? null) === 'path' && ($repo['url'] ?? null) === $path;
        });

        if (! $hasRepo) {
            $composer['repositories'][] = [
                'type' => 'path',
                'url' => $path,
            ];
        }

        $composer['require'] = $composer['require'] ?? [];
        if (! array_key_exists($packageName, $composer['require'])) {
            $composer['require'][$packageName] = '*';
        }

        File::put($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
