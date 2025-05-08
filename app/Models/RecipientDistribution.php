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

    protected $casts = [
        'date_given' => 'date',
    ];

    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }

    // Define the inventory relationship through distribution
    public function inventory()
    {
        return $this->hasOneThrough(Inventory::class, Distribution::class, 'id', 'id', 'distribution_id', 'inventory_id');
    }

    /**
     * Scope to filter by month and year.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $month
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByMonthAndYear($query, $month, $year)
{
    return $query->whereMonth('date_given', $month)
                 ->whereYear('date_given', $year);
}

}
