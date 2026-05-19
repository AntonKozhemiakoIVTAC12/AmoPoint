<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'ip',
        'city',
        'country',
        'device',
        'browser',
        'os',
        'url',
        'referrer',
        'user_agent',
    ];
}
