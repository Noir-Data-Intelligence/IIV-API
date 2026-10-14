<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot BI × amostra — estado: pendente|em_ensaio|concluida|rejeitada_na_area. */
class BoletimInternoAmostra extends Pivot
{
    use HasUuids;

    public const PENDENTE = 'pendente';

    public const EM_ENSAIO = 'em_ensaio';

    public const CONCLUIDA = 'concluida';

    public const REJEITADA_NA_AREA = 'rejeitada_na_area';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'boletim_interno_amostras';
}
