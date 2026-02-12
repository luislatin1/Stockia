<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        
        User::create([
        'name'       => $request->name,
        'email'      => $request->email,
        'password'   => Hash::make($request->password),
        'company_id' => session('current_company_id'),
    ]);

    return redirect()->route('users.index')
        ->with('success', 'Usuario creado correctamente.');
    
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
{
    $user = User::where('company_id', session('current_company_id'))
                ->findOrFail($id);

    return view('users.show', compact('user'));
}

public function edit(string $id)
{
    $user = User::where('company_id', session('current_company_id'))
                ->findOrFail($id);

    return view('users.edit', compact('user'));
}
   /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
    {
    $user = User::where('company_id', session('current_company_id'))
                ->findOrFail($id);
    $user->update([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
    ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
{
    $user = User::where('company_id', session('current_company_id'))
                ->findOrFail($id);

    $user->delete();

    return redirect()->route('users.index');
}}
