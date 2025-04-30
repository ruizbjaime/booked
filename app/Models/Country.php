<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Country extends Model
{
    protected $fillable = [
        'es_name',
        'en_name',
        'iso_code',
        'phone_code',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getNameAttribute(): string
    {
        if(App::getLocale() === 'es') {
            return $this->attributes['es_name'];
        }

        return $this->attributes['en_name'];
    }
}
