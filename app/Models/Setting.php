<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'title',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Récupérer la valeur typée d'un setting
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Définir la valeur en fonction du type
     */
    public function setTypedValue($value): void
    {
        $this->value = match ($this->type) {
            'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Helper statique pour récupérer un setting par clé
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Helper statique pour définir un setting
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $title = null, ?string $description = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->group = $group;
        if ($title) {
            $setting->title = $title;
        }
        if ($description) {
            $setting->description = $description;
        }
        $setting->setTypedValue($value);
        $setting->save();
        return $setting;
    }
}
