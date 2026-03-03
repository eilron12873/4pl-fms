<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [
        'company_name',
        'company_address',
        'company_logo',
        'telephone_number',
        'email_address',
        'website',
        'default_timezone',
        'default_date_format',
        'default_currency',
    ];

    public static function getSettings()
    {
        try {
            $settings = static::first();

            if (! $settings) {
                return new static([
                    'company_name' => null,
                    'company_address' => null,
                    'company_logo' => null,
                    'telephone_number' => null,
                    'email_address' => null,
                    'website' => null,
                    'default_timezone' => 'Asia/Manila',
                    'default_date_format' => 'Y-m-d',
                    'default_currency' => 'PHP',
                ]);
            }

            return $settings;
        } catch (\Exception $e) {
            return new static([
                'company_name' => null,
                'company_address' => null,
                'company_logo' => null,
                'telephone_number' => null,
                'email_address' => null,
                'website' => null,
                'default_timezone' => 'Asia/Manila',
                'default_date_format' => 'Y-m-d',
                'default_currency' => 'PHP',
            ]);
        }
    }

    public function getLogoUrlAttribute()
    {
        if ($this->company_logo) {
            return asset('storage/'.$this->company_logo);
        }

        return null;
    }
}

