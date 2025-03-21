<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicite extends Model
{
    use HasFactory;

    public function livre()
    {
        return $this->belongsTo(Livre::class, 'livre_id', 'id');
    }
}