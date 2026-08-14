<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\CompanyUserPermission;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPermissionController extends Controller
{
    private function currentCompanyId(): int
    {
        return (int) session('current_company_id');
    }

    private function findCompanyUser(User $user): CompanyUser
    {
        $companyUser = CompanyUser::where('company_id', $this->currentCompanyId())
            ->where('user_id', $user->id)
            ->firstOrFail();

        return $companyUser;
    }

    public function edit(User $user)
    {
        $companyUser = $this->findCompanyUser($user);

        $overrides = CompanyUserPermission::where('company_user_id', $companyUser->id)
            ->pluck('allowed', 'permission_key');

        $sections = [];
        foreach (Permission::CATALOG as $key => $definition) {
            if (! Permission::isOverridable($key)) {
                continue;
            }

            $section = $definition['section'];
            $inheritedAllowed = in_array($companyUser->role, $definition['default_roles'], true);

            $sections[$section][] = [
                'key' => $key,
                'label' => $definition['label'],
                'inherited_allowed' => $inheritedAllowed,
                'state' => $overrides->has($key)
                    ? ($overrides[$key] ? 'forzado_si' : 'forzado_no')
                    : 'heredado',
            ];
        }

        return view('users.permissions', [
            'user' => $user,
            'companyUser' => $companyUser,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $companyUser = $this->findCompanyUser($user);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['in:heredado,forzado_si,forzado_no'],
        ]);

        $states = $validated['permissions'] ?? [];

        DB::transaction(function () use ($companyUser, $states) {
            CompanyUserPermission::where('company_user_id', $companyUser->id)->delete();

            foreach ($states as $key => $state) {
                if ($state === 'heredado' || ! Permission::isOverridable($key)) {
                    continue;
                }

                if (! isset(Permission::CATALOG[$key])) {
                    continue;
                }

                CompanyUserPermission::create([
                    'company_user_id' => $companyUser->id,
                    'permission_key' => $key,
                    'allowed' => $state === 'forzado_si',
                ]);
            }
        });

        return redirect()->route('users.index')
            ->with('success', 'Permisos actualizados correctamente.');
    }
}
