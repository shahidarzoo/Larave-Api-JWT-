<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;
class CategoryController extends Controller
{
    public function index()
    {
    	try 
    	{
    		$categories = DB::table('categories')->where('is_featured', 1)
    			//->where('show_in_menu', 1)
    			->select('id','name', 'alias','image_url')
    			->get();
    			
    		if(count($categories) == 0)
    		{
    			return response()->json(['status_code' => 404, 'error' => 'Record not found'],404);
    		}
    		return response()->json(['data' => $categories, 'status_code' => 200],200);	
    	} 
    	catch (Exception $e) 
    	{
    		return Response::json(['status_code' => 500, 'error' => 'There is something wrong'], 500);
    	}
    }
}
