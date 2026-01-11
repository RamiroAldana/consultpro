<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestedQuery extends Model
{
    use HasFactory;

    protected $table = 'requested_queries';

    protected $fillable = ['name','sources','status'];

    protected $casts = [
        'sources' => 'array',
    ];

    public function details()
    {
        return $this->hasMany(DetailQuery::class, 'requested_query_id');
    }
}
