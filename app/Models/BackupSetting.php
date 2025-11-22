<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BackupSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        // Encrypt sensitive values before storing
        if ($type === 'encrypted' && $value !== null) {
            $value = Crypt::encryptString($value);
        } elseif ($type === 'boolean') {
            $value = $value ? 'true' : 'false';
        } elseif ($type === 'json' && $value !== null) {
            $value = json_encode($value);
        } else {
            $value = (string) $value;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value === 'true',
            'encrypted' => Crypt::decryptString($value),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
