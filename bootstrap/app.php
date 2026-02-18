<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ValidarDocumentoDte;
use App\Http\Middleware\SetCurrentCompany;
use App\Http\Middleware\EnsureCompanySelected;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
        SetCurrentCompany::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureCompanyRole::class,
            'company.selected' => EnsureCompanySelected::class,
            'dte.document' => ValidarDocumentoDte::class,
        ]);

    
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
