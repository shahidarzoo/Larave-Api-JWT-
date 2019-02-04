<?php

namespace App\Http\Controllers\admin;
use Illuminate\Support\Facades\Session;

use App\Colors;
use App\Fabric;
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
use Excel;
use Chumper\Zipper\Zipper;
use App\CommentLog;

class ProductsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        
        $filters = array();
        $query = DB::table('products')
                ->join('stores', 'stores.id', '=', 'products.store_id')
                ->select('products.*', 'stores.title')
                ->whereNull('products.deleted_at')
                ->groupBy("products.id");
        if (!empty($_GET['filter_apply'])) {
            $filters['status'] =  $status = $request->input('status');
            if($status != '' && ($status == 0 || $status == 1)){
                $query->where('products.status',$status);
                $filters['filter_p'] = 1;
            }
            $filters['store_id'] =  $store_id = $request->input('store_id');
            if($store_id != ''){
                $query->where('products.store_id',$store_id);
                $filters['filter_p'] = 1;
            }
            $filters['hide_product'] =  $hide_product = $request->input('hide_product');
            if($hide_product != '' && ($hide_product == 0 || $hide_product == 1)){
                $query->where('products.hide_product',$hide_product);
                $filters['filter_p'] = 1;
            }
        }
        
        
//        $query = get_raw_query($query);
//        dd($query);
        
