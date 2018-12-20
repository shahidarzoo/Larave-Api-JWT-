<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Orders;
use Mockery\Exception;
use DB;
use Response;

class OrderController extends Controller
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
            $order_data = Orders::where('user_id', $this->user->id)
                ->where('status' ,'!=','unPaid')
                ->where('status' ,'!=','cancelled')
                ->where('status' ,'!=','declined')
                ->orderBy('id', 'desc')
                ->select('*')
                ->get();
            if(empty($order_data))
            {
                return response()->json(['status_code'=>404, 'error' => 'Record Not found'], 404);
            }
            return Response::json(['data'=>$order_data, 'status_code'=> 200 ],200);
        } 
        catch (Exception $e)
        {
            return Response::json(['status_code'=>500, 'error' => 'there is something wrong'], 500);
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
        //
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
