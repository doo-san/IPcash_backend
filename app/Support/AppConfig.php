<?php

namespace App\Support;

use App\Models\AppSetting;

// Lecture typée des réglages de config/app_config.php : la valeur enregistrée
// depuis l'admin (`app_settings`) sinon le défaut du fichier de config.
class AppConfig
{
    /** @return array<string, array{label: string, type: string, default: mixed, min?: int}> */
    public static function fields(): array
    {
        $fields = [];
        foreach (config('app_config') as $group) {
            $fields += $group['fields'];
        }

        return $fields;
    }

    public static function get(string $key): string|int|bool
    {
        $field = self::fields()[$key];
        $stored = AppSetting::get($key);

        if ($stored === null || $stored === '') {
            return $field['default'];
        }

        return match ($field['type']) {
            'integer' => (int) $stored,
            'toggle' => in_array($stored, ['1', 'true'], true),
            default => $stored,
        };
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function bool(string $key): bool
    {
        return (bool) self::get($key);
    }

    public static function string(string $key): string
    {
        return (string) self::get($key);
    }

    /** Un service est-il ouvert aux clients ? (`transfer` → `feature_transfer_enabled`) */
    public static function featureEnabled(string $feature): bool
    {
        return self::bool("feature_{$feature}_enabled");
    }

    /**
     * Charge utile publique de `GET /config`.
     *
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        $features = [];
        foreach (array_keys(config('app_config.features.fields')) as $key) {
            $features[preg_replace('/^feature_(.+)_enabled$/', '$1', $key)] = self::bool($key);
        }

        $message = self::string('maintenance_message');

        return [
            'supportPhoneNumber' => self::string('support_phone_number'),
            'maintenance' => [
                'enabled' => self::bool('maintenance_enabled'),
                'message' => $message === '' ? null : $message,
            ],
            'minAppVersion' => [
                'ios' => self::string('min_app_version_ios') ?: null,
                'android' => self::string('min_app_version_android') ?: null,
            ],
            'limits' => [
                'transferMinAmountXof' => self::int('transfer_min_amount_xof'),
            ],
            'features' => $features,
        ];
    }
}
