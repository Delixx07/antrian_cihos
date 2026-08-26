<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

// Jangan pakai putenv() untuk memuat .env. Aplikasi ini berbagi satu proses
// Apache/PHP (XAMPP mod_php, satu proses) dengan app "appointment" di sibling
// folder - putenv() menulis ke environment level PROSES, jadi tanpa ini
// request app ini bisa ikut membaca DB_DATABASE dkk milik app appointment
// (atau sebaliknya), menyebabkan query nyasar ke database yang salah secara
// acak (contoh nyata: "Table appointment_pasien_cihos.antrian_access doesn't
// exist" - padahal harusnya baca antrian_cihos).
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai header proxy (X-Forwarded-Proto/For/Host) dari reverse-proxy
        // di depan aplikasi (mis. appt.ciputracare.cloud). Tanpa ini Laravel
        // menganggap request HTTP walau publik-nya HTTPS → URL yang dibuat
        // Laravel sendiri (route()/url(), termasuk action form "Selesai")
        // jadi http:// → browser blokir sebagai "Mixed Content" / not secure.
        // '*' = percayai proxy front-end (aman, app tak diekspos langsung ke
        // internet, hanya via proxy).
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'auth.antrian' => \App\Http\Middleware\RequireLogin::class,
            'role'         => \App\Http\Middleware\RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
