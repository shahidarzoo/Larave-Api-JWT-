<?php

namespace App\Http\Controllers\admin;

use App\Stores;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\OrdersDetails;
use App\Invoices;
use App\OrderToStore;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        $best_boutiques = DB::select("select count(s.id) as store_count,SUM(ots.qty) as total_qty,DATE(ots.created_at),SUM(ots.subtotal) as subtotal, s.title,s.slug,s.id from stores as s
inner JOIN order_to_store as ots ON s.id=ots.store_id
INNER JOIN orders as o On ots.order_id=o.id
WHERE DATE(ots.created_at)>=DATE('2017-08-23') and DATE(ots.created_at)<=DATE('2017-08-24')
GROUP BY s.id
ORDER BY  total_qty DESC,count(s.id)");
        $filter = (object)['status' => 'unpaid'];

//       dd($best_boutique);
        return view('admin.reports.best-boutique', [
            'best_boutiques' => $best_boutiques,
            'filter' => $filter,
        ]);
    }

    function best_boutique(Request $request)
    {
        if ($_POST) {
            $this->validate($request, [
                'date_from' => 'required',
                'date_to' => 'required',

            ]);

            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');
            $order_status = $request->input('order_status');
            $order_item_status = $request->input('order_item_status');
            if (!empty($order_status)) {
                //$status = " AND o.`status`='$order_status' ";
                $status = " AND ots.`boutique_status`='$order_status' ";
            } else {
                $status = "";
            }
            $query = "select count(s.id) as store_count,SUM(ots.qty) as total_qty,DATE(ots.created_at),SUM(ots.subtotal) as subtotal, s.title,s.slug,s.id from stores as s
                    inner JOIN order_to_store as ots ON s.id=ots.store_id
                    INNER JOIN orders as o On ots.order_id=o.id
                    WHERE DATE(ots.created_at)>=DATE('" . $date_from . "') and DATE(ots.created_at)<=DATE('" . $date_to . "') " . $status . " " . $item_status . "
                    GROUP BY s.id
                    ORDER BY  total_qty ASC ,count(s.id)";
            $best_boutiques = DB::select($query);
            $filter = (object)['status' => $request->input('order_status')];
            $filter = (object)['status' => $request->input('order_status'),'item_status' => $request->input('order_item_status')];
//       dd($query);
        } else {
            $best_boutiques = array();
            $filter = (object)['status' => '','item_status' => ''];
            $date_from = "";
            $date_to = "";
        }

        return view('admin.reports.best-boutique', [
            'best_boutiques' => $best_boutiques,
            'filter' => $filter,
            'date_to' => $date_to,
            'date_from' => $date_from,
        ]);
    }

    function best_product(Request $request)
    {
        if ($_POST) {
            $this->validate($request, [
                'date_from' => 'required',
                'date_to' => 'required',

            ]);

            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');
            $order_status = $request->input('order_status');
            $order_item_status = $request->input('order_item_status');
            if (!empty($order_status)) {
                $status = " AND o.`status`='$order_status' ";
            } else {
                $status = "";
            }

            if (!empty($order_status)) {
                $item_status = " AND ots.`boutique_status`='$order_status' ";
            } else {
                $item_status = "";
            }

            $query = "select count(p.id) as product_count,SUM(ots.qty) as total_qty,DATE(ots.created_at),SUM(ots.subtotal) as subtotal, p.`name`,p.slug,p.id from products as p
                    inner JOIN order_to_store as ots ON p.id=ots.product_id
                    INNER JOIN orders as o On ots.order_id=o.id
                    WHERE DATE(ots.created_at)>=DATE('" . $date_from . "') and DATE(ots.created_at)<=DATE('" . $date_to . "')  " . $status . " " . $item_status . "
                    GROUP BY p.id
                    ORDER BY  total_qty DESC,count(p.id)";
            $best_product = DB::select($query);
            $filter = (object)['status' => $request->input('order_status'),'item_status' => $request->input('order_item_status')];
        } else {
            $best_product = array();
            $filter = (object)['status' => '','item_status' => ''];
            $date_from = "";
            $date_to = "";
        }

        return view('admin.reports.best-product', [
            'best_products' => $best_product,
            'filter' => $filter,
            'date_to' => $date_to,
            'date_from' => $date_from,
        ]);
    }

    function orders(Request $request)
    {
        if ($_POST) {
            $this->validate($request, [
                'date_from' => 'required',
                'date_to' => 'required',
                'period' => 'required',

            ]);

            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');
            $order_status = $request->input('order_status');
            $period = $request->input('period');
            if (!empty($order_status)) {
                $status = " AND o.`status`='$order_status' ";
            } else {
                $status = "";
            }
            if ($period == 'daily') {
                $query = "select count(DISTINCT o.id) as order_count,DATE(o.created_at) as created_at,SUM(o.total_price) as subtotal, SUM(o.discount_price) as discount_price ,SUM(o.tax_amount) as tax_amount  from orders as o

                    WHERE DATE(o.created_at)>=DATE('" . $date_from . "') and DATE(o.created_at)<=DATE('" . $date_to . "')  " . $status . "
                    GROUP BY o.id
                    ORDER BY  o.created_at DESC,count(o.id)";
                $filter_type = (object)['status' => 'daily'];
            } else {
                $query = "select count(DISTINCT o.id) as order_count,DATE(o.created_at) as created_at,SUM(o.total_price) as subtotal, SUM(o.discount_price) as discount_price ,SUM(o.tax_amount) as tax_amount  from orders as o
                     WHERE DATE_FORMAT(o.created_at, '%y-%m')>=DATE_FORMAT('" . $date_from . "', '%y-%m')  AND DATE_FORMAT(o.created_at, '%y-%m')<=DATE_FORMAT('" . $date_to . "', '%y-%m')
                   " . $status . "
                    GROUP BY o.id
                    ORDER BY  o.created_at DESC,count(o.id)";
                $filter_type = (object)['status' => 'monthly'];
            }
            $best_product = DB::select($query);
            $filter = (object)['status' => $request->input('order_status')];
//        dd($query);
        } else {
            $best_product = array();
            $filter = (object)['status' => ''];
            $filter_type = (object)['status' => ''];
            $date_from = "";
            $date_to = "";
        }

        return view('admin.reports.sales-orders', [
            'best_products' => $best_product,
            'filter' => $filter,
            'filter_type' => $filter_type,
            'date_to' => $date_to,
            'date_from' => $date_from,
        ]);
    }


    function orders_report(Request $request)
    {
        if (!empty($_GET['filters'])) {
            $this->validate($request, [
                'date_from' => 'required',
                'date_to' => 'required',
                'period' => 'required',

            ]);

            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');
            $order_status = $request->input('order_status');
            $period = $request->input('period');
            $boutique = $request->input('boutique');
            $boutique_order_status="od.oss_status='Accepted' AND ";
            if (!empty($order_status)) {
                if($order_status=="cancelled"){
                    $boutique_order_status="";
                }
                $status = " AND od.`order_status`='$order_status'";
            } else {
                $status = "";
            }
            if (!empty($boutique)) {
                $store_clause = " AND od.`ots_store_id`='$boutique'";
            } else {
                $store_clause = "";
            }
            if ($period == 'daily') {
                $format = "Y/m/d";
                $filter_type = (object)['status' => 'daily'];
                $date_clasuse = "(DATE(od.order_created_at)>=DATE('" . $date_from . "') and DATE(od.order_created_at)<=DATE('" . $date_to . "'))  ";
            } else {
                $format = "Y/m";
                $filter_type = (object)['status' => 'monthly'];
                $date_clasuse = "(DATE_FORMAT(od.order_created_at, '%y-%m')>=DATE_FORMAT('" . $date_from . "', '%y-%m')  AND DATE_FORMAT(od.order_created_at, '%y-%m')<=DATE_FORMAT('" . $date_to . "', '%y-%m'))";
            }
            $query = "select * from (SELECT
	`o`.`id` AS `order_id`,
	`o`.`user_id` AS `order_user_id`,
	`o`.`order_number` AS `order_number`,
	`o`.`total_price` AS `order_total_price`,
	`o`.`discount_price` AS `order_discount_price`,
	`o`.`tax_amount` AS `order_tax_amount`,
	`o`.`status` AS `order_status`,
	`o`.`checkout_method` AS `checkout_method`,
	`o`.`created_at` AS `order_created_at`,
	`o`.`order_details` AS `order_details`,
	`ots`.`order_id` AS `ots_order_id`,
	`ots`.`store_id` AS `ots_store_id`,
	`ots`.`variation_id` AS `ots_variation_id`,
	`ots`.`price` AS `ots_price`,
	`ots`.`qty` AS `ots_qty`,
	`ots`.`tax_rate` AS `ots_tax_rate`,
	`ots`.`tax` AS `ots_tax`,
	`ots`.`shiping_amount` AS `ots_shiping_amount`,
	`ots`.`subtotal` AS `ots_subtotal`,
	`ots`.`size_id` AS `ots_size_id`,
	`ots`.`color_id` AS `ots_color_id`,
	`ots`.`cart_data` AS `ots_cart_data`,
	`ots`.`custom_spec` AS `ots_custom_spec`,
	`ots`.`status` AS `ots_status`,
	`ots`.`created_at` AS `ots_created_at`,
	`oss`.`order_id` AS `oss_order_id`,
	`oss`.`store_id` AS `oss_store_id`,
	`oss`.`status` AS `oss_status`,
	`oss`.`shipment_status` AS `oss_shipment_status`
FROM
	(
		(
			`orders` `o`
			JOIN `order_to_store` `ots` ON (
				(`o`.`id` = `ots`.`order_id`)
			)
		)
		LEFT JOIN `order_store_status` `oss` ON (
			(
				(
					`ots`.`order_id` = `oss`.`order_id`
				)
				AND (
					`ots`.`store_id` = `oss`.`store_id`
				)
			)
		)
	))  as od
              where ".$boutique_order_status."". $date_clasuse . " " . $status . "".$store_clause;
            $orders_list = DB::select($query);
            $orders_data = array();
            $orders_data_date = array();
            foreach ($orders_list as $key => $value) {
                $orders_data[$value->ots_store_id][date_format(date_create($value->order_created_at), $format)][$value->order_number][] = $value;
                $orders_data_date[date_format(date_create($value->order_created_at), $format)][$value->order_id][$value->ots_store_id][] = $value;
            }
            $filter = (object)['status' => $request->input('order_status')];
//        dd($query);
        } else {
            $best_product = array();
            $filter = (object)['status' => ''];
            $filter_type = (object)['status' => ''];
            $date_from = "";
            $date_to = "";
        }
        $stores = Stores::all();

        return view('admin.reports.sales-report', [
            'orders_data' => @$orders_data,
            'orders_data_date' => @$orders_data_date,
            'filter' => $filter,
            'filter_type' => $filter_type,
            'date_to' => $date_to,
            'date_from' => $date_from,
            'stores' => $stores,
            'boutique' => @$boutique,
        ]);
    }
    
    function financial_report(Request $request){
        
        $best_product = array();
            $filter = (object)['status' => ''];
            $date_from = "";
            $date_to = "";
        if (!empty($_GET['filters'])) {
            $this->validate($request, [
                'date_from' => 'required',
                'date_to' => 'required',

            ]);

            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');
            $order_status = $request->input('order_status');
            $boutique = $request->input('boutique');
//            $boutique_order_status=" AND od.oss_status NOT IN ('unPaid','cancelled') AND od.boutique_status NOT IN ('Rejected','cancelled') ";
            $boutique_order_status=" AND od.boutique_status NOT IN ('Rejected','cancelled') ";
            if (!empty($order_status)) {
                if($order_status=="cancelled"){
                    $boutique_order_status="";
                }
                $status = " AND od.`order_status`='$order_status'";
            } else {
                $status = "";
            }
            if (!empty($boutique)) {
                $store_clause = " AND od.`store_id`='$boutique'";
            } else {
                $store_clause = "";
            }
            $date_clasuse = " AND (DATE(od.order_date)>=DATE('" . $date_from . "') and DATE(od.order_date)<=DATE('" . $date_to . "'))  ";
            
            $filter = (object)['status' => $request->input('order_status')];
        }else{
            $status = " AND od.`order_status`='Complete'";
        }
            $query = "select * from (SELECT
            `o`.`created_at` AS `order_date`,
            `o`.`order_number` AS `order_number`,
            `p`.`name` as `product_name`,
            `st`.`title` as `store_name`,
            CONCAT(u.first_name, ' ',u.last_name) AS customer_name,
            `ots`.`price` AS `ots_total_item_price`,
            `ots`.`shipped` AS `ots_boksha_delivered`,
            `ots`.`self` AS `ots_store_delivered`,
            `ots`.`shiping_amount` AS `ots_delivery_cost`,
            `ots`.`tax_rate` AS `ots_tax_rate`,
            `ots`.`tax` AS `ots_tax`,
            
            `o`.`order_details` AS `order_details`,
            `o`.`total_price` AS `total_price`,
            `o`.`status` AS `order_status`,
            `o`.`checkout_method` AS `checkout_method`,
            `o`.`id` AS order_increment_id,
            `o`.`buyer_credit_amount_used` AS buyer_credit_amount_used,
            `ots`.`id` AS order_item_id,
            `ots`.`store_id` AS store_id,
            `ots`.`subtotal` AS ots_subtotal,
            `ots`.`boutique_status` AS `boutique_status`,
            `ots`.`cart_data` AS cart_data,
            `ots`.`qty` AS order_item_qty,
            `ots`.`financial_notes` AS financial_notes,
            `ots`.`financial_comments` AS financial_comments,
            `ots`.`transfer_amount` AS transfer_amount,
            `ots`.`transfer_date` AS transfer_date,
            `oss`.`status` AS `oss_status`,
            `st`.`commission` AS commission,
            `st`.`title` AS store_title

            FROM((`order_to_store` `ots`
                            JOIN `orders` `o` ON ((`o`.`id` = `ots`.`order_id`))
                    )
                    LEFT JOIN `order_store_status` `oss` ON (((`ots`.`order_id` = `oss`.`order_id`) AND (`ots`.`store_id` = `oss`.`store_id`)))
                    LEFT JOIN `products` `p` ON ots.product_id = p.id
                    LEFT JOIN `stores` `st` ON ots.store_id = st.id
                    LEFT JOIN `users` `u` ON o.user_id = `u`.`id`
            ))  as od where 1=1";
            if($boutique_order_status != '' || $date_clasuse != '' || $status != '' || $store_clause != ''){
                $query .= " ".$boutique_order_status."". $date_clasuse . " " . $status . "".$store_clause;
            }
            $query .= ' ORDER BY `od`.`order_increment_id` DESC';
            $orders_list = DB::select($query);
            
//        $query = get_raw_query($query);
//        dd($query);
        $stores = Stores::all();

        return view('admin.reports.financial-report', [
            'orders_list' => $orders_list,
            'filter' => $filter,
            'date_to' => $date_to,
            'date_from' => $date_from,
            'stores' => $stores,
            'boutique' => $boutique,
        ]);
        
    }
    function update_financial_report(Request $request)
    {
        $order_item_id = $request->order_item_id;
        $financial_notes = $request->financial_notes;
        $financial_comments = $request->financial_comments;
        $transfer_amount = $request->transfer_amount;
        $transfer_date = $request->transfer_date;

        foreach($order_item_id as $k => $id){
          $values = array(
                'financial_notes'=> $financial_notes[$k],
                'financial_comments'=> $financial_comments[$k],
                'transfer_amount'=> $transfer_amount[$k],
                'transfer_date'=> $transfer_date[$k]
            );
          OrderToStore::where('id',$id)->update($values);

        }
        return redirect()->back();
    }


}
