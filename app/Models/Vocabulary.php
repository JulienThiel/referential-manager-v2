<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webpatser\Uuid\Uuid;
use App\Models\Product; // Import de l'entité Product

class Vocabulary extends Model
{
    use HasFactory;

    // Utilisation d'UUID en clé primaire
    public $incrementing = false;
    protected $keyType = 'string';

    // Champs autorisés pour le mass-assignment
    protected $fillable = ['name'];

    // Génération automatique d'un UUID lors de la création
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = Uuid::generate()->string;
        });
    }

    /**
     * Relation 1-N avec VocabularyEntry
     */
    public function entries()
    {
        return $this->hasMany(VocabularyEntry::class);
    }

    /**
     * Relation Many-to-Many avec Product via la table pivot product_vocabulary
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_vocabulary');
    }
}
