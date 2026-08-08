<?php

namespace App\Enums;

enum AmostraStatus: string
{
    case Recebida = 'recebida';
    case Aceite = 'aceite';
    case Rejeitada = 'rejeitada';
}
