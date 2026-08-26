<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan aplikasi berupa key-value sederhana (mis. teks berjalan layar
 * display) - diedit admin lewat halaman Media Layar Tunggu, dibaca di mana
 * saja lewat Setting::get(). Di-cache singkat supaya display yang polling
 * tiap beberapa detik tak query DB tiap kali.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting:{$key}", 300, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
