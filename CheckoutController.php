<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Colors;
use App\Credit;
use App\Countries;
use App\Mail\OrderCompleteMail;
use App\Mail\OrderCompleteMailToAdmin;
use App\Mail\OrderAbandonMailToAdmin;
use App\Mail\GuestWelcomeMail;
use App\Sizes;
use App\User;
use Cart;
use App\Models\Category;
use App\Models\CategoryRelation;
use App\Products;
use App\Coupons;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\DeliveryAddressRequest;
use App\DeliveryAddress;
use App\BillingAddress;
use App\Orders;
use Auth;
use App\UserInformation;
use App\OrderToStore;
use App\Shipments;
use App\Paypal;
use App\ProductVariations;
use App\Models\DeliveryCities;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use App\Models\EmailsFunc;
use App\Stores;
use App\EntityMeta;
use App\General;
use App\CustomMeasurements;
use Response;

class CheckoutController extends Controller
{
    /* Cart Add */
    public function cart(Request $request)
    {
    	try 
    	{
    		$user = \JWTAuth::parseToken()->toUser();
    		$user_id = $request->user_id;
    		if($user)
    		{
    			$deliver_address = DeliveryAddress::where("user_id", $user_id)->first();
	            $country = Countries::where("id", $deliver_address->country)->first();
	            
	            $users=User::findOrFail($user_id);
	            $users->phone_number = $deliver_address->mobile;
	            $users->country_code = $deliver_address->country_code;
	            $users->phone_number2 = $deliver_address->phone_number;
	            $users->address = $deliver_address->address1;
	            if($country)
	            {
	                 $users->country_id = $country->id;
	            }
	            $city_area = "";
	            if ($deliver_address->country == 'United Arab Emirates' && is_numeric($deliver_address->city)) 
	            {
	                $delivery_city = DeliveryCities::where('id', $deliver_address->city)->first();
	                $city_area = $delivery_city->area;
	            } 
	            else 
	            {
	                $city_area = $deliver_address->city;
	            }
	            $users->city = $city_area;
	            $users->save();
    		}
		
	        /* Order Store to database */
	       	$orders = new Orders();
	        $order_number = rand(100, 999) . time();
	        $orders->order_number = $order_number;
	        $orders->user_id = $request->user_id;
	        $orders->total_price = $request->total_price;
	        $orders->buyer_credit_amount_used = $request->buyer_credit_amount_used;
	        $orders->discount_price = $request->discount_price;
	        $orders->tax_amount = $request->tax_amount;
	        $orders->delivery_store_item_count = $request->entity_value;
	        $orders->order_details = $request->order_detail;
	        $order_res = $orders->save();

	         /* Loop of order_details field form order table */
	        foreach ($order_details['Products_information'] as $product_key => $order_product) 
	        {
	        	/* Save to OrderToStore */
		        $order_to_store = new OrderToStore();
	            $order_to_store->order_id = $orders->id;
	            $order_to_store->product_id = $order_product->id;
	            $order_to_store->user_id = $user->id;
	            $order_to_store->store_id = $order_product->options['store_id'];
	            $order_to_store->variation_id = $order_product->options['variation_id'];
	            $order_to_store->price = $order_product->price;
	            $order_to_store->qty = $order_product->qty;
	            $order_to_store->tax_rate = $order_product->taxRate;
	            $order_to_store->tax = $order_product->tax;
	            $order_to_store->shiping_amount = $delivery_charges;
	            $order_to_store->subtotal = $order_product->subtotal;
	            $order_to_store->size_id = $order_product->options['size'];
	            $order_to_store->color_id = $order_product->options['color_id'];
	            $order_to_store->custom_spec = $order_product->options['custom_spec'];
	            $order_to_store->cart_data = json_encode($order_product);
	            $order_to_store->status = 'in_progress';
	            $order_to_store->cart_id = $order_product->rowId;
	            $order_to_store->save();
	           
	            /* Save to Shipments */
	            $shipments = new Shipments();
	            $shipments->order_id = $orders->id;
	            $shipments->order_to_store_id = $order_to_store->id;
	            $shipments->shipment_status_id = 1;
	            $shipments->shipment_number = null;               
	            $shipments->save();
	        }
	        return ressponse()->json(['status_code' => 200, 'data' => 'Successfully Checkout']);
	    } 
    	catch (Exception $e) 
    	{
    		return Response::json(['status_code' => 500, 'error' => 'There is something wrong'], 500);
    	}
    }
    /* Coupon Code */
    function coupon_code(Request $request) 
    { 
        try 
        {
        	$promo_code = $request->promo_code;
	        if (empty($promo_code)) 
	        {
	           return response()->json(['status_code'=> 500, 'error' => 'Promo code is invalid', 'status' => false],500);
	        }
	        $today = date("Y/m/d");
	        $coupon_row = Coupons::where([
	                    ['coupon_code', "=", $promo_code],
	                    ['expire_date', ">=", $today],
	                    ['attempt_no', ">=", 1],
	                ])->first();
            if($coupon_row->expire_date < $today)
            {
                return response()->json(['status_code'=> 500, 'error' => 'Promo code has expired', 'status' => false],500);
            }
	        if (!empty($coupon_row) && count($coupon_row) >= 1) 
	        {
	            $percentage = (double) $coupon_row['discount_percentage'];
	            $checkout_coupon = [
	            	'code' => $coupon_row->coupon_code,
	            	'percentage' => $percentage, 
	            	'coupon_type' => $coupon_row->coupons_type
	            ];
	        } 

	     	return response(['data' => $checkout_coupon, 'status_code' => 200, 'status' => true],200);
        } 
        catch (Exception $e) 
        {
        	return Response::json(['status_code' => 500, 'error'=> 'There is someting wrong', 'status' => false],500);
        }
    }
    /* Number Verification */
    function verify_number(Request $request) 
    {
        try 
        {
        	 $validator = Validator::make($request->all(), [
                'country_code' => 'required|max:20',
                'mobile_no' => 'required|max:20',
            ]);
            if($validator->fails())
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500, 'status' => false], 500);
            }
        	$phone_number = $request->country_code.$request->mobile_no;
	        $response = '';
	        if ($phone_number) 
	        {
	            $otp = mt_rand(100000, 999999);
	            $message = "Dear customer! You have placed an order #" . $request->order_number . ". Please enter this code " . $otp . " to verify your phone number Boksha LLC";
	            $response = helper_sms_send($phone_number, $message);
		        $response_data = array(
		            "response" => $response,
		            "status" => $status,
		            "message" => $message
		        );
		    }
        	return response()->json(['data' => $otp, 'status_code' => 200, 'status' => true], 200);
        } 
        catch (Exception $e) 
        {
        	return Response::json(['status_code' => 500, 'error' => 'There is someting wrong', 'status' => false], 500);
        }
    }
    /* Shipping add record */
    public function delivery_store(Request $request)
    {
        try 
        {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name' => 'required',
                'country_code' => 'required',            
                'mobile' => 'required',
                'email' => 'required|email',
                'address1' => 'required',
                'country' => 'required',
            ]);
            if($validator->fails())
            {
                return response()->json(['data' => $validator->errors(), 'status_codet' => 500, 'status' => false], 500);
            }
            $user = User::where("email", $request->email)->first();
            if(!$user)
            {
                $user = new User();
                    $new_password = str_random(8);
                    $user->first_name = $request->first_name;
                    $user->last_name = $request->last_name;
                    $user->email = $request->email;
                    $user->password = bcrypt($new_password);
                    $user->address = $request->address1;
                    $user->phone_number = $request->country_code.$request->mobile;
                    $user->country_code = $request->country_code;
                    $user->phone_number2 = $request->mobile;
                    $user->country_id = $request->country;
                    $user->city = $request->city;
                    $user->user_type = 3;
                    $user->status = 1;
                    $user->uuid = str_random(15);
                    $user->save();
            }

            $country = Countries::where('id', $request->country)->first();
            $delivery = DeliveryAddress::where('user_id', $user->id)->first();
            if(count($delivery) > 0)
            {
                return response()->json(['status_code' => 500, 'data' => 'Record already exist', 'status' => flase], 500);
            }
            else
            {
                $delivery_address = DeliveryAddress::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->mobile,
                    'mobile' => $request->country_code.$request->mobile,
                    'country_code' => $request->country_code,
                    'email' => $request->email,
                    'address1' => $request->address1,
                    'address2' => $request->address2,
                    'country' => $country->name,
                    'city' => $request->city,
                    'user_id' => $user->id,
                    'postcode' => $request->postcode,
                    'customer_type' => "buyer",
                ]);
                if($request->bill_to_address  == 1)
                {
                    $delivery_address = BillingAddress::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'phone_number' => $request->mobile,
                        'mobile' => $request->country_code.$request->mobile,
                        'country_code' => $request->country_code,
                        'email' => $request->email,
                        'address1' => $request->address1,
                        'address2' => $request->address2,
                        'country' => $country->name,
                        'city' => $request->city,
                        'user_id' => $user->id,
                        'postcode' => $request->postcode,
                        'customer_type' => "buyer",
                    ]);
                }
                return response()->json(['status_code' => 201, 'data' => 'Submitted successfully', 'status' => true], 201);
            }
            
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is something wrong', 'status' => false],500);    
        }
    }
    /* Billing record */
    public function billing_store(Request $request)
    {
        try 
        {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name' => 'required',
                'user_id' => 'required|',
                'email' => 'required',
                'country_code' => 'required',
                'mobile' => 'required|max:20',
                'country' => 'required|max:20',
                'city' => 'required|max:100',
                'address1' => 'required',
            ]);
            if($validator->fails())
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500, 'status' => false], 500);
            }
            $billing_address = BillingAddress::where("user_id", $request->user_id)
                                ->orWhere('email', $request->email)
                                ->first();
            if(!empty($billing_address) && count($billing_address)>=1)
            {
                return response()->json(['status_code'=>500, 'error' => 'Record already exist', 'status' => false], 500);
            }
            else
            {

                $billing = BillingAddress::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->mobile,
                    'mobile' => $request->country_code.$request->mobile,
                    'country_code' => $request->country_code,
                    'email' => $request->email,
                    'address1' => $request->address1,
                    'address2' => $request->address2,
                    'country' => $request->country,
                    'city' => $request->city,
                    'user_id' => $request->user_id,
                    'postcode' => $request->postcode,
                    'customer_type' => "buyer",
                ]);

               return response()->json(['data'=> 'Billing address created successfully','status_code'=> 201 , 'status' => true],201);
            }
            return Response::json(['status_code'=> 203, 'data'=> 'unbale to create address.', 'status' => false],203);
            
        } 
        catch (JWTException $e) 
        {
            return response()->json(['status_code'=>500, 'error' => 'there is something wrong', 'status' => false], 500);
        }
    }

    /* Shipping process basis on country and city */
    public function shipping(Request $request)
    {
        try 
        {
            $country = "";
            $city = "";
            $user = DB::table('users')->where('id', $request->user_id)->first();
            if (!empty($user) && $user != null) 
            {
                $deliver_address = DeliveryAddress::where("user_id", $user->id)->first();
                if (!empty($deliver_address) && count($deliver_address) >= 1) 
                {
                    if ($deliver_address->country == 'United Arab Emirates') 
                    {
                        $country = 229;
                    } 
                    else 
                    {
                        $country = $deliver_address->country;
                    }
                    $city = $deliver_address->city;
                } 
                else if ($user_id) 
                {
                    $country = $user->country_id;
                    $city = $user->city;
                }
             }
            $place_id = (!empty($deliver_address->place_id) ? $deliver_address->place_id : "");
            $delivery_charges = helper_delivery_charges($country, $city, true, $place_id, $request->store_count);
            $delivery_charges = $delivery_charges* $request->store_count;

            return response()->json(['data' => $delivery_charges, 'status_code'=> 200, 'status' => true], 200);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is something wrong', 'status' => 'false'],500);    
        }
    }

}
