<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenSeguimiento extends Model
{
    protected $table = 'tokens_seguimiento';

    protected $fillable = [
        'documento_id',
        'token_hash',
        'proposito',
        'expira_en',
        'ultimo_uso_en',
        'usos',
        'revocado',
    ];

    protected $casts = [
        'expira_en' => 'datetime',
        'ultimo_uso_en' => 'datetime',
        'usos' => 'integer',
        'revocado' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Documento al que permite realizar seguimiento.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Tokens que no han sido revocados.
     */
    public function scopeNoRevocados(Builder $query): Builder
    {
        return $query->where('revocado', false);
    }

    /**
     * Tokens revocados.
     */
    public function scopeRevocados(Builder $query): Builder
    {
        return $query->where('revocado', true);
    }

    /**
     * Tokens que todavía no han expirado.
     *
     * Un token sin fecha de expiración se considera no expirado.
     */
    public function scopeNoExpirados(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query
                ->whereNull('expira_en')
                ->orWhere('expira_en', '>', now());
        });
    }

    /**
     * Tokens cuya fecha de expiración ya pasó.
     */
    public function scopeExpirados(Builder $query): Builder
    {
        return $query
            ->whereNotNull('expira_en')
            ->where('expira_en', '<=', now());
    }

    /**
     * Filtra los tokens por propósito.
     */
    public function scopeDelProposito(
        Builder $query,
        string $proposito
    ): Builder {
        return $query->where('proposito', $proposito);
    }

    /**
     * Tokens utilizables actualmente.
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->noRevocados()
            ->noExpirados();
    }

    /**
     * Tokens pertenecientes a un documento.
     */
    public function scopeDelDocumento(
        Builder $query,
        int $documentoId
    ): Builder {
        return $query->where(
            'documento_id',
            $documentoId
        );
    }

    /**
     * Ordena por los utilizados más recientemente.
     */
    public function scopeUsadosRecientemente(Builder $query): Builder
    {
        return $query->orderByDesc('ultimo_uso_en');
    }
}