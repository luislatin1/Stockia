@extends('layouts.app')

@section('title', 'Editar Cliente DTE')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Editar Cliente DTE</h2>
        <p class="text-sm text-gray-500">Actualiza datos fiscales del receptor.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dte.customers.update', $customer) }}" method="POST" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @php($method = 'PUT')
        @php($submitLabel = 'Guardar Cambios')
        @include('dte.customers.partials.form')
    </form>
</div>
@endsection
