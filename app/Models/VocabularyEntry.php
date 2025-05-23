<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class VocabularyEntry extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'entry_labels' => 'array',
    ];

    protected $fillable = [
        'vocabulary_id',
        'parent_id',
        'entry_value',
        'entry_labels',
        'rank',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = Uuid::generate()->string;
        });
    }

    public function vocabulary()
    {
        return $this->belongsTo(Vocabulary::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('rank');
    }
}
