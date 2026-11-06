<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Movimento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'movimenti';

    protected $fillable = [
        'categoria',
        'descrizione',
        'entratauscita',
        'importo',
        'data_movimento',
        'estero_senza_iva',
        'user_id',
        'entity_id',
    ];

    protected $casts = [
        'entratauscita' => 'integer',
        'data_movimento' => 'date',
        'estero_senza_iva' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Se non indicata, la data del movimento è quella odierna
        static::creating(function (Movimento $movimento) {
            $movimento->data_movimento ??= now()->toDateString();
        });
    }

    /**
     * Scope a query to only include movements for the selected entity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon|null  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSelectedEntity(Builder $query, ?Carbon $date = null): Builder
    {
        $query->where('entity_id', session('selected_entity_id'));

        if ($date) {
            $query->whereDate('data_movimento', $date);
        }

        return $query;
    }

    /**
     * Applica i filtri dell'elenco movimenti (testo, periodo, categoria, tipo).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn ($q, $text) => $q->where('descrizione', 'like', '%'.addcslashes($text, '%_\\').'%'))
            ->when($filters['dal'] ?? null, fn ($q, $date) => $q->whereDate('data_movimento', '>=', $date))
            ->when($filters['al'] ?? null, fn ($q, $date) => $q->whereDate('data_movimento', '<=', $date))
            ->when($filters['categoria'] ?? null, fn ($q, $cat) => $q->where('categoria', $cat))
            ->when($filters['estero'] ?? false, fn ($q) => $q->where('estero_senza_iva', true))
            ->when(isset($filters['tipo']) && $filters['tipo'] !== '', fn ($q) => $q->where('entratauscita', (int) $filters['tipo']));
    }

    public function categorie() {
        return $this->belongsTo(Cat::class, 'categoria', 'cat_id');
    }

    public function users() {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function importo(): Attribute
    {
        return Attribute::make(
            set: fn ($value, $attributes) =>
                isset($attributes['entratauscita']) && !$attributes['entratauscita']
                ? abs($value) * -1
                : $value,
        );
    }
}
