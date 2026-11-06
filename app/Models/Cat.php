<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Cat
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Cat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cat query()
 * @mixin \Eloquent
 */
class Cat extends Model
{
    use HasFactory;

    protected $table = 'categorie';
    protected $primaryKey = 'cat_id';

    protected $fillable = [
        'descriz_cat'
        ];

    protected $casts = [
        'estero_senza_iva' => 'boolean',
    ];

    /**
     * Limita la query alle categorie dell'entità selezionata in sessione.
     */
    public function scopeForSelectedEntity(Builder $query): Builder
    {
        return $query->where('entity_id', session('selected_entity_id'));
    }

    // Definisci la relazione con l'entità (se necessario)
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    // Definisci la relazione con i movimenti
    public function movimenti()
    {
        return $this->hasMany(Movimento::class, 'categoria', 'cat_id');
    }

}
