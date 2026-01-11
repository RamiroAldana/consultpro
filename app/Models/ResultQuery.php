<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultQuery extends Model
{
    use HasFactory;

    protected $table = 'results_queries';

    protected $fillable = ['detail_query_id','source','status_response','response_json','image_path'];

    protected $casts = [
        'response_json' => 'array',
    ];

    public function detail()
    {
        return $this->belongsTo(DetailQuery::class, 'detail_query_id');
    }
}
