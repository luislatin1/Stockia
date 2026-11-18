#!/usr/bin/env bash
# Uso: wsl-reset-pass.sh <email>  — pone una contraseña aleatoria y la imprime
set -euo pipefail
cd /var/www/stockia
NEWPASS="Stk-$(openssl rand -hex 5)"
EMAIL="$1" NEWPASS="$NEWPASS" php artisan tinker --execute '
$u = App\Models\User::where("email", getenv("EMAIL"))->firstOrFail();
$u->password = Illuminate\Support\Facades\Hash::make(getenv("NEWPASS"));
$u->save();
echo "OK id={$u->id}\n";'
echo "Nueva contraseña: $NEWPASS"
