<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batasi percobaan login per akun+IP (bukan per-IP saja) - beberapa
        // konsol staf bisa berbagi jaringan yang sama, jadi throttle murni
        // per-IP bisa ikut mengunci staf lain yang tak salah apa-apa.
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('username')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function (Request $request, array $headers) {
                return back()->withInput($request->only('username'))
                    ->withErrors(['username' => 'Terlalu banyak percobaan login. Coba lagi sebentar lagi.'])
                    ->withHeaders($headers);
            });
        });
    }
}
