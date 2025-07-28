<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'date_distribute',
        'quantity',
        'remarks',
        'stocks',
        'reason'
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }


}
