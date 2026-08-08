<?php

namespace App\Enums;

enum ProdutoType: string
{
    case Vacina = 'vacina';
    case Soro = 'soro';
    case Reagente = 'reagente';
}
