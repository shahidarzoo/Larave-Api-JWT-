@extends('admin.layouts.master')
@section('css')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{asset('admin/global/plugins/datatables/datatables.min.css')}}" rel="stylesheet" type="text/css"/>
    <link href="{{asset('admin/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css')}}"
          rel="stylesheet" type="text/css"/>
    <link href="{{asset('admin/global/plugins/bootstrap-sweetalert/sweetalert.css')}}" rel="stylesheet"
          type="text/css"/>

@endsection

@section('content')
    <div class="page-content">
        <!-- BEGIN PAGE HEAD-->
        <div class="page-head">
            <!-- BEGIN PAGE TITLE -->
            <div class="page-title">
                <h1>Products
                </h1>
            </div>
            <!-- END PAGE TITLE -->

        </div>
        <!-- END PAGE HEAD-->
        <!-- BEGIN PAGE BREADCRUMB -->
    {{--@include('admin.includes.breadcrumbs')--}}
    <!-- END PAGE BREADCRUMB -->
        <!-- BEGIN PAGE BASE CONTENT -->
        <div class="row">
            <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light bordered">

                    <div class="portlet-body">
                        <form id="product_list">
                            <input type="hidden" name="_token" value="{{csrf_token()}}">
                        <div class="table-toolbar">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn-group">
                                        <a href="{{url('admin/products/create')}}" id="sample_editable_1_new"
                                           class="btn sbold green"> Add New
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    </div>
                                    <div class="row">
                                        <div class="btn-group">
                                            <br/>
                                            <br/>
                                            <div class="col-md-7 ">
                                                <select name="product_status" class="bulk-status form-control">
                                                    <option value="">Select Status</option>
                                                    <option value="1">Active</option>
                                                    <option value="0">InActive</option>
                                                </select>
                                              </div>
                                            <div class="col-md-2">
                                                <input type="button" value="Update" class="btn sbold green"
                                                       onclick="update_bulk_status();">
                                            </div>

                                            <div class="col-md-2">
                                                
                                                    <input type="button" id="download-button" value="Download" class="btn sbold green float-right download-all" data-url="{{url('admin/product/download-all')}}" style="margin-left: 12px !important;">
                                            </div>
                                            <a class="zip-file" href="{{ url('uploads/product_images.zip') }}"></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if(isset($filters) && isset($filters['filter_p']) && $filters['filter_p'] == 1){ ?>
                                    <a class="btn btn-primary sbold pull-right" style="margin-right: 10px;" href="{{url('admin/products')}}"> Clear Filter </a>
                                    <?php } ?>
                                    <a class="btn sbold green-seagreen pull-right" data-toggle="modal" href="#filter_products" style="margin-right: 10px;"> Filter Product </a>
                                </div>

                            </div>
                        </div>
                        <div class="table-scr-wrapper">
                            <table class="table table-striped table-bordered table-hover table-checkable order-column"
                                   id="sample_1">
                                <thead>
                                <tr>
                                    <th>
                                        <label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">
                                            <input type="checkbox" class="group-checkable"
                                                   data-set="#sample_1 .checkboxes" id="download-master" />
                                            <span></span>
                                        </label>
                                    </th>
                                    <th>Id</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Seller</th>
                                    <th>Status</th>
                                    <th>Hide</th>
                                    <th>Created</th>
                                    <th>Notes for Boutiques</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php  foreach ($products as  $product) { 
                                    $comment_log = DB::table('comment_log as cl')->select('cl.*','u.first_name','u.last_name')
                                            ->join('users as u','u.id','cl.user_id','left')
                                            ->where('comment_type','=', 'product')
                                            ->where('entity_id','=', $product->id)
                                            ->where('cl.deleted_at','=', null)
                                            ->orderby('cl.id','desc')->get();
                                    ?>
                                <tr class="odd gradeX">
                                    <td>
                                        <label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">
                                            <input type="checkbox" class="checkboxes bulk-checkbox multiple_download" value=" {{$product->id}}" name="product_ids[]" data-id="{{$product->id}}"/>
                                            <span></span>
                                        </label>
                                    </td>
                                    {{csrf_field()}}
                                    </form>
                                    <td>
                                        {{$product->id}}
                                    </td>
                                    <td>
                                        {{$product->name}}
                                    </td>
                                    <td>{{number_format($product->price,2)}}</td>
                                    <td>{{$product->title}}</td>
                                    <td id="<?php if($product->status == 1){ echo 'Active';}else{echo 'orange';} ?>">
                                        <a href="javascript:;" onclick="changeStatus('{{$product->id}}')">
                                            <?php
                                                if($product->status == 1) {
                                                    ?>
                                                    <i class="icon-like"></i> Active
                                            <?php
                                                } else {
                                                    ?>
                                                    <i class="icon-dislike"></i> Inactive
                                                <?php
                                                }
                                            ?>
                                        </a>
                                    </td>
                                    <td  id="<?php if($product->hide_product == 1) { echo 'No'; }
                                    else{ echo 'orange'; } ?>">
                                        <a href="javascript:;" onclick="hide_product('{{$product->id}}')">
                                            <?php
                                                if($product->hide_product == 1) {
                                                    ?>
                                                     No
                                            <?php
                                                } else {
                                                    ?>
                                                     Yes
                                                <?php
                                                }
                                            ?>
                                        </a>
                                    </td>
                                    <td>{{$product->created_at}}</td>
                                    <td>
                                        <a class=" btn dark btn-outline sbold" data-toggle="modal" href="#comment{{$product->id}}"> View Comments </a>
                                        <div class="modal fade" id="comment{{$product->id}}" tabindex="-1" role="basic" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                                        <h4 class="modal-title">Comments</h4>
                                                    </div>
                                                    <div class="modal-body" style="text-align:left;">
                                                        <?php if($comment_log){ ?>
                                                            <table class="table table-hover table-bordered table-striped">
                                                                <tr>
                                                                    <td>Date</td>
                                                                    <td>User</td>
                                                                    <td>Comments</td>
                                                                </tr>
                                                            <?php foreach ($comment_log as $i_status){ ?>
                                                                <tr>
                                                                    <td><?php echo $i_status->created_at; ?></td>
                                                                    <td><?php echo $i_status->first_name.' '.$i_status->last_name; ?></td>
                                                                    <td><?php echo $i_status->comment_text; ?></td>
                                                                </tr>
                                                            <?php } ?>
                                                            </table>
                                                        <?php } ?>
                                                        <form class="" method="post" action="{{url('admin/products/save_comments')}}">
                                                            {{csrf_field()}}
                                                            <input type="hidden" value="{{$product->id}}" name="entity_id">
                                                            <div class="row">
                                                                <div class="form-body col-md-12 col-sm-12">
                                                                    <div class="form-group <?php
                                                                    if ($errors->has('comment_text')) {
                                                                        echo "has-error";
                                                                    }
                                                                    ?>">
                                                                        <label class="control-label col-md-4">Comments <span class="required-entry">*</span></label>
                                                                        <div class="col-md-8">
                                                                            <textarea class="form-control" name="comment_text" required></textarea>
                                                                            <?php
                                                                            if ($errors->has('comment_text')) {
                                                                                ?>
                                                                                <span class="help-block"> {{$errors->first('comment_text')}} </span>
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-actions right1 row">
                                                                <div class="form-body col-md-12 col-sm-12">
                                                                    <button type="button" class="btn default" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn green float-right">Submit</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" style="position: relative">
                                            <button class="btn btn-xs green dropdown-toggle" type="button"
                                                    data-toggle="dropdown" aria-expanded="false"> Actions
                                                <i class="fa fa-angle-down"></i>
                                            </button>
                                            <ul class="dropdown-menu pull-right" role="menu">
                                                <li>
                                                    <a href="{{url('admin/products/'.$product->id.'/edit')}}">
                                                        <i class="icon-pencil"></i> Edit 
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{url('admin/product/download-images/'.$product->id)}}">
                                                        <i class="icon-cloud-download"></i>  Download Photos 
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{url('admin/product/log/'.$product->id.'/0')}}">
                                                        <i class="icon-cloud-download"></i>  Product Log 
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:;" onclick="deleteIt('{{$product->id}}')">
                                                        <i class="icon-trash"></i> Delete 
                                                    </a>
                                                </li>
                                               
                                                <li>
                                                    <a href="{{url('admin/product/varitions/'.$product->id)}}">
                                                        <i class="icon-picture"></i> Variations & Images
                                                    </a>
                                                </li>

                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php

                                }
                                ?>

                                </tbody>
                            </table>
                        </div>
                        </form>
                    </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
            </div>
        </div>
        <!-- END PAGE BASE CONTENT -->
    </div>
