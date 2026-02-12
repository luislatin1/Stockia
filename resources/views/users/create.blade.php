@extends('layouts.app')

@section('content')

<h2 class="text-xl font-bold mb-4">Nuevo Usuario</h2>

<form action="{{ route('users.store') }}" method="POST">
    @csrf

    <input type="text" name="name" placeholder="Nombre" class="border p-2 block mb-2">
    <input type="email" name="email" placeholder="Email" class="border p-2 block mb-2">
    <input type="password" name="password" placeholder="Password" class="border p-2 block mb-2">

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Crear Usuario
    </button>
</form>

@endsection
