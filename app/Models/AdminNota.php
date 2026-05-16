<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNota extends Model
{
    protected $table = 'admin_notas';

    protected $fillable = ['admin_id', 'contenido'];

    protected $casts = ['created_at' => 'datetime'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
