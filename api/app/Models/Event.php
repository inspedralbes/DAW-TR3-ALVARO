<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'date', 'description'];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
