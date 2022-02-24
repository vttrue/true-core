<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 12.12.2018
 * Time: 13:01
 */

namespace TrueCore\App\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class IfModifiedSince
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param string|Carbon|null
     * @return mixed
     */
    public function handle($request, \Closure $next, $lastModifiedValue = null)
    {
        /** @var Response $response */
        $response = $next($request);

        if(in_array($request->method(), ['GET', 'HEAD'])) {

            if($lastModifiedValue === null && $request->attributes->has('lastModified')) {
                $lastModifiedValue = $request->attributes->get('lastModified');
            }

            $lastModified = new Carbon;

            //dd($request->session()->has('cityChanged'));

            if(
                _currentCustomer() || (count($request->session()->get('cart', [])) > 0
                    || count($request->session()->get('wishList', [])) > 0
                    || count($request->session()->get('comparison', [])) > 0)
                || $request->session()->has('cityChanged')
            ) {
                $response->header('Last-Modified', $lastModified->format('D, d M Y H:i:s \G\M\T'));

                return $response;
            }

            $ifModifiedSince = $request->header('IF_MODIFIED_SINCE') ?? $request->header('IF_NONE_MATCH');

            if(is_object($response->original)) {

                $responseData = $response->original->getData();

                if (
                    is_array($responseData)
                    && array_key_exists('lastModified', $responseData)
                    && (is_string($responseData['lastModified']) || $responseData['lastModified'] instanceof Carbon)
                ) {

                    try {
                        $lastModified = (($responseData['lastModified'] instanceof Carbon) ? $responseData['lastModified'] : new Carbon($responseData['lastModified']));
                    } catch (\Throwable $e) {
                        $lastModified = new Carbon('1970-01-01 00:00:00'); // C'est la vie :3
                    }

                }

            } elseif($lastModifiedValue !== null) {

                try {
                    $lastModified = (($lastModifiedValue instanceof Carbon) ? $lastModifiedValue : new Carbon($lastModifiedValue));
                } catch (\Throwable $e) {
                    $lastModified = new Carbon('1970-01-01 00:00:00'); // C'est la vie :3
                }

            }

            $lastModified->setTimezone('+00:00');

            if ($ifModifiedSince !== null) {

                try {
                    $ifModifiedSince = new Carbon($ifModifiedSince);
                } catch(\Throwable $e) {
                    $ifModifiedSince = new Carbon('1970-01-01 00:00:00'); // They should send correct date, otherwise we suppose the minimal is sent
                }

                if ($ifModifiedSince >= $lastModified) {

                    $notModified = true;

                    if(Session::has('cityChangedAt')) {

                        $cityChangedAt = Session::get('cityChangedAt');

                        if($cityChangedAt instanceof Carbon) {

                            $cityChangedAt->setTimezone('+00:00');

                            if($cityChangedAt > $ifModifiedSince) {
                                $notModified = false;
                            }

                            $lastModified->setTimestamp($cityChangedAt->getTimestamp());

                        }

                    }

                    if($notModified === true) {
                        return response([], 304);
                    }
                }

            }

            $response->header('Last-Modified', $lastModified->format('D, d M Y H:i:s \G\M\T'));

        }

        return $response;
    }
}
