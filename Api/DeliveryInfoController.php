<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryInfoRequest;
use App\DeliveryAddress;
use App\Countries;
use DB;
use Response;
use Auth;
use Validator;

class DeliveryInfoController extends Controller
{
    public $user;
    public function __construct()
    {
        $this->user = \JWTAuth::parseToken()->authenticate();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try 
        {
            $countries = Countries::all();
            $delivery_address = DeliveryAddress::where("user_id", $this->user->id)->first();
            if(empty($delivery_address))
            {
                return response()->json(['status_code'=>404, 'error' => 'Record Not found'], 404);
            }
            $shipping = array(
                "id" => $delivery_address->id,
                "first_name" =>  $delivery_address->first_name,
                "last_name" =>  $delivery_address->last_name,
                "email" =>  $delivery_address->email,
                "country_code" =>  $delivery_address->country_code,
                "phone_number" =>  $delivery_address->phone_number,
                "address1" =>  $delivery_address->address1,
                "address2" =>  $delivery_address->address2,
                "country" =>  $countries,
                "city" =>  $delivery_address->city,
            );
            return Response::json(['data'=>$shipping, 'status_code'=> 200 ],200);
        } 
        catch (Exception $e) 
        {
            return response()->json(['status_code'=>500, 'error' => 'there is something wrong'], 500);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try 
        {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:20',
                'last_name' => 'required|max:20',
                'email' => 'required|unique:users,email,' . Auth::user()->id,
                'country_code' => 'required',
                'mobile' => 'required|max:20',
                'country' => 'required|max:20',
                'city' => 'required|max:100',
                'address1' => 'required',
            ]); 
            if($validator->fails())
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
            }
            $billing_address = DeliveryAddress::where("user_id", $this->user->id)->first();
            if(!empty($billing_address) && count($billing_address)>=1)
            {
                return response()->json(['status_code'=>500, 'error' => 'Record already exist'], 500);
            }
            else
            {
                $billing = DeliveryAddress::create([
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
                    'user_id' => $this->user->id,
                    'postcode' => $request->postcode,
                    'customer_type' => "buyer",
                ]);

               return Response::json(['data'=> 'Shippig address created successfully','status_code'=> 200 ],200);
            }
            return Response::json(['status_code'=> 203, 'data'=> 'unbale to create shipping address.'],203);
            
        } 
        catch (JWTException $e) 
        {
            return response()->json(['status_code'=>500, 'error' => 'there is something wrong'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id=null)
    {
        try 
        {
            if($id === null)
            {
                return response()->json(['status_code'=>404, 'error' => 'The page you looking for Not found'], 404);
            }
            $countries = Countries::all();
            $delivery_address = DeliveryAddress::where("user_id", $this->user->id)->where('id',$id)->first();
            if(empty($delivery_address))
            {
                return response()->json(['status_code'=>404, 'error' => 'Record Not found'], 404);
            }
            $shipping = array(
                "id" => $delivery_address->id,
                "first_name" =>  $delivery_address->first_name,
                "last_name" =>  $delivery_address->last_name,
                "email" =>  $delivery_address->email,
                "country_code" =>  $delivery_address->country_code,
                "phone_number" =>  $delivery_address->phone_number,
                "address1" =>  $delivery_address->address1,
                "address2" =>  $delivery_address->address2,
                "country" =>  $countries,
                "city" =>  $delivery_address->city,
            );
            return Response::json(['data'=>$shipping, 'status_code'=> 200 ],200);
        } 
        catch (Exception $e) 
        {
            return response()->json(['status_code'=>500, 'error' => 'there is something wrong'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try 
        {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:20',
                'last_name' => 'required|max:20',
                'email' => 'required|unique:users,email,' . Auth::user()->id,
                'country_code' => 'required',
                'mobile' => 'required|max:20',
                'country' => 'required|max:20',
                'city' => 'required|max:100',
                'address1' => 'required',
            ]);
            if($validator->fails())
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
            }
            $billing_address = DeliveryAddress::where("user_id", $this->user->id)->first();
            $billing_address->first_name = $request->first_name;
            $billing_address->last_name = $request->last_name;
            $billing_address->phone_number = $request->mobile;
            $billing_address->mobile = $request->country_code.$request->mobile;
            $billing_address->country_code = $request->country_code;
            $billing_address->email = $request->email;
            $billing_address->address1 = $request->address1;
            $billing_address->address2 = $request->address2;
            $billing_address->country = $request->country;
            $billing_address->city = $request->city;
            $billing_address->user_id = $this->user->id;
            $billing_address->postcode = $request->postcode;
            $billing_address->customer_type = "buyer";
            $res = $billing_address->save();

           return Response::json(['data'=> 'Shippig address updated successfully','status_code'=> 200 ],200);
            
        } 
        catch (JWTException $e) 
        {
            return response()->json(['status_code'=>500, 'error' => 'there is something wrong'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
