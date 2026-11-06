<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    use HasFactory;

    protected $table = 'entities';

    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_entities');
    }
    public function categorie()
    {
        return $this->hasMany(Cat::class, 'entity_id');
    }


}
