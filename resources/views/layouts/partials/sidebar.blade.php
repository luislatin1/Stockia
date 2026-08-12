@php
    $sections = config('sidebar.sections', []);
    $currentRole = currentRole();

    $matchesAnyRoute = static function (array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (is_string($pattern) && $pattern !== '' && request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };

    $canViewItem = static function (array $item) use ($currentRole): bool {
        if (isset($item['roles']) && is_array($item['roles']) && ! in_array($currentRole, $item['roles'], true)) {
            return false;
        }
        if (isset($item['can']) && auth()->check() && ! auth()->user()->can($item['can'])) {
            return false;
        }
        // Los ítems ligados a un módulo (requires_module) nunca se ocultan por
        // falta de ruta: si el módulo está apagado se muestran bloqueados en
        // vez de desaparecer (ver $item['locked'] más abajo).
        if (isset($item['requires_module'])) {
            return true;
        }
        if (isset($item['if_route']) && ! \Illuminate\Support\Facades\Route::has($item['if_route'])) {
            return false;
        }
        if (($item['type'] ?? 'link') === 'link' && isset($item['route']) && ! \Illuminate\Support\Facades\Route::has($item['route'])) {
            return false;
        }
        return true;
    };

    $visibleSections = [];
    foreach ($sections as $section) {
        $sectionItems = array_values(array_filter($section['items'] ?? [], $canViewItem));
        $sectionItems = array_map(static function (array $item): array {
            if (isset($item['requires_module'])) {
                $item['locked'] = ! \App\Models\Module::isEnabled($item['requires_module']);
                $item['module_label'] = \App\Models\Module::label($item['requires_module']);
            }
            return $item;
        }, $sectionItems);
        if ($sectionItems === []) {
            continue;
        }
        $sectionOpen = false;
        foreach ($sectionItems as $item) {
            $activePatterns = $item['active'] ?? [($item['route'] ?? '')];
            if ($matchesAnyRoute((array) $activePatterns)) {
                $sectionOpen = true;
                break;
            }
        }
        $section['items']   = $sectionItems;
        $section['is_open'] = $sectionOpen;
        $visibleSections[]  = $section;
    }
@endphp

<nav class="flex-1 px-3 py-4 space-y-4 text-sm overflow-y-auto">
    @foreach ($visibleSections as $section)
        @include('layouts.partials.sidebar-section', ['section' => $section])
    @endforeach
</nav>
