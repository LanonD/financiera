<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestamoArchivo extends Model
{
    public $timestamps    = false;
    protected $table      = 'prestamo_archivos';
    protected $fillable   = ['prestamo_id', 'subido_por', 'nombre_original', 'ruta', 'tipo_archivo', 'tamano'];
    protected $casts      = ['created_at' => 'datetime'];
    const CREATED_AT      = 'created_at';
    const UPDATED_AT      = null;

    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function esImagen(): bool
    {
        return in_array(strtolower($this->tipo_archivo), ['jpg', 'jpeg', 'png']);
    }

    public function tamanoFormateado(): string
    {
        $bytes = (int)$this->tamano;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}
