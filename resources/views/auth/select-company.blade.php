@extends('layouts.app')

@section('title', 'Seleccionar Empresa')

@section('content')

<div class="max-w-md mx-auto mt-20 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Seleccionar Empresa</h2>

    <form method="POST" action="{{ route('company.select') }}">
        @csrf

        <select name="company_id" class="w-full border p-2 mb-4 rounded">
            @foreach($companies as $company)
                <option value="{{ $company->id }}">
                    {{ $company->name }}
                </option>
            @endforeach
        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded w-full">
            Continuar
        </button>
    </form>
</div>

@endsection
