<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\ShipmentReturns;
use App\OrderToStore;
use App\Products;
use App\Orders;
use Auth;
use DB;
use Response;
use Validator;

class ShipmentController extends Controller
{
    public $user;
    public function __construct()
    {
        $this->user = \JWTAuth::parseToken()->toUser();
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
            $shipment_return = ShipmentReturns::leftJoin('products', 'shipment_returns.product_id', '=', 'products.id')
                    ->where('shipment_returns.user_id', $this->user->id)
                    ->orderBy('shipment_returns.id', 'desc')
                    ->select(['shipment_returns.order_id','shipment_returns.status','shipment_returns.reason','shipment_returns.resolve_status','shipment_returns.created_at','products.id','products.name'])
                    ->get();
            if(empty($shipment_return))
            {
                return response()->json(['status_code' => 404, 'error' => 'Record not found'], 404);
            }
            return response()->json(['data' => $shipment_return, 'status_code' => 200], 200);  
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is something wrong'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try 
        {

            return response()->json(['data' => $shipment, 'status_code' => 200],200);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'something went wrong'], 500);
        }
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
                'order_id'  => 'required',
                'cart_id'   => 'required',
                'reason'    => 'required',
                'status'    => 'required',
                'product_id'    => 'required',
            ]);
            if($validator->fails())
            {
                return response()->json(['data' => $validator->errors(), 'status_codet' => 500], 500);
            }
            $order = Orders::where('order_number', $request->order_id)
                            ->where('user_id', $this->user->id)
                            ->select('order_number')
                            ->first();
            if(!$order)
            {
                return response()->json(['status_code' => 400, 'error' => 'Order number is not valid'], 400);
            }
            ShipmentReturns::create([
                'order_id'      => $request->order_id,
                'cart_id'       => $request->cart_id,
                'reason'        => $request->reason,
                'status'        => $request->status,
                'product_id'    => $request->product_id,
                'user_id'       => $this->user->id
            ]);
            return response()->json(['status_code' => 201, 'data' => 'Submitted successfully'], 201);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is something wrong'],500);    
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
