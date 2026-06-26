<x-guest-layout>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-7 space-y-5">

        {{-- Confirmación de envío --}}
        @if (session('status'))
            <x-stockia.alert tone="success">{{ session('status') }}</x-stockia.alert>
        @endif

        {{-- Encabezado --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Recuperar contraseña</h1>
            <p class="mt-1 text-sm text-gray-500">
                Te enviaremos un enlace para restablecerla.
            </p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <x-stockia.field label="Correo electrónico" for="email" :error="$errors->first('email')">
                <x-stockia.input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    :invalid="$errors->has('email')"
                    autocomplete="email"
                    autofocus
                    required />
            </x-stockia.field>

            <x-stockia.button type="submit" :block="true">
                Enviar enlace
            </x-stockia.button>

        </form>

        <a href="{{ route('login') }}"
           class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors">
            ← Volver a iniciar sesión
        </a>

    </div>

</x-guest-layout>