<div class="modal fade filter_model_wrapper" id="filter_products" tabindex="-1" role="basic" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">Filters</h4>
            </div>
            <div class="modal-body" style="text-align:left;">
                <form class="form-horizontal" method="get" action="{{url('admin/products')}}">
                    {{csrf_field()}}
                    <div class="">
                        <div class="form-body">
                            <div class="form-group">
                                <label class="control-label col-md-4">Status </label>
                                <div class="col-md-8">
                                    <select class="form-control" name="status">
                                        <option value="">Select One</option>
                                        <option value="1" <?php if(isset($filters['status']) && $filters['status'] == 1){echo 'selected';};?>>Active</option>
                                        <option value="0" <?php if(isset($filters['status']) && $filters['status'] == 0){echo 'selected';};?>>inActive</option>
                                    </select>
                                    
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Boutique </label>
                                <div class="col-md-8">
                                    <select class="form-control" id="store_id" name="store_id">
                                        <option value="">Select One</option>
                                        <?php
                                        foreach ($stores as $store) {
                                            ?>
                                            <option value="{{$store->id}}" <?php
                                            if ($store->id == $filters['store_id']) {
                                                echo "selected=selected";
                                            }
                                            ?>>{{$store->title}}</option>
                                                    <?php
                                                }
                                                ?>
                                    </select>

                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Hidden </label>
                                <div class="col-md-8">
                                    <select class="form-control" name="hide_product">
                                        <option value="">Select One</option>
                                        <option value="0" <?php if(isset($filters['hide_product']) && $filters['hide_product'] == 0)echo 'selected';?>>Yes</option>
                                        <option value="1" <?php if(isset($filters['hide_product']) && $filters['hide_product'] == 1)echo 'selected';?>>No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions right1 row">
                        <div class="form-body col-md-12 col-sm-12">
                            <button type="button" class="btn default" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn green pull-right" name="filter_apply" value="yes">Apply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
    <script src="{{asset('admin/global/scripts/datatable.js')}}" type="text/javascript"></script>
    <script src="{{asset('admin/global/plugins/datatables/datatables.min.js')}}" type="text/javascript"></script>
    <script src="{{asset('admin/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js')}}"
            type="text/javascript"></script>
    <script src="{{asset('admin/pages/scripts/table-datatables-managed.min.js')}}" type="text/javascript"></script>
    <script src="{{asset('admin/global/plugins/bootstrap-sweetalert/sweetalert.min.js')}}"
            type="text/javascript"></script>
    <script src="{{asset('admin/pages/scripts/ui-sweetalert.min.js')}}" type="text/javascript"></script>
    <script src="{{asset('admin/js/product.js')}}" type="text/javascript"></script>
   <script src="{{asset('admin/js/download.js')}}" type="text/javascript"></script>
   <script src="{{asset('admin/js/download-jquery.js')}}" type="text/javascript"></script>

@endsection