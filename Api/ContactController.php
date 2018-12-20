<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use DB;
use Response;
use Auth;
use App\User;
use Mockery\Exception;
use Validator;
use App\UserInformation;

class ContactController extends Controller
{
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
            $contact_details = DB::table('users')
            ->leftJoin('user_information', 'users.id', '=', 'user_information.user_id')
            ->select(
                'users.id as user_id',
                'users.first_name as first_name',
                'users.last_name as last_name',
                'users.email as email',
                'users.address as address',
                'users.city',
                'users.phone_number'
            )
            ->where('users.id', $this->user->id)
            ->first();
            if(empty($contact_details))
            {
                return response()->json(['status_code'=>404, 'error' => 'Record Not found'], 404);
            }
            $contact = array(
                'user_id' => $contact_details->user_id,
                'first_name' => $contact_details->first_name,
                'last_name' => $contact_details->last_name,
                'email' => $contact_details->email,
                'address' => $contact_details->address,
                'city' => $contact_details->city,
                'phone_number' => $contact_details->phone_number,
            );
            return Response::json(['data'=>$contact,'status_code'=> 200 ],200);
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
    public function edit($id=null)
    {
        try 
        {
            $contact_details = DB::table('users')
            ->leftJoin('user_information', 'users.id', '=', 'user_information.user_id')
            ->select(
                'users.id as user_id',
                'users.first_name as first_name',
                'users.last_name as last_name',
                'users.email as email',
                'users.address as address',
                'users.city',
                'users.phone_number'
            )
            ->where('users.id', $this->user->id)
            ->first();
            if(empty($contact_details))
            {
                return response()->json(['status_code'=>404, 'error' => 'Record Not found'], 404);
            }
            $contact = array(
                'user_id' => $contact_details->user_id,
                'first_name' => $contact_details->first_name,
                'last_name' => $contact_details->last_name,
                'email' => $contact_details->email,
                'address' => $contact_details->address,
                'city' => $contact_details->city,
                'phone_number' => $contact_details->phone_number,
            );
            return Response::json(['data'=>$contact,'status_code'=> 200 ],200);
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
                'city' => 'required|max:20',
                'address' => 'required',
            ]);
            if($validator->fails())
            {
                return response()->json(['data' => $validator->errors(), 'status_codet' => 500], 500);
            }
            $input = $request->all();
            $id = $request->id;
            $user = User::findOrFail(Auth::user()->id);
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->email = $request->input('email');
            $user->phone_number = $request->input('country_code').$request->input('mobile');
            $user->country_code = $request->input('country_code');
            $user->phone_number2 = $request->input('mobile');
            $user->city = $request->input('city');
            $user->address = $request->input('address');
            $user->save();
            $user_info = UserInformation::where('user_id', $id)->first();
            if (!empty($user_info) && count($user_info) >= 1) 
            {
                $user_info->phone_number = $request->input('country_code').$request->input('mobile');
                $user_info->city = $request->input('city');
                $user_info->address = $request->input('address');
                $user_info->save();
            }

           return Response::json(['data'=> 'Contact info updated successfully','status_code'=> 200 ],200);
            
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
