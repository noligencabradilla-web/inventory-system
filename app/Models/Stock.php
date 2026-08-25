<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id','id_no','description','unit','total','stock','hidden'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inbounds()
    {
        return $this->hasMany(Inbound::class);
    }

    public function outbounds()
    {
        return $this->hasMany(Outbound::class);
    }

    public function requests()
    {
        return $this->hasMany(StockRequest::class);
    }

    /**
     * will serves as the history for the allocation
     */
    public function inbound_allocations(){
        return $this->hasMany(InboundAllocation::class);
    }
    /**
     * will be the moving qty b4 proceeding to outbounds
     */
    public function stock_allocations(){
        return $this->hasMany(StockAllocation::class);
    }
}
