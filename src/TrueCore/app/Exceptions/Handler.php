<?php

namespace TrueCore\App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * @param \Throwable $exception
     *
     * @throws Exception
     */
    public function report(\Throwable $exception)
    {
//        if (app()->bound('sentry')
//            && $this->shouldReport($exception)
//            && config('app.sentryEnabled', true)) {
//            app('sentry')->captureException($exception);
//        }

        parent::report($exception);
    }

    protected function whoopsHandler()
    {
        try {
            return app(\Whoops\Handler\HandlerInterface::class);
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            return (new \Illuminate\Foundation\Exceptions\WhoopsHandler)->forDebug();
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     *
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     */
    public function render($request, \Throwable $exception)
    {
        if (!$request->is('api/*')) {

            if ($exception instanceof \ErrorException) {
                return response()->json(['message' => 'Unknown error'], 403);
            }
        } else {
            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param ValidationException $exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'message' => __('validation.exception.message'),
            'errors'  => $exception->errors(),
        ], $exception->status);
    }
}
