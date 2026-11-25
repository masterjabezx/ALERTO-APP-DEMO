<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'cast_type',
        'description',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return match ($setting->cast_type) {
            'int', 'integer' => (int) $setting->value,
            'bool', 'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value ?? '[]', true),
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $castType = 'string', ?string $description = null): self
    {
        $storedValue = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

        $attributes = [
            'value' => $storedValue,
            'cast_type' => $castType,
        ];

        if ($description !== null) {
            $attributes['description'] = $description;
        }

        return static::updateOrCreate(['key' => $key], $attributes);
    }
}