//        $products = $query->limit(20)->get();
        /*$query->limit(10);
        $query->orderBy('id', 'desc');*/
        $products = $query->get();
        
        $stores = DB::table('stores')
                ->join('products', 'products.store_id', '=', 'stores.id')
                ->select('stores.id', 'stores.title')
                ->whereNotNull('products.id')
                ->groupBy("stores.id")
                ->get();

        //$products = Products::all();
        return view('admin.products.index', [
            'filters' => $filters,
            'stores' => $stores,
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
        $fabric = Fabric::getAll();
        $sizes = Sizes::all();
        $stores = Stores::where([
                    ['status', '=', 1],
                    ['deleted_at', null]
                ])->get();
        $product_templates = mh_meta_get_by_column(array('entity_key' => 'product_template', 'entity_type' => 'description_template'));
        $tags = Tags::all_tags_without_sale();


        return view('admin.products.add', [
            'categories' => $categories,
            'colors' => $colors,
            'fabric' => $fabric,
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
      

        $category_fields = $request->input('category_fields');

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
        $product->item_fabric = isset($category_fields)?$category_fields['item_fabric']:'';
        $product->no_of_days = $request->input('no_of_days');
        $product->additionalComments = $request->input('additionalComments');
        $product->category_fields = json_encode($category_fields);
        $product->save();
        $fabric = $request->input('item_fabric');
        $db_fabric = DB::table('fabric')->where("name", 'like', "%{$fabric}%")->take(1)->first();
        if(!$db_fabric){
            $store_fabric = new Fabric();
            $store_fabric->name = $fabric;
            $store_fabric->save();
        }

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
        $fabric = Fabric::getAll();
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
        $tags = Tags::all_tags_without_sale();
         $comment_log = DB::table('comment_log as cl')->select('cl.*','u.first_name','u.last_name')
                                            ->join('users as u','u.id','cl.user_id','left')
                                            ->where('comment_type','=', 'product')
                                            ->where('entity_id','=', $product->id)
                                            ->where('cl.deleted_at','=', null)
                                            ->orderby('cl.id','desc')->get();
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'colors' => $colours,
            'fabric' => $fabric,
            'sizes' => $sizes,
            'stores' => $stores,
            'tags' => $tags,
            'product_categories' => $product_categories,
            'product_colors' => $product_colors,
            'product_sizes' => $product_sizes,
            'variation' => $product_variaions,
            'product_templates' => $product_templates,
            'product_to_tags' => $product_to_tags,
            'comment_log' => $comment_log,
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


        $category_fields = $request->input('category_fields');
        
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
        $product->item_fabric = isset($category_fields)?$category_fields['item_fabric']:'';
        $product->no_of_days = $request->input('no_of_days');
        $product->additionalComments = $request->input('additionalComments');
        $product->category_fields = json_encode($category_fields);
        $product->save();
        $fabric = $request->input('item_fabric');
        $db_fabric = DB::table('fabric')->where("name", 'like', "%{$fabric}%")->take(1)->first();
        if(!$db_fabric){
            $store_fabric = new Fabric();
            $store_fabric->name = $fabric;
            $store_fabric->save();
        }

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
        return response()->json(['msg' => 'deleted']);
    }

    public function changeStatus($id) {
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

    public function images($id, $variation_id = null) {
        $product = Products::findOrFail($id);
        $product_images = ProductImages::where('product_id', $product->id);
        if (!empty($variation_id)) {
            $product_images = $product_images->where('variation_id', $variation_id);
        }
        $product_images = $product_images->get();
        if (sizeof($product_images) > 0) {
            return view('admin.products.images', [
                'images' => $product_images,
                'product' => $product,
                'variation_id' => $variation_id,
                'product_id' => $id
            ]);
        } else {
            return view('admin.products.upload-images', [
                'product' => $product,
                'variation_id' => $variation_id,
                'product_id' => $id
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
            'product_id' => $id,
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

        $query = "SELECT * from product_images where product_id = ".$product_id." AND deleted_at is null";
        $product_images = DB::select($query);

        if ($product_images) {
            $zipFileName = 'product_' . $product_id . '_images.zip';
            $zip = new ZipArchive;
            $public_dir = public_path('uploads');
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
            }else{
                return back();
            }
        }else{
//            return redirect('admin/products');
            return back();
        }
    }


    public function downloads_all(Request $request) 
    {
        $public_dir = '';
        $$zipFileName = '';
        $zip = '';
        $product_images =  DB::table('product_images')
                    ->where('deleted_at', null)
                    ->whereIn('product_id',  explode(',', $request->ids))
                    ->orderby('product_id', 'desc')
                    ->get();
        if ($product_images) 
        {
            unlink(public_path('uploads/product_images.zip'));
            $remove = public_path('uploads/download-images');
            delete_directory($remove);
            //die();
            $zipFileName = 'product_images.zip';
            $zip = new ZipArchive;
            $public_dir = public_path('uploads/download-images');
            if (!is_dir($public_dir)) 
            {
                @mkdir($public_dir, 0777, true);
            }
            /*if ($zip->open($public_dir . '/' . $zipFileName, ZipArchive::CREATE) === TRUE) 
            {*/
                foreach ($product_images as $image) 
                {
                    
                    $directory_path = base_path() . '/public/uploads/products/' . $image->product_id . '/images/';
                    $image_path = $image->image_path;
                    $file_name = basename($image_path);
                    $imagePath = $public_dir.'/'.$image->product_id;
                    @mkdir($imagePath);
                    copy($directory_path.$file_name, $public_dir.'/'.$image->product_id.'/'.$file_name);
                }
               
           /* }
                $zip->close();*/
            $files = glob(public_path('uploads/download-images'));
            \Zipper::make(public_path('uploads/product_images.zip'))->add($files)->close();
            $fileurl =  public_path('uploads/product_images.zip');
    
            
            if (file_exists($fileurl)) 
            {
                return 'success';
                //return response()->download($fileurl);
            }
            else
            {
                return back();
            }
        }
        else
        {
            return back();
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
    public function import() {

        $categories = Category::getLiveCategories();
             $colors = Colors::getAll();
        $sizes = Sizes::all();
        $stores = Stores::where([
                    ['status', '=', 1],
                    ['deleted_at', null]
                ])->get();
        //  echo '<pre>'; print_r($stores); echo '</pre>';

        $product_templates = mh_meta_get_by_column(array('entity_key' => 'product_template', 'entity_type' => 'description_template'));
        $tags = Tags::all();


        return view('admin.products.import_products', [
            'categories' => $categories,
            'colors' => $colors,
            'sizes' => $sizes,
            'stores' => $stores,
            'tags' => $tags,
            'row_index' => 1,
            'product_templates' => $product_templates,
        ]);
    }
    public function import_submit(Request $request){
        //validate the xls file
        $this->validate($request, array(
            'file'      => 'required',
            'store'     => 'required',
            'category'  => 'required'
        ));
 
        if($request->hasFile('file')){
            $extension = File::extension($request->file->getClientOriginalName());
            if ($extension == "xlsx" || $extension == "xls" || $extension == "csv") {
                $path = $request->file->getRealPath();
                $data = Excel::load($path, function($reader) {
                })->get();

                if(!empty($data) && $data->count()){

                    $error_html = "<ul>";
                    $error = false;
                        $count1=1;
                        foreach ($data  as $key =>$sheet){
                            foreach ($sheet  as $row){
                            
                                $value = $row;
                            
                                
                                /*Error If Not Record Submit Code Start*/
                                $row_error = array();
                                $row_error_html = '<span>Row ' . $count1 . ': Missing Fields: </span>';
                                $row_error_status = false;
                                // if ($value->store_id == '')
                                // {
                                //     $row_error[] = 'Store ID';
                                //     $row_error_status = true;
                                // }
                                if ($value->name == '')
                                {
                                    $row_error[] = 'Name';
                                    $row_error_status = true;
                                }
                                if ($value->short_description == '')
                                {
                                    $row_error[] = 'Short Description';
                                    $row_error_status = true;
                                }
                                if ($value->price == '')
                                {
                                    $row_error[] = 'Price';
                                    $row_error_status = true;
                                }
                                if ($value->item_fabric == '')
                                {
                                    $row_error[] = 'Item Fabric';
                                    $row_error_status = true;
                                }
                                if ($value->no_of_days == '')
                                {
                                    $row_error[] = 'No of Days';
                                    $row_error_status = true;
                                }


                                if ($row_error_status == false)
                                {
                                    $category_fields= array();
                                    if($value->sheila_included){
                                    $category_fields['sheila_included']=$value->sheila_included;
                                    }
                                    if($value->sheila_type){
                                    $category_fields['sheila_type']=$value->sheila_type;
                                    }
                                    if($value->sheila_fabric){
                                    $category_fields['sheila_fabric']=$value->sheila_fabric;
                                    }
                                    if($value->sheila_size){
                                    $category_fields['sheila_size']=$value->sheila_size;
                                    }
                                    if($value->belt_included){
                                    $category_fields['belt_included']=$value->belt_included;
                                    }
                                    if($value->cleaning){
                                    $category_fields['cleaning']=$value->cleaning;
                                    }
                                    if($value->measurements){
                                    $category_fields['measurements']=$value->measurements;
                                    }
                                
                                    $product = new Products();
                                    $product->name = $value->name;
                                    $product->uuid = str_random(15);
                                    if ($value->description) {
                                        $product->description = $value->description;
                                    }
                                    $product->quantity = 0; 
                                    $product->price = $value->price;  

                                    if($value->sale_price){
                                        $product->sale_price = $value->sale_price;
                                    }

                                    $product->store_id = trim($request->input('store'));
                                    $product->short_description = $value->short_description;
                                    
                                    if($value->custom_sizes){
                                        $custom_sizes = explode(',', $value->custom_sizes);
                                        $product->custom_sizes = json_encode($custom_sizes);
                                    }
                                                                    
                                    $product->item_fabric = $value->item_fabric;
                                    $product->no_of_days = $value->no_of_days;
                                    if($value->comments){
                                        $product->additionalComments = $value->comments;
                                    }
                                    if($category_fields){
                                    $product->category_fields = json_encode($category_fields);
                                    }
                                    $product->status = 0;
                                    $product->slug = helper_get_unique_slug($value->name, "products", "slug");
                                    $product->save();

                                    $product = Products::findOrFail($product->id);
                                    $product->sku = $product->id . strtotime(Carbon::now()->format('Y-m-d H:i:s'));
                                    $product->save(); 

////////////////Color///////////////////
if(is_numeric($value->colour_id)){
    $Colors = Colors::where([
        ['id', '=', $value->colour_id]
       
    ])->first();
}elseif(is_string($value->colour_id)){
        $Colors = Colors::where([
        ['name', '=', $value->colour_id]
       
    ])->first();
}
    if(count($Colors)>0)
    {
        $colour_id1=$Colors->id;

    }else{
            $colour_id1=1;
    }
////////////////Color///////////////////
////////////////Size///////////////////
    $all_sizes = array();
    if($value->sizes_id){
        $all_sizes = explode(',', $value->sizes_id);
    }
    $sizes = array();
    if($all_sizes){
        foreach ($all_sizes as $size){
            $db_size = Sizes::where([['id', '=', $size]])->first();
            if(!$db_size){
                $db_size = Sizes::where([['name', '=', $size]])->first();
            }
            if($db_size){
                $sizes[] = $db_size->id;
            }else{
                $siz = new Sizes();
                $siz->name = $size;
                $siz->code = $size;
                $siz->price = 0;
                $siz->save();
                $sizes[] = $siz->id;
            }
        }
    }else{
        $sizes[] = 7;
    }
    foreach ($sizes as $size){
        $var = new ProductVariations();
        $var->product_id = $product->id;
        $var->size_id = $size;
        $var->color_id = $colour_id1;
        $var->quantity = $value->quantity_item;
        $var->price = 0;
        $var->save();
    }
       
   
////////////////Size///////////////////
    
                                    

                                    
                                
                               
                                    $category_relation = new CategoryRelation();
                                    $category_relation->relation_type = 'product';
                                    $category_relation->entity_id = $product->id;
                                    $category_relation->cat_id = trim($request->input('category'));
                                    $category_relation->save();
                                   
                                   
                                    $tags_id=array();
                                    $tags_id=explode(',', $value->tags_id);
                                    add_edit_tags($tags_id,$value->price,$value->sale_price,$product->id);


                                }else{
                                    $error = true;
                                    $error_html .= "<li>";
                                    $error_html .= $row_error_html;
                                    if ($row_error)
                                    {
                                        $count = 0;
                                        foreach ($row_error as $row_e)
                                        {
                                            $count++;
                                            if ($count > 1)
                                            {
                                                $error_html .= ' - ' . $row_e;
                                            }
                                            else
                                            {
                                                $error_html .= ' ' . $row_e . ' ';
                                            }
                                        }
                                    }

                                    $error_html .= "</li>";
                                }
                                /*Error If Not Record Submit Code End*/
                            
                            }
                        }
                }
                $error_html .= "<li><strong style='color: green;'>Note: Other rows added Successfully</strong></li></ul>";
                if ($error == false)
                {
                    Session::flash('message', 'Your Data has successfully imported');
                    return back();
                }
                else
                {
                    Session::flash('test', $error_html);
                    return back();
                }
                   
                
 
            }else {
                Session::flash('message', 'File is a '.$extension.' file.!! Please upload a valid xls/csv file..!!');
                return back();
            }
        }
    }
    
    public function save_comments(Request $request) {
        $entity_id = $request->input('entity_id');
        $comment_text = $request->input('comment_text');
        $user_id = Auth::user()->id;
        $comment = new CommentLog();
        $comment->user_id = $user_id;
        $comment->comment_type = 'product';
        $comment->entity_id = $entity_id;
        $comment->comment_text = $comment_text;
        $comment->save();
        flash_message('Your comments added successfully');
        return back();
    }

}
