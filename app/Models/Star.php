<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    use HasFactory;

        /**
     * Déclaration des attributs modifiables en masse.
     */
    protected $fillable = [
        'user_id',
        'livre_id',
        'episode_id',
        'type_publication_id',
        'star',
    ];

    public function livre()
	{
		return $this->belongsTo(Livre::class);
	}

    public function type_publication()
	{
		return $this->belongsTo(Type_publication::class);
	}

    public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function historiqueDeLecture()
	{
		return $this->belongsTo(Historique_de_lecture::class);
	}

}
