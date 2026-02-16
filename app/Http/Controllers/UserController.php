<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const ROLES = ['SuperAdmin', 'Admin', 'Vendedor'];

    private function currentCompanyId(): int
    {
        return (int) session('current_company_id');
    }

    private function usersForCurrentCompany()
    {
        $companyId = $this->currentCompanyId();

        return User::whereHas('companies', function ($query) use ($companyId) {
            $query->where('companies.id', $companyId);
        })->with(['companies' => function ($query) use ($companyId) {
            $query->where('companies.id', $companyId);
        }]);
    }

    private function findUserInCurrentCompany(string $id): User
    {
        return $this->usersForCurrentCompany()->findOrFail($id);
    }

    public function index()
    {
        $users = $this->usersForCurrentCompany()->get();
        $roles = self::ROLES;

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = self::ROLES;

        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->currentCompanyId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);

        DB::transaction(function () use ($validated, $companyId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->companies()->attach($companyId, [
                'role' => $validated['role'],
            ]);

            $companyUser = CompanyUser::where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->first();

            if ($companyUser) {
                $warehouseIds = Warehouse::where('company_id', $companyId)->pluck('id')->all();

                if (! empty($warehouseIds)) {
                    $companyUser->warehouses()->syncWithoutDetaching($warehouseIds);
                }
            }
        });

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado y rol asignado correctamente.');
    }

    public function show(string $id)
    {
        $user = $this->findUserInCurrentCompany($id);

        return view('users.show', compact('user'));
    }

    public function edit(string $id)
    {
        $user = $this->findUserInCurrentCompany($id);
        $roles = self::ROLES;
        $currentRole = optional($user->companies->first())->pivot->role;

        return view('users.edit', compact('user', 'roles', 'currentRole'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $companyId = $this->currentCompanyId();
        $user = $this->findUserInCurrentCompany($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);

        DB::transaction(function () use ($user, $validated, $companyId) {
            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $user->companies()->updateExistingPivot($companyId, [
                'role' => $validated['role'],
            ]);
        });

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function updateRole(Request $request, string $id): RedirectResponse
    {
        $companyId = $this->currentCompanyId();
        $user = $this->findUserInCurrentCompany($id);

        $validated = $request->validate([
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);

        $user->companies()->updateExistingPivot($companyId, [
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $companyId = $this->currentCompanyId();
        $user = $this->findUserInCurrentCompany($id);

        $user->companies()->detach($companyId);

        if ($user->companies()->count() === 0) {
            $user->delete();
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario removido de la empresa.');
    }
}
