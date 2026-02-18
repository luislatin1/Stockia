@extends('layouts.app')

@section('title', 'Nuevo Cliente DTE')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Nuevo Cliente DTE</h2>
        <p class="text-sm text-gray-500">Registra un receptor para facturación local/dummy.</p>
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

    <form action="{{ route('dte.customers.store') }}" method="POST" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @php($customer = null)
        @php($submitLabel = 'Crear Cliente')
        @include('dte.customers.partials.form')
    </form>
</div>
@endsection
