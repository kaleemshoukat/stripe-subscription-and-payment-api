<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    use ApiResponserTrait;

    public $stripe;
    public function __construct(){
        $this->stripe= new \Stripe\StripeClient(
            env('STRIPE_SECRET')
        );
    }

    public function createProduct(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:255',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->custom_validation($validator);
        }

        try {
            $stripe_product=$this->stripe->products->create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $product=new Product();
            $product->name=$request->name;
            $product->description=$request->description;
            $product->stripe_product_id=$stripe_product->id;
            $product->save();

            return $this->success('Product created successfully.');
        }
        catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    public function createPrice(Request $request){
        $validator = Validator::make($request->all(),[
            'product_id' => 'required',
            'price' => 'required',
            'billing_cycle' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->custom_validation($validator);
        }

        try {
            if ($request->billing_cycle=='1 Month'){
                $interval=['interval' => 'month'];
            }
            else if ($request->billing_cycle=='6 Months'){
                $interval=[
                        'interval' => 'month',
                        "interval_count" => 6
                    ];
            }
            else if ($request->billing_cycle=='1 Year'){
                $interval=['interval' => 'year'];
            }

            $product=Product::find($request->product_id);
            $stripe_price=$this->stripe->prices->create([
                'unit_amount' => $request->price*100,
                'currency' => 'usd',
                'recurring' => $interval,
                'product' => $product->stripe_product_id,
            ]);

            $product_price=new ProductPrice();
            $product_price->product_id=$request->product_id;
            $product_price->price=$request->price;
            $product_price->billing_cycle=$request->billing_cycle;
            $product_price->stripe_price_id=$stripe_price->id;
            $product_price->save();

            return $this->success('Product price created successfully.');
        }
        catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    public function createSubscription(Request $request){
        $validator = Validator::make($request->all(),[
            'product_price_id'=>'required',
            'number'=>'required',
            'exp_month'=>'required',
            'exp_year'=>'required',
            'cvc'=>'required',
        ]);
        if ($validator->fails()) {
            return $this->custom_validation($validator);
        }

        try {
            if (is_null(Auth::user()->stripe_customer_id)){
                //create token with card details
                $token=$this->stripe->tokens->create([
                    'card' => [
                        'number' => $request->number,
                        'exp_month' => $request->exp_month,
                        'exp_year' => $request->exp_year,
                        'cvc' => $request->cvc,
                    ],
                ]);

                //create customer with above generated token
                $customer = $this->stripe->customers->create([
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'source' => $token->id,
                ]);
                $stripe_customer_id=$customer->id;

                //update stripe_customer_id in user table
                User::where('id', Auth::user()->id)->update(['stripe_customer_id'=>$stripe_customer_id]);
            }
            else{
                $stripe_customer_id=Auth::user()->stripe_customer_id;
            }

            //create subscription
            $product_price=ProductPrice::find($request->product_price_id);
            $stripe_subscription=$this->stripe->subscriptions->create([
                'customer' => $stripe_customer_id,
                'items' => [
                    ['price' => $product_price->stripe_price_id],  //stripe price_id is based on product price and its billing cycle
                ],
            ]);

            $subscription=new Subscription();
            $subscription->user_id=Auth::user()->id;
            $subscription->product_price_id=$request->product_price_id;
            $subscription->stripe_subscription_id=$stripe_subscription->id;
            $subscription->save();

            return $this->success('User subscribed to selected package successfully.');
        }
        catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    public function cancelSubscription(){
        $subscription=Subscription::where(['user_id'=>Auth::user()->id, 'status'=>SubscriptionStatus::ACTIVE])->orderBy('id', 'DESC')->first();
        if ($subscription){
            try {
                $this->stripe->subscriptions->cancel(
                    $subscription->stripe_subscription_id,
                    []
                );

                //update status
                $subscription->status=SubscriptionStatus::DISABLED;
                $subscription->save();

                return $this->success('User subscription cancelled successfully.');
            }
            catch (\Exception $e){
                return $this->error($e->getMessage());
            }
        }
        else{
            return $this->error('User do not have any subscription');
        }
    }



}
