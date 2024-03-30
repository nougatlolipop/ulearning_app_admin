<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Customer;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Carbon;
use App\Models\Course;
use App\Models\Order;

class PayController extends Controller
{
    public function checkout(Request $request) {
        try {
            $user = $request->user();
            $token = $user->token;
            $courseId = $request->id;

            Stripe::setApiKey('sk_test_51OzFmk01ghwMge8lyEYzXQ4WyvBUOEHyVuSDFgYqd9TwXI3cfgDtOAurRLQ3iCM5fDl0c8SrOts7Zj97M7RApYEl00wHUmu0wm');

            $courseResult = Course::where('id','=',$courseId)->first();

            if (empty($courseResult)) {
                return response()->json([
                    'code'=>400,
                    'msg' => 'Course does not exist'
                ],400);
            }

            $orderMap=[];

            $orderMap['course_id']=$courseId;
            $orderMap['user_token']=$token;
            $orderMap['status']=1;

            $orderRes= Order::where($orderMap)->first();

            if (!empty($orderRes)) {
                return response()->json([
                    'code'=>400,
                    'msg' => 'You already bought this course',
                    'data' => ""
                ]);
            }

            $YOUR_DOMAIN = env('APP_URL');
            $map=[];
            $map['user_token']=$token;
            $map['course_id']=$courseId;
            $map['total_amount']=$courseResult->price;
            $map['status']=0;
            $map['created_at']=Carbon::now();
            $orderNum=Order::insertGetId($map);
            $checkOutSession=Session::create(
                [
                    'line_items'=>[[
                        'price_data'=>[
                            'currency'=>'USD',
                            'product_data'=>[
                                'name'=>$courseResult->name,
                                'description'=>$courseResult->description,
                            ],
                            'unit_amount'=>intval($courseResult->price*100),
                        ],
                        'quantity'=>1,
                    ]],
                    'payment_intent_data'=>[
                        'metadata'=>[
                            'order_num'=>$orderNum,
                            'user_token'=>$token
                        ],
                    ],
                    'metadata'=>[
                        'order_num'=>$orderNum,
                        'user_token'=>$token
                    ],
                    'mode'=>'payment',
                    'success_url'=>$YOUR_DOMAIN.'success',
                    'cancel_url'=>$YOUR_DOMAIN.'cancel',
                ]
            );

            return response()->json([
                'code'=>200,  
                'msg'=>'success',
                'data' => $checkOutSession->url
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                'code'=>500,
                'msg' => $th->getMessage(),
                'data'=>""
            ],500);
        }
    }

    public function web_go_hooks()
    {
       // Log::info("11211-------");
        Stripe::setApiKey('sk_test_51OzFmk01ghwMge8lyEYzXQ4WyvBUOEHyVuSDFgYqd9TwXI3cfgDtOAurRLQ3iCM5fDl0c8SrOts7Zj97M7RApYEl00wHUmu0wm');
        $endpoint_secret = 'whsec_Yf6pHbVzGvLux4ykVmxmTCvJA6qHwBpW';
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
      //  Log::info("payload----" . $payload);

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
          //  Log::info("UnexpectedValueException" . $e);
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
        //    Log::info("SignatureVerificationException" . $e);
            http_response_code(400);
            exit();
        }
        //   Log::info("event---->" . $event);
        // Handle the checkout.session.completed event
        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
           // Log::info("event->data->object---->" . $session);
            $metadata = $session["metadata"];
            $order_num = $metadata->order_num;
            $user_token = $metadata->user_token;
        //    Log::info("order_id---->" . $order_num);
            $map = [];
            $map["status"] = 1;
            $map["updated_at"] = Carbon::now();
            $whereMap = [];
            $whereMap["user_token"] = $user_token;
            $whereMap["id"] = $order_num;
            Order::where($whereMap)->update($map);
        }


        http_response_code(200);
    }
}
