<?php

namespace TrueCore\App\Http\Controllers\Admin\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\{
    Facades\Artisan,
    Facades\Auth
};
use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;

trait Setting
{
    /**
     * @param array $input
     *
     * @return array
     */
    protected function processInput(array $input) : array
    {
        return $input;
    }

    /**
     * @param null $group
     *
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    public function edit($group = null)
    {
        if($this->hasPermission('read') === false) {
            return response()->json([], 403);
        }

        try {
            $this->data['settings'] = (($group !== null)
                ? $this->service::map($this->setting->get(null, $group), $group): $this->service::map($this->setting->get()));
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                                        'errors' => [
                                            'settings' => [
                                                $exception->getMessage(),
                                            ],
                                        ],
                                    ], 422);
        }

        return $this->response();
    }

    /**
     * @param Request $request
     * @param $group
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function update(Request $request, $group = null)
    {
        if($this->hasPermission('write') === false) {
            return response()->json([], 403);
        }

        try {
            $request = $this->requestUpdateClass::createFrom($request);
        } catch (\Throwable $e) {
            throw new RequestUpdateException('Cannot init update request, wrong class');
        }

//        $request->request->add([
//            'id' => $id,
//        ]);

        $v = Validator::make($request->all(), $request->rules(), $request->messages());

        if ($v->fails()) {
            $this->data['message'] = __('admin/validation.exception.invalid_parameters_message');
            $this->data['errors']  = $v->errors();

            return $this->response(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        (new $this->service)->edit($request->all(), $group);

        return response()->json([
                                    'message' => __('admin/common/form.update'),
                                ]);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View|void
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View|void
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function batch(Request $request)
    {
        //
    }

}
