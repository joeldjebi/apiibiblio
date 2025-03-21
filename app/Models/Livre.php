<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livre extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'amount',
    ];

    public function auteur( )
    {
        return $this->belongsTo(Auteur::class, 'auteur_id');
    }

    public function type_publication()
    {
        return $this->belongsTo(Type_publication::class, 'type_publication_id');
    }

	public function typePublication()
    {
        return $this->belongsTo(Type_publication::class, 'type_publication_id');
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function editeur()
    {
        return $this->belongsTo(Editeur::class, 'editeur_id');
    }

    public function langue()
    {
        return $this->belongsTo(Langue::class, 'langue_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class, 'livre_id', 'id');
    }

    public function chapitres()
    {
        return $this->hasMany(Chapitre::class, 'livre_id', 'id');
    }

	// App\Models\Livre.php
	public function stars()
	{
		return $this->hasMany(Star::class, 'livre_id');
	}


    public function file()
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    public function pays()
    {
        return $this->belongsTo(Pays::class, 'pays_id', 'id');
    }

    // Relation avec les utilisateurs via les transactions
    public function users()
    {
        return $this->belongsToMany(User::class, 'wallet_transactions', 'livre_id', 'user_id')
            ->withPivot('montant', 'type_transaction', 'date_transaction')
            ->withTimestamps();
    }

}
