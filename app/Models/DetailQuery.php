<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailQuery extends Model
{
    use HasFactory;

    protected $table = 'details_queries';

    protected $fillable = ['requested_query_id','full_name','document_type','document_number','status'];

    public function requestedQuery()
    {
        return $this->belongsTo(RequestedQuery::class, 'requested_query_id');
    }

    public function results()
    {
        return $this->hasMany(ResultQuery::class, 'detail_query_id');
    }
}
