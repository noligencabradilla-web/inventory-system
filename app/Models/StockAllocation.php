<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAllocation extends Model
{
    protected $fillable = [
        "inbound_allocation_id",
        "allocation",
        "outstanding"
    ];

    public function inbound_allocation(){
        return $this->belongsTo(InboundAllocation::class);
    }
}
