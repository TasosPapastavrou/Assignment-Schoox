<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Course extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'title', 'description', 'status', 'is_premium', 'user_id',
    ];

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilterCourses(Builder $query, $data)
    {

        $query->when($data['title'] ?? null, function ($query) use ($data) { 
                $query->where('title', '=', $data['title']);            
            })->when($data['description'] ?? null, function ($query) use ($data) {
                $query->where('description', 'LIKE', '%' . $data['description'] . '%');
            })->when($data['status'] ?? null, function ($query) use ($data) { 
                $query->where('status', '=', $data['status']);
            })->when($data['is_premium'] ?? null, function ($query) use ($data) { 
                $query->where('is_premium', '=', $data['is_premium']);
            })->when($data['startDate']?? null, function ($query) use ($data) {
                $data['startDate'] = Carbon::parse($data['startDate'])->startOfDay();
                $query->where('created_at', '>=', $data['startDate']);
            })->when($data['endDate'] ?? null, function ($query) use ($data) {
                $data['endDate'] = Carbon::parse($data['endDate'])->endOfDay();
                $query->where('created_at', '<=', $data['endDate']);
            })->when($data['tags'] ?? null, function($query) use ($data) {
                    $tags = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
                    $query->whereHas('tags', function (Builder $query) use ($tags) {
                        $query->whereIn('name', $tags);
                    });
        });
    }

}
