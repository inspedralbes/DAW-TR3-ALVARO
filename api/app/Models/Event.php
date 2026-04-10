<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'date', 'description', 'venue', 'capacity', 'zones'];

    protected function casts(): array
    {
        return [
            'date'  => 'datetime',
            'zones' => 'array',
        ];
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
