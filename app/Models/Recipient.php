<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'birthdate',
        'barangay',
        'gender',
    ];

    public function distributions()
    {
        return $this->hasMany(RecipientDistribution::class);
    }
}
