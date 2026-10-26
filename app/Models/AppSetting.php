<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use LogsAdminActivity;

    // Lus à chaque requête API (maintenance, services ouverts, limites) :
    // tous les réglages en un seul objet de cache, mémorisé le temps de la
    // requête, invalidé à chaque écriture admin.
    private const CACHE_KEY = 'app_settings.all';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'description'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::memo()->forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::memo()->forget(self::CACHE_KEY));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::allCached()[$key] ?? $default;
    }

    /** @return array<string, string|null> */
    private static function allCached(): array
    {
        return Cache::memo()->rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all(),
        );
    }
}
