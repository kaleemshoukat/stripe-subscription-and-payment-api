<?php

namespace App\Traits;

use Illuminate\Support\Arr;

trait ApiResponserTrait
{
    protected function success($message=null, $data=null, $code=200){
        $response=['status' => true, 'message'=>$message, 'data'=>$data];
        return response()->json($response, $code);
    }

    protected function error($message=null, $data=null, $code=422){
        $response=['status' => false, 'errors'=>[$message], 'data'=>$data];
        return response()->json($response, $code);
    }

    protected function custom_validation($validator){
        $messages= Arr::flatten($validator->messages()->get('*'));
        return [
            'status'=>false,
            'errors'=>$messages
        ];
    }
}
