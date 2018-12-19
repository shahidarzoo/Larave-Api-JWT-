<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Dispute;
use App\Orders;
use Illuminate\Support\Facades\Validator;
use DB;
use Response;

class MessageController extends Controller
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
            $messages = Dispute::where('user_id', $this->user->id)
                        ->groupBy('order_id', 'product_id')
                        ->orderBy('id','desc')
                        ->get();
            if(empty($messages))
            {
                return response()->json(['status_code'=>404, 'error' => 'Record Not found'], 404);
            }
            return Response::json(['data' => $messages, 'status_code' => 200], 200);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'Something wrong'], 500);
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
                'order_id' => 'required',
                'message'=> 'required'
            ]);
            if ($validator->fails()) 
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
            }
            $dispute = new Dispute();
            $dispute->message = $request->message;
            $dispute->order_id = $request->order_id;
            $dispute->user_id = $this->user->id;
            $dispute->save();
            return Response::json(['data'=> 'Message Submitted successfully','status_code'=> 201 ],201);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is Something wrong'],500);   
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
        try
        {
            $message_data = Dispute::where('user_id', $this->user->id)
                    ->where('id', $id)
                    ->first();
            $message = array([
                'id' => $message_data->id,
                'order_id' => $message_data->order_id,
                'status' => $message_data->status
            ]);
            return Response::json(['data' => $message, 'status_code' => 200], 200);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is Something wrong'], 500);   
        }
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
