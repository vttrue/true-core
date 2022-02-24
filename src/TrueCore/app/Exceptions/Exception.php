<?php

namespace TrueCore\App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class Exception extends \Exception
{
    /**
     * Render the exception into an HTTP response.
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return config('app.debug')
            ? response()->json([
                'exception'  => get_class($this),
                'message'    => $this->getMessage(),
                'file'       => $this->getFile(),
                'line'       => $this->getLine(),
                'stackTrace' => $this->getTrace(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
            : response()->json(['message' => $this->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
