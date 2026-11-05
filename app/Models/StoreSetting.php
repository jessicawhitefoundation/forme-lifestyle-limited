<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget('store_setting_global');
        });

        static::deleted(function () {
            cache()->forget('store_setting_global');
        });
    }
}
