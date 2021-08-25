<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponserTrait;

    public $stripe;
    public function __construct(){
        $this->stripe= new \Stripe\StripeClient(
            env('STRIPE_SECRET')
        );
    }

    public function deductPayment(Request $request){
        $validator = Validator::make($request->all(),[
            'number' => 'required',
            'exp_month' => 'required',
            'exp_year' => 'required',
            'cvc' => 'required',
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->custom_validation($validator);
        }

        try {
            $user=User::where('id', Auth::guard('user')->user()->id)->first();
            if (is_null($user->stripe_charge_id)) {
                $token = $this->stripe->tokens->create([
                    'card' => [
                        'number' => $request->number,
                        'exp_month' => $request->exp_month,
                        'exp_year' => $request->exp_year,
                        'cvc' => $request->cvc,
                        'name' => $request->name,
                    ],
                ]);

                $charge = $this->stripe->charges->create([
                    'amount' => 5000,
                    'currency' => 'usd',
                    'description' => 'Payment is reserved for stripe api project.',
                    'source' => $token->id,
                    //"capture"=> false,      //payment will not be captured only it will be holded in stripe account
                ]);

                //to capture payment use this
//                $capture=$this->stripe->charges->capture(
//                    $charge->id,
//                    []
//                );

                $user->stripe_charge_id=$charge->id;
                $user->save();
                return $this->success('User payment reserved successfully.');
            }
            else{
                return $this->error('User payment is already reserved.');
            }
        }
        catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    public function refundPayment(){
        try {
            $user=User::where('id', Auth::guard('user')->user()->id)->first();
            if (!is_null($user->stripe_charge_id)){
                $refund = $this->stripe->refunds->create([
                    //'amount' => 1000,     //For custom amount refund parameter
                    'charge' => $user->stripe_charge_id,
                ]);

                $user->stripe_charge_id=null;
                $user->save();
                return $this->success('User payment refunded successfully.');
            }
            else{
                return $this->error('No payment found to refund.');
            }
        }
        catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }
}
