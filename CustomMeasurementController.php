<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CustomMeasurements;
use App\Models\Category;
use App\User;
use Mockery\Exception;
use DB;
use Response;
use Auth;
use Validator;

class CustomMeasurementController extends Controller
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

            $measurements = CustomMeasurements::where('user_id', $this->user->id)
                    ->join('categories', 'custom_measurements.cat_id', '=', 'categories.id')
                    ->orderBy('custom_measurements.id', 'decs')
                    ->select('custom_measurements.id','custom_measurements.name','custom_measurements.custom_sizes','custom_measurements.is_default','custom_measurements.created_at','categories.name as cat_name')
                    ->get();
            if(empty($measurements))
            {
                return response()->json(['status_code' => 404, 'errpr' => 'Record not found'],404);
            }
            return response()->json(['data' => $measurements, 'status_code' => 200],200);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'there is something wrong'],500);    
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
                'cat_id'     => 'required',
                'name'       => 'required',
                'is_default' => 'required|integer',
            ]);
            if($validator->fails())
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
            }
            $sizes['Length']          = $request->length;
            $sizes['Shoulder Width']  = $request->shoulder_width;
            $sizes['Chest']           = $request->chest_size;
            $sizes['Hips']            = $request->hips_size;
            $sizes['Waist']           = $request->waist_size;
            $sizes['Sleeves Length']  = $request->sleeves_length;
            $sizes['Sleeves Width']   = $request->sleeves_width;
            $sizes['Trousers Length'] = $request->trousers_length;
            $measurements = CustomMeasurements::create([
                'user_id' => $this->user->id,
                'cat_id' => $request->cat_id,
                'name' => $request->name,
                'is_default' => $request->is_default,
                'custom_sizes' => json_encode($sizes),
                'status' => 1
            ]);
            return Response::json(['status_code' => 201, 'data' => 'Custome Measurement Created Successfully'], 201);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 505, 'error' => 'There is an error'],500);
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
        try 
        {
            $measurement = CustomMeasurements::where('user_id', $this->user->id)
                            ->where('id', $id)
                            ->first();
            $custom_sizes = json_decode($measurement->custom_sizes,true);
            $categories  = Category::get();
            $data  = array(
                'name' => $measurement->name,
                'is_default' => $measurement->is_default,
                'custom_sizes' => $custom_sizes,
                'categories' => $categories
            );
            if(!$measurement)
            {
                return response()->json(['status_code' => 404, 'error' => 'Record Not Found'], 404);
            }
            return response()->json(['data' => $data, 'status_code'=> 200],200);    
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'something wrong'], 500);
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
            $validator = Validator::make($request->all(),[
                'cat_id'     => 'required',
                'name'       => 'required',
                'is_default' => 'required|integer'
            ]);
            if($validator->fails())
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500],500);
            }
            $sizes['Length']          = $request->length;
            $sizes['Shoulder Width']  = $request->shoulder_width;
            $sizes['Chest']           = $request->chest_size;
            $sizes['Hips']            = $request->hips_size;
            $sizes['Waist']           = $request->waist_size;
            $sizes['Sleeves Length']  = $request->sleeves_length;
            $sizes['Sleeves Width']   = $request->sleeves_width;
            $sizes['Trousers Length'] = $request->trousers_length;

            CustomMeasurements::where("id", $request->id)->update([
                'name'         => $request->name,
                'cat_id'       => $request->cat_id,
                'is_default'   => $request->is_default,
                'user_id'      => $this->user->id,
                'custom_sizes' => json_encode($sizes),
            ]);

            return response()->json(['status_code' => 200, 'data' => 'Record Updated Successfully'],200);
        } 
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500, 'error' => 'There is something wrong'], 500);
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
         $measurement = CustomMeasurements::find($id);
        $measurement->delete();
        return response()->json(['status_code' => 200, 'data' => 'Record Delete Successfully'],200);
    }
}
