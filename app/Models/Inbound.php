<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbound extends Model
{
    use HasFactory;

    protected $fillable = ['stock_id','total'];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
    public function allocations()
    {
        return $this->hasMany(InboundAllocation::class);
    }
}
