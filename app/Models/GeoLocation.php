<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoLocation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'data_hash',
        'created_by',
        'updated_by',
    ];
}
