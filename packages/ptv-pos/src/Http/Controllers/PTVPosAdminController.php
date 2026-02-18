<?php

namespace Stockia\PTVPos\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PTVPosAdminController extends Controller
{
    public function index()
    {
        $registers = DB::table('pos_registers')
            ->where('company_id', session('current_company_id'))
            ->orderBy('name')
            ->get();

        return view('ptvpos::admin.registers', compact('registers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:pos_registers,code'],
        ]);

        DB::table('pos_registers')->insert([
            'company_id' => session('current_company_id'),
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('ptvpos.admin.registers.index')->with('success', 'Caja creada.');
    }
}

