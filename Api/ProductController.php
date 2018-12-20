<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Products;
use DB;
use Response;
use App\Models\Category;
use App\CustomMeasurements;
use Illuminate\Session\Store;
use App\Models\CategoryRelation;
use App\Stores;
use App\ProductVariations;
use App\Tags;

class ProductController extends Controller
{
	// Product Details
    public function index(Request $request)
    {
    	try 
    	{
    		$products = Products::select(['products.id', 'products.name', 'products.short_description', 'products.price', 'products.sale_price'])
    				->where('products.deleted_at', '=', null)
    				->groupBy('products.store_id')
    				->paginate(10);
    		if(count($products) == 0)
    		{
    			return response()->json(['status_code' => 404, 'error' => 'Records not found'], 404);
    		}
    		return response()->json(['data'=> $products, 'status_code' => 200], 200);
    	} 
    	catch (Exception $e) 
    	{
    		return Response::json(['status_code' => 500, 'error' => 'There is an error'], 500);	
    	}
    }
 	// Product Base on Category
    public function category_products($alias)
    {
    	try 
    	{

            $product_to_cateogry = Category::join('category_relations', 'categories.id', '=', 'category_relations.cat_id')
    					->leftJoin('products', 'category_relations.entity_id', '=', 'products.id')
    					->leftJoin('stores', 'products.store_id', '=', 'stores.id')
    					->leftJoin('product_to_tags as ptags', 'products.id','=', 'ptags.fk_product_id')
    					->leftJoin('category_to_tags as ctags', 'ctags.fk_tags_id', '=', 'categories.id')
    					->groupBy('products.store_id')
    					->where('category_relations.deleted_at', null)
    					->where('categories.alias',$alias)
    					->where('categories.deleted_at',null)
    					->paginate(10);
    		if(count($product_to_cateogry) == 0)
    		{
    			return response()->json(['status_code' => 404, 'error' => 'Records not fount'], 404);
    		}
    		return response()->json(['data' => $product_to_cateogry, 'status_code' => 200], 200);
    	} 
    	catch (Exception $e) 
    	{
    		return Response::json(['status_code' => 500, 'error' => 'There is something wrong'], 500);
    	}
    }
    // single product with slug
    public function show($slug)
    {
    	try 
    	{
    		$product = Products::select('products.*')
    				->where('products.deleted_at', '=', Null)
    				->where('products.slug', $slug)
    				->groupBy('products.store_id')
    				->first();
    		if(count($product) == 0)
    		{
    			return response()->json(['status_code' => 404, 'error' => 'Record not found'], 404);
    		}
    		return response()->json(['data' => $product, 'status_code' => 200], 200);
    	} 
    	catch (Exception $e) 
    	{
    		return Response::json(['status_code' => 500, 'error' => 'There is some thing wrong'], 500);
    	}
    }
}
