<?php

namespace App\Exceptions;

use RuntimeException;

/** O hotel não está vinculado a um ponto do mapa, então não há origem para as rotas. */
class HotelNotMappedException extends RuntimeException
{
    public function __construct(string $message = 'Este hotel ainda não está vinculado a um ponto do mapa da cidade.')
    {
        parent::__construct($message);
    }
}
