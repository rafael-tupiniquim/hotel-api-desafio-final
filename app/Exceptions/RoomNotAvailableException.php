<?php

namespace App\Exceptions;

use Exception;

/** O quarto já possui reserva que se sobrepõe ao período solicitado. */
class RoomNotAvailableException extends Exception
{
    public function __construct(string $message = 'O quarto selecionado não está disponível para o período informado.')
    {
        parent::__construct($message);
    }
}
