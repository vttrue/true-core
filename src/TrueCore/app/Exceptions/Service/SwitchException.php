<?php

namespace TrueCore\App\Exceptions\Http\Service;

use Symfony\Component\HttpFoundation\Response;

class SwitchException extends \TrueCore\App\Exceptions\Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json(['error' => true, 'message' => $this->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}