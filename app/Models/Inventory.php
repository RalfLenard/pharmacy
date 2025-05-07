<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_in',
        'quantity',
        'brand_name',
        'lot_number',
        'expiration_date',
        'generic_name',
        'utils',
        'stocks'
    ];

    protected $casts = [
        'date_in' => 'date',
        'expiration_date' => 'date',
    ];

    public function distributions()
    {
        return $this->hasMany(Distribution::class);
    }
}
