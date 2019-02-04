<?php

namespace App\Http\Controllers\admin;

use App\Colors;
use App\Http\Requests\ProductsRequest;
use App\Models\Category;
use App\Models\CategoryRelation;
use App\ProductImages;
use App\Products;
use App\ProductLogs;
use App\ProductsToColours;
use App\ProductsToSizes;
use App\Sizes;
use App\StoresToProducts;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use File;
use App\Stores;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\ProductVariations;
use DB;
use App\ProductToTags;
use App\Tags;
use ZipArchive;

class ProductsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $products = DB::table('products')
                ->join('stores', 'stores.id', '=', 'products.store_id')
                ->select('products.*', 'stores.title')
                ->whereNull('products.deleted_at')
                ->groupBy("products.id")
                ->get();

        //$products = Products::all();
        return view('admin.products.index', [
            'products' => $products
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $categories = Category::getLiveCategories();
        $colors = Colors::getAll();
        $sizes = Sizes::all();
        $stores = Stores::where([
                    ['status', '=', 1],
                    ['deleted_at', null]
                ])->get();
        $product_templates = mh_meta_get_by_column(array('entity_key' => 'product_template', 'entity_type' => 'description_template'));
        $tags = Tags::all();


        return view('admin.products.add', [
            'categories' => $categories,
            'colors' => $colors,
            'sizes' => $sizes,
            'stores' => $stores,
            'tags' => $tags,
            'row_index' => 1,
            'product_templates' => $product_templates,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductsRequest $request) {
      

        $category = $request->input('categories');
        $category_alias = '';
        if ($category) {
            $category_alias = mh_get_column_by_id("categories", $category[0], 'alias');
        }

        $category_fields = $request->input($category_alias);

        $store_id = Stores::where('user_id', Auth::id())->take(1)->value('id');
        $product = new Products();
        $product->name = $request->input('name');
        $product->uuid = str_random(15);
        if ($request->input('description')) {
            $product->description = $request->input('description');
        }
        $product->quantity = 0;
        $product->price = $request->input('price');
        if($request->input('sale_price')){
            $product->sale_price = $request->input('sale_price');
        }
        
        $product->store_id = $request->input('store');
        $product->short_description = $request->input('short_description');
        $product->custom_sizes = json_encode($request->input('custom_sizes'));
        $product->slug = helper_get_unique_slug($request->input('name'), "products", "slug");
        $product->item_fabric = $request->input('item_fabric');
        $product->no_of_days = $request->input('no_of_days');
        $product->additionalComments = $request->input('additionalComments');
        $product->category_fields = json_encode($category_fields);
        $product->save();

        //making SKU
        $product = Products::findOrFail($product->id);
        $product->sku = $product->id . strtotime(Carbon::now()->format('Y-m-d H:i:s'));
        $product->save();
        $variation = $request->input('variation');
        if (!empty($variation) && count($variation) >= 1) {
            foreach ($variation['colors'] as $key => $vari) {
                $var = new ProductVariations();
                $var->product_id = $product->id;
                $var->size_id = $variation['sizes'][$key];
                $var->color_id = $variation['colors'][$key];
                $var->quantity = $variation['quantity'][$key];
                $var->price = 0;
                $var->save();
                unset($var);
            }
        }


        foreach ($request->input('categories') as $category) {
            $category_relation = new CategoryRelation();
            $category_relation->relation_type = 'product';
            $category_relation->entity_id = $product->id;
            $category_relation->cat_id = $category;
            $category_relation->save();
        }
        ///////////////////Sale Price Check & Entry Start///////////////////      
        $tags_id=$request->input('tags');
       
        add_edit_tags($tags_id,$request->input('price'),$request->input('sale_price'),$product->id);   
        ///////////////////Sale Price Check & Entry Entry///////////////////


        //storing colours
//        foreach ($request->input('colors') as $color) {
//            $product_colors = new ProductsToColours();
//            $product_colors->product_id = $product->id;
//            $product_colors->colour_id = $color;
//            $product_colors->save();
//        }
//
//        //storing sizes
//        foreach ($request->input('sizes') as $size) {
//            $product_sizes = new ProductsToSizes();
//            $product_sizes->product_id = $product->id;
//            $product_sizes->size_id = $size;
//            $product_sizes->save();
//        }


        return redirect('admin/products/index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        return redirect('admin/products');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $product = Products::findOrFail($id);
        $categories = Category::getLiveCategories();
        $colours = Colors::getAll();
        $sizes = Sizes::all();
        $product_to_tags = ProductToTags::where(['fk_product_id' => $id])->get();
        $stores = Stores::where([
                    ['status', '=', 1],
                    ['deleted_at', null]
                ])->get();
        $product_templates = mh_meta_get_by_column(array('entity_key' => 'product_template', 'entity_type' => 'description_template'));
        $product_categories = CategoryRelation::where('relation_type', 'product')
                        ->where('entity_id', $id)
                        ->get()->pluck('cat_id')->toArray();
        $product_colors = ProductsToColours::where('product_id', $id)->get()->pluck('colour_id')->toArray();
        $product_sizes = ProductsToSizes::where('product_id', $id)->get()->pluck('size_id')->toArray();

        $product_variaions = ProductVariations::where('product_id', $id)->get();
         $tags = Tags::all();
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'colors' => $colours,
            'sizes' => $sizes,
            'stores' => $stores,
            'tags' => $tags,
            'product_categories' => $product_categories,
            'product_colors' => $product_colors,
            'product_sizes' => $product_sizes,
            'variation' => $product_variaions,
            'product_templates' => $product_templates,
            'product_to_tags' => $product_to_tags,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProductsRequest $request, $id) {

        $category = $request->input('categories');
        $category_alias = '';
        if ($category) {
            $category_alias = mh_get_column_by_id("categories", $category[0], 'alias');
        }

        $category_fields = $request->input($category_alias);
        
        $this->editHistory($request, $id, $category_fields);

        $user_id = Auth::user()->id;
        $user_data = User::findOrFail($user_id);
        $user_type = $user_data->user_type;
        if ($user_type == 1) {
            $edit = 'Admin: ' . $user_data->first_name . ' ' . $user_data->last_name;
        } else {
            $edit = $user_data->first_name . ' ' . $user_data->last_name;
        }


        $product = Products::findOrFail($id);
        $product->name = $request->input('name');
        $product->price = $request->input('price');
        if($request->input('sale_price')){
            $product->sale_price = $request->input('sale_price');
        }else{
             $product->sale_price = 0;
        }
        
        $product->quantity = 0;
        if ($request->input('description')) {
            $product->description = $request->input('description');
        }
        $product->short_description = $request->input('short_description');
        $product->store_id = $request->input('store');
        $product->custom_sizes = json_encode($request->input('custom_sizes'));
        $product->edit_history = $edit;
        $product->item_fabric = $request->input('item_fabric');
        $product->no_of_days = $request->input('no_of_days');
        $product->additionalComments = $request->input('additionalComments');
        $product->category_fields = json_encode($category_fields);
        $product->save();

         /* Mail Chaimp */
        $product = curl_product_query($id);
        $data_array = curl_data_array($product);
        curlMailchimpUpdate($data_array, $product);

       
        /* End Mailchimp */

        $product_categories = CategoryRelation::where('relation_type', 'product')
                ->where('entity_id', $id)
                ->get();
        foreach ($product_categories as $product_category) {
            $product_category->delete();
        }

        foreach ($request->input('categories') as $categories) {
            $category_relation = new CategoryRelation();
            $category_relation->relation_type = 'product';
            $category_relation->entity_id = $id;
            $category_relation->cat_id = $categories;
            $category_relation->save();
        }
        $variation = $request->input('variation');
        if (!empty($variation) && count($variation) >= 1) {
            foreach ($variation['colors'] as $key => $vari) {
                if (!empty($variation['variation_id'][$key])) {
                    $var = ProductVariations::findOrFail($variation['variation_id'][$key]);
                    $var->product_id = $product->id;
                    $var->size_id = $variation['sizes'][$key];
                    $var->color_id = $variation['colors'][$key];
                    $var->quantity = $variation['quantity'][$key];
                    $var->price = $variation['price'][$key];;
                    $var->save();
                } else {
                    $var = new ProductVariations();
                    $var->product_id = $product->id;
                    $var->size_id = $variation['sizes'][$key];
                    $var->color_id = $variation['colors'][$key];
                    $var->quantity = $variation['quantity'][$key];
                    $var->price = 0;
                    $var->save();
                    unset($var);
                }
            }
        }
        
        ///////////////////Sale Price Check & Entry Start///////////////////      
        $tags_id=$request->input('tags');
        add_edit_tags($tags_id,$request->input('price'),$request->input('sale_price'),$product->id,'edit');   
        ///////////////////Sale Price Check & Entry Entry///////////////////
    
   

        return redirect('admin/products');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $product = Products::findOrFail($id);
        $product->delete();
        curlMailchimpDelete($id);
        return response()->json(['msg' => 'deleted']);
    }

    public function changeStatus($id) {
        /* Mail Chaimp */
        $product = curl_product_query($id);
        $data_array = curl_data_array($product);
        curlMailchimpStore($data_array, $product);
        /* Image Upload */
        $product = curl_product_image_query($id);
        $curl_Image_array = curl_Image_array($product);
        CurlImageUpdate($curl_Image_array, $product);          
        /* End Mailchimp */
        $product = Products::findOrFail($id);
        if ($product->status == 1) {
            $product->status = 0;
        } else if ($product->status == 0) {
            $product->status = 1;
        }
        $product->save();
        return response()->json(['msg' => 'status changed']);
    }

    public function hideProduct($id) {
        $product = Products::findOrFail($id);
        if ($product->hide_product == 1) {
            $product->hide_product = 0;
        } else if ($product->hide_product == 0) {
            $product->hide_product = 1;
        }
        $product->save();
        return response()->json(['msg' => 'status changed']);
    }

    public function images($id, $variation_id = null) 
    {
        $product = Products::findOrFail($id);
        $product_images = ProductImages::where('product_id', $product->id);
        if (!empty($variation_id)) 
        {
            $product_images = $product_images->where('variation_id', $variation_id);
        }
        $product_images = $product_images->get();

        if (sizeof($product_images) > 0) 
        {
            return view('admin.products.images', [
                'images' => $product_images,
                'product' => $product,
                'variation_id' => $variation_id
            ]);
        } 
        else 
        {
            return view('admin.products.upload-images', [
                'product' => $product,
                'variation_id' => $variation_id
            ]);
        }
    }

    public function uploadImages($id, $variation_id = null) {
        $product = Products::findOrFail($id);
        $product_images = ProductImages::where('product_id', $product->id)->get();
        return view('admin.products.upload-images', [
            'product' => $product,
            'variation_id' => $variation_id,
        ]);
    }

    public function deleteImage($id) {
        $product_image = ProductImages::findOrFail($id);
        if ($product_image->is_featured == 1) {
            return response()->json(['error' => '1', 'msg' => 'not deleted']);
        } else {
            $product_image->delete();
            return response()->json(['error' => 0, 'msg' => 'deleted']);
        }
    }

    public function setFeatured($id) {
        $product_image = ProductImages::findOrFail($id);
        $images = ProductImages::where('product_id', $product_image->product_id)->get();
        foreach ($images as $image) {
            $image->is_featured = 0;
            $image->save();
        }
        $product_image->is_featured = 1;
        $product_image->save();
        return response()->json(['error' => 0, 'msg' => ' Image Updated']);
    }

    public function variation_partial($next) {
        $colors = Colors::getAll();
        $sizes = Sizes::all();
        $response_data = array(
            'status' => 1,
            'message' => "product listing",
            'content' => view('admin.products.partials.variation', ['row_index' => $next, "colors" => $colors, "sizes" => $sizes])->render(),
            'next' => $next + 1,
        );
        return json_encode($response_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function variation_delete($id) {
        $product = ProductVariations::findOrFail($id);
        $product->delete();
        $response_data = array(
            'status' => 1,
            'message' => "deleted");
        return json_encode($response_data);
    }

    function variation_list($id) {
        $product = Products::findOrFail($id);
        $product_variations = ProductVariations::where('product_id', $id)->get();
        return view('admin.products.variation-list', [
            'product_variations' => $product_variations,
            'product_detail' => $product,
        ]);
    }

    /**
     * update the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update_status(Request $request) {
        $product_ids = $request->input('product_ids');
        $product_status = $request->input('product_status');
        $i = 0;
        foreach ($product_ids as $id) {
            $status = null;
            $status = DB::table('products')->where('id', '=', $id)->update(array('status' => $product_status));
            if ($status) {
                $i = $i + 1;
            }
        }
        return response()->json(['msg' => $i . ' Products status updated']);
    }

    public function downloadImages($product_id) {
        $directory_path = base_path() . '/public/uploads/products/' . $product_id . '/images/';

        $product_images = ProductImages::where('product_id', $product_id)->get();

        if ($product_images) {
            $zipFileName = 'product_' . $product_id . '_images.zip';
            $zip = new ZipArchive;
            $public_dir = public_path();
            if ($zip->open($public_dir . '/' . $zipFileName, ZipArchive::CREATE) === TRUE) {
                foreach ($product_images as $image) {
                    $image_path = $image->image_path;
                    $file_name = basename($image_path);
                    $zip->addFile($directory_path . $file_name, $file_name);
                }
                $zip->close();
            }
            $filetopath = $public_dir . '/' . $zipFileName;
            $headers = array(
                'Content-Type' => 'application/octet-stream',
                'Expires' => 0,
                'Content-Transfer-Encoding' => 'binary',
                'Content-Length' => filesize($filetopath),
                'Cache-Control' => 'private, no-transform, no-store, must-revalidate',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
            );

            if (file_exists($filetopath)) {
                return response()->download($filetopath, $zipFileName, $headers);
            }
        }
    }

    public function deleteImages($product_id) {
        $product_images = ProductImages::where('product_id', $product_id)->get();
        if ($product_images) {
            foreach ($product_images as $image) {
                $image->delete();
            }
        }
        return redirect('admin/product/varitions/' . $product_id);
    }

    public function productLogs($id, $log_id = 0) {
        $product = Products::findOrFail($id);
        if ($log_id == 0) {
            $product_log = ProductLogs::where('product_id', $id)->orderBy('created_at', 'DESC')->get()[0];
        } else {
            $product_log = ProductLogs::where('id', $log_id)->orderBy('created_at', 'DESC')->get()[0];
        }


        $categories = Category::getLiveCategories();
        $colours = Colors::getAll();
        $sizes = Sizes::all();
        $product_to_tags = ProductToTags::where(['fk_product_id' => $id])->get();
        $stores = Stores::where([
                    ['status', '=', 1],
                    ['deleted_at', null]
                ])->get();
        $product_templates = mh_meta_get_by_column(array('entity_key' => 'product_template', 'entity_type' => 'description_template'));
        $product_categories = CategoryRelation::where('relation_type', 'product')
                        ->where('entity_id', $id)
                        ->get()->pluck('cat_id')->toArray();
        $product_colors = ProductsToColours::where('product_id', $id)->get()->pluck('colour_id')->toArray();
        $product_sizes = ProductsToSizes::where('product_id', $id)->get()->pluck('size_id')->toArray();

        $product_variaions = ProductVariations::where('product_id', $id)->get();

        $product_log_options = DB::table('product_log')
                ->select('id', 'created_at')
                ->orderBy('created_at', 'DESC')
                ->where('product_id', $id)
                ->get();
        return view('admin.products.log', [
            'product' => $product,
            'categories' => $categories,
            'colors' => $colours,
            'sizes' => $sizes,
            'stores' => $stores,
            'product_categories' => $product_categories,
            'product_colors' => $product_colors,
            'product_sizes' => $product_sizes,
            'variation' => $product_variaions,
            'product_templates' => $product_templates,
            'product_to_tags' => $product_to_tags,
            'product_log' => $product_log,
            'product_log_options' => $product_log_options,
            'log_id' => $product_log->id,
        ]);
    }
    
    protected function editHistory(Request $request, $id, $category_fields) {

        $old_product = Products::findOrFail($id);
        $product_to_tags = ProductToTags::where(['fk_product_id' => $id])->get();

        $product_categories = CategoryRelation::where('relation_type', 'product')
                        ->where('entity_id', $old_product->id)
                        ->get()->pluck('cat_id')->toArray();
        $product_variaions = ProductVariations::where('product_id', $old_product->id)->get();
        $product_images = ProductImages::where('product_id', $old_product->id)->get();


        $product = new ProductLogs();
        $product->product_id = $old_product->id;
        $product->name = $old_product->name;
        $product->short_description = $old_product->short_description;
        $product->description = $old_product->description;
        $product->price = $old_product->price;
        $product->sale_price = $old_product->sale_price;
        $product->custom_sizes = $old_product->custom_sizes;
        $product->item_fabric = $old_product->item_fabric;
        $product->no_of_days = $old_product->no_of_days;
        $product->additionalComments = $old_product->additionalComments;
        $product->category_fields = $old_product->category_fields;
        $product->quantity = $old_product->quantity;
        $product->slug = $old_product->slug;
        $product->store_id = $old_product->store_id;
        $product->uuid = $old_product->uuid;
        $product->hide_product = $old_product->hide_product;
        $product->is_featured = $old_product->is_featured;
        $product->product_images = json_encode($product_images);
        $product->product_to_tags = json_encode($product_to_tags);
        $product->product_variations = json_encode($product_variaions);
        $product->product_to_category = json_encode($product_categories);
        $product->save();

    }

}
