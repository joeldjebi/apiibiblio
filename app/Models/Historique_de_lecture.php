<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historique_de_lecture extends Model
{
    use HasFactory;

    /**
     * Déclaration des attributs modifiables en masse.
     */
    protected $fillable = [
        'user_id',
        'file_id',
        'episode_id',
        'chapitre_id',
        'position',
    ];

    /**
     * Relation avec le modèle File.
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /**
     * Relation avec le modèle User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relation avec le modèle Livre.
     */
    public function livre()
    {
        return $this->belongsTo(Livre::class, 'livre_id', 'id');
    }

    /**
     * Relation avec le modèle Episode.
     */
    public function episode()
    {
        return $this->belongsTo(Episode::class, 'episode_id', 'id');
    }

    /**
     * Relation avec le modèle Chapitre.
     */
    public function chapitre()
    {
        return $this->belongsTo(Chapitre::class, 'chapitre_id', 'id');
    }

    /**
     * Scope pour filtrer les historiques par utilisateur.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}