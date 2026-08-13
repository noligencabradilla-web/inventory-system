<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SPOffices extends Model
{
    protected $table = 's_p_offices';

    protected $fillable = ['office', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class, 's_p_office_id');
    }
}
