<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['label'];

    protected static function booted()
    {
        static::creating(fn ($model) => $model->id = Uuid::generate()->string);
    }

    public function vocabularies()
    {
        return $this->belongsToMany(Vocabulary::class, 'product_vocabulary');
    }
}