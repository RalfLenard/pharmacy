<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipientDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_id',
        'distribution_id',
        'quantity',
        'date_given',
    ];

    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }

    // In Distribution.php (the Distribution model)
    public function inventory()
    {
        return $this->hasOneThrough(Inventory::class, Distribution::class, 'id', 'id', 'distribution_id', 'inventory_id');
    }

}
