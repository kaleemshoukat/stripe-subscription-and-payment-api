<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponserTrait;

    public function login(Request $request){
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->custom_validation($validator);
        }

        $user=User::where(['email'=>$request->email])->first();
        if ($user){
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Password Grant to User')->accessToken;
                $data=['user'=>$user, 'token'=>$token];
                return $this->success('User Logged In.', $data);
            }
            else {
                return $this->error('Email or Password entered is incorrect.');
            }
        }
        else {
            return $this->error('Email or Password entered is incorrect.');
        }
    }

    public function logout(){
        try {
            Auth::guard('user')->user()->token()->revoke();
            return $this->success('User logged out successfully.');
        }
        catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

}
