<?php

namespace App\Exceptions;

use Exception;

class IgdbException extends Exception
{
    public function render()
    {
        return response()->json(['message' => 'Neizdevās ielādēt datus no IGDB'], 502);
    }
}
