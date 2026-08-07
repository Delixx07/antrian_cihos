<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * READ-ONLY. Direktori user RS (dbuser.user_detail) — sumber login.
 * Password disimpan sebagai SHA1 (skema RS). Jangan menulis ke tabel ini.
 */
class UserDetail extends Model
{
    protected $connection = 'dbuser';
    protected $table = 'user_detail';
    public $timestamps = false;

    protected $hidden = ['password'];

    /** Cocokkan password polos dengan hash SHA1 tersimpan (hex, case-insensitive). */
    public function checkPassword(string $plain): bool
    {
        return hash_equals(strtolower((string) $this->password), sha1($plain));
    }
}
