<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundAllocation extends Model
{
    protected $fillable = [
        "inbound_id",
        "stock_id",
        "office_id",
        "allocation",
    ];

    public function inbound()
    {
        return $this->belongsTo(Inbound::class);
    }
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
    public function office()
    {
        return $this->belongsTo(SPOffices::class);
    }
}
