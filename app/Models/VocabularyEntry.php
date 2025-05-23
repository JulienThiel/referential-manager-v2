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
        // Eager load grandchildren recursively
        return $this->hasMany(self::class, 'parent_id')->orderBy('rank')->with('children');
    }

    public static function getTreeForVocabulary($vocabularyId)
    {
        return self::where('vocabulary_id', $vocabularyId)
                     ->whereNull('parent_id')
                     ->orderBy('rank')
                     ->with('children') // This will now recursively load due to the modified children() relationship
                     ->get();
    }

    public function getDepthAttribute(): int
    {
        if (is_null($this->parent_id)) {
            return 0;
        }
        // If the parent relationship is loaded, use it.
        if ($this->relationLoaded('parent') && $this->parent) {
            return $this->parent->depth + 1;
        }
        // Fallback: if parent is not loaded, recursively call depth on parent.
        // This assumes parent model can be fetched or is already available.
        // For a tree structure built with relationships, parent should be accessible.
        return $this->parent ? $this->parent->depth + 1 : 0;
    }
}
