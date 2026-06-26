<x-guest-layout>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-7 space-y-5">

        {{-- Estado de sesión (ej. "enlace de recuperación enviado") --}}
        @if (session('status'))
            <x-stockia.alert tone="success">{{ session('status') }}</x-stockia.alert>
        @endif

        {{-- Encabezado --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Iniciar sesión</h1>
            <p class="mt-1 text-sm text-gray-500">Accede a tu punto de venta e inventario.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- Correo electrónico --}}
            <x-stockia.field label="Correo electrónico" for="email" :error="$errors->first('email')">
                <x-stockia.input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    :invalid="$errors->has('email')"
                    autocomplete="username"
                    autofocus
                    required />
            </x-stockia.field>

            {{-- Contraseña --}}
            <x-stockia.field label="Contraseña" for="password" :error="$errors->first('password')">
                <x-stockia.input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    :invalid="$errors->has('password')"
                    autocomplete="current-password"
                    required />
            </x-stockia.field>

            {{-- Recordarme + ¿Olvidaste? --}}
            <div class="flex items-center justify-between gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                    Recordarme
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <x-stockia.button type="submit" :block="true">
                Iniciar sesión
            </x-stockia.button>

        </form>
    </div>

</x-guest-layout>
