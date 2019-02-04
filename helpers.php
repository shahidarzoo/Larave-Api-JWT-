<?php

/**
 * Created by PhpStorm.
 * User: Adnan
 * Date: 25/06/2017
 * Time: 04:32 AM
 */
use App\OrderToStore;
///use Mail;
use App\OrderStoreStatus;
use App\Orders;
use App\Models\EmailsFunc;
use App\ProductVariations;
use Illuminate\Support\Facades\DB;
use App\Credit;
use App\CreditLog;
use App\ProductToTags;

function helper_get_unique_slug($slug, $table_name, $column_name = "slug", $id = 0) {
    if (!empty($slug)) {

        $slug = str_replace(" ", "-", $slug);
        $slug = helper_safe_url($slug);
        $slug = substr($slug, 0, 200);
        $slug = strtolower($slug);
        $allSlugs = DB::table($table_name)->where([
                    ['id', "<>", $id],
                    [$column_name, 'like', "{$slug}%"]
                ])->get();
        if (!$allSlugs->contains($column_name, $slug)) {
            return $slug;
        }
        for ($i = 1; $i <= 10000; $i++) {
            $newSlug = $slug . '-' . $i;
            if (!$allSlugs->contains('slug', $newSlug)) {
                return $newSlug;
            }
        }
        return time();
    } else {
        $slug = time();
    }
    return strtolower($slug);
}

if (!function_exists('helper_safe_url')) {

    /**
     * Convert a string into a url safe address and remove specia charackers.
     *
     * @param string $unformatted
     * @return string
     */
    function helper_safe_url($unformatted, $lower = TRUE) {
        if ($lower) {
            $url = strtolower(trim($unformatted));
        }
        //replace accent characters, forien languages
        $search = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        $url = str_replace($search, $replace, $url);

        //replace common characters
        $search = array('&', '£', '$');
        $replace = array('and', 'pounds', 'dollars');
        $url = str_replace($search, $replace, $url);

        // remove - for spaces and union characters
        $find = array(' ', '&', '\r\n', '\n', '+', ',', '//');
        $url = str_replace($find, '-', $url);

        //delete and replace rest of special chars
        $find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
        $replace = array('', '-', '');
        $uri = preg_replace($find, $replace, $url);

        return $uri;
    }

}

if (!function_exists('mh_meta_get')) {

    /**
     *
     * @param type Int $entity_id
     * @param type varchar $entity_key
     * @param type varchar $entity_type default is page
     *
     */
    function mh_meta_get($entity_id, $entity_key, $entity_type = 'users') {

        $entity = new App\EntityMeta();
        $meta_data = $entity->get_entity_meta(array(
            'entity_id' => $entity_id,
            'entity_key' => $entity_key,
            'entity_type' => $entity_type
        ));

        return $meta_data;
    }

}
if (!function_exists('mh_meta_get_by_column')) {

    /**
     *
     * @param type Int $entity_id
     * @param type varchar $entity_key entity_id ,entity_key ,entity_type
     * @param type varchar $entity_type default is page
     *
     */
    function mh_meta_get_by_column($column_key_value, $entity_type = 'users') {

        $entity = new App\EntityMeta();
        $meta_data = $entity->meta_get_by_column($column_key_value);

        return $meta_data;
    }

}
if (!function_exists('mh_meta_get_by_column_rows')) {

    /**
     *
     * @param type Int $entity_id
     * @param type varchar $entity_key entity_id ,entity_key ,entity_type
     * @param type varchar $entity_type default is page
     *
     */
    function mh_meta_get_by_column_row($column_key_value, $entity_type = 'users', $take = 1) {

        $entity = new App\EntityMeta();
        $meta_data = $entity->meta_get_by_column_row($column_key_value, $take);

        return $meta_data;
    }

}
if (!function_exists('mh_get_column_by_id')) {

    /**
     *
     * @param type String $table_name
     * @param type Int $id
     * @param type Column Name $column_name
     *
     */
    function mh_get_column_by_id($table, $id, $column) {

        $value = DB::table($table)->where("id", $id)->take(1)->value($column);

        return !empty($value) ? $value : "";
    }

}
if (!function_exists('mh_get_column_by_where')) {

    /**
     *
     * @param type String $table_name
     * @param type Int $id
     * @param type Column Name $column_name
     *
     */
    function mh_get_column_by_where($table, $where, $column) {

        $value = DB::table($table)->where($where)->take(1)->value($column);

        return !empty($value) ? $value : "";
    }

}
if (!function_exists('helper_limit_string')) {

    function helper_limit_string($string_var, $end_limit = 50, $start = 0, $tag_strip = true) {
        if ($tag_strip)
            $string_var = strip_tags($string_var);
        if (strlen($string_var) > $end_limit) {

            $string_var = substr($string_var, $start, $end_limit) . '...';
        }

        return $string_var;
    }

}
if (!function_exists('flash_message')) {

    /**
     *
     * @param type String $message
     * @param type String $message_type  success,danger
     *
     */
    function flash_message($message, $message_type = 'success') {
        Session::flash('message', $message);
        Session::flash('alert-class', 'alert-' . $message_type);
    }

}
if (!function_exists('get_boutique_star_rate')) {

    /**
     *
     * @param type String $message
     * @param type String $message_type  success,danger
     *
     */
    function get_boutique_star_rate($store_id) {
        $star_rating = App\StarRating::where([
                    'store_id' => $store_id,
                ])->sum('stars');

        $people_rate = App\StarRating::where([
                    'store_id' => $store_id,
                ])->get();
        $average_rating = ($star_rating >= 1) ? $star_rating / count($people_rate) : 0;
        return array(
            'average_rating' => round($average_rating),
            'people_rate' => count($people_rate),
        );
    }

}

if (!function_exists('mailchimp')) {

    /**
     * @param $data =array('email'=>'myemail@gmail.com','status'=>'subscribed or unsubscribed or cleaned or pending','firstname'=>'subscriber first name','lastname'=>'last name')
     * @return mixed
     */
    function mailchimp($data = array()) {
        $apiKey = get_config('services.mailchimp.api_key');
        $listId = get_config('services.mailchimp.list_id');

        $memberId = md5(strtolower($data['email']));
        $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

        $json = json_encode([
            'email_address' => $data['email'],
            'status' => $data['status'], // "subscribed","unsubscribed","cleaned","pending"
            'merge_fields' => [
                'FNAME' => $data['firstname'],
                'LNAME' => $data['lastname']
            ]
        ]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode;
    }

}

use Newsletter as MailChimpNewsletter;

if (!function_exists('subscribe_mailchimp')) {

    function subscribe_mailchimp($email, $first_name = "", $last_name = "") {
        $data['email'] = $email;
        $data['status'] = 'subscribed';
        $data['firstname'] = $first_name;
        $data['lastname'] = $last_name;

        mailchimp($data);
        //MailChimpNewsletter::subscribe($email, ['FNAME'=>$first_name, 'LNAME'=>$last_name]);
        //return MailChimpNewsletter::lastActionSucceeded();
    }

}
if (!function_exists('unsubscribe_mailchimp')) {

    function unsubscribe_mailchimp($email) {
        MailChimpNewsletter::unsubscribe($email);
        return MailChimpNewsletter::lastActionSucceeded();
    }

}
if (!function_exists('get_config')) {

    function get_config($key) {
        return config($key);
    }

}
if (!function_exists('helper_delivery_charges')) {

    function helper_delivery_charges($country = "", $city = "", $default = false, $place = "", $store_count = 0, $item_count = 0, $callType = "", $store_id = "") {
        $delivery_static_amount = mh_meta_get_by_column_row(array('entity_key' => 'delivery_static_amount'), 'config');
        $delivery_country_default_amount = mh_meta_get_by_column_row(array('entity_key' => 'delivery_amount'), 'config');
        $delivery_store_item_count = mh_meta_get_by_column_row(array('entity_key' => 'delivery_store_item_count'), 'config');
        // boutique,item,1,2,3,4,5,6,7,8,9,10
        if ($delivery_store_item_count->entity_value == 'boutique')
            $store_count = $store_count;
        else if ($delivery_store_item_count->entity_value == 'item')
            $store_count = $item_count;
        else
            $store_count = $delivery_store_item_count->entity_value;

        if ($country == "" && $city == "" && $place = "" && $default == true) {
            //  return 0;
        } else if ($country == 229 && $place != "") {
            $UAE_place = App\Models\DeliveryCities::where('id', $place)->first();
            $cities = array('dubai', 'abu dhabi', 'ajman', 'sharjah', 'fujairah', 'ras al khaimah', 'umm al quwain');
            if (!empty($UAE_place) && count($UAE_place) >= 1) {
                $delivery_amount = $delivery_static_amount->entity_value + $UAE_place->price / $store_count;
                //    return number_format($delivery_amount, 2);
            } else {
                $delivery_amount = $delivery_country_default_amount->entity_value + $delivery_static_amount->entity_value / $store_count;
                //    return number_format($delivery_amount, 2);
            }
        } else if ($country == 229) {
            $delivery_amount = $delivery_country_default_amount->entity_value + $delivery_static_amount->entity_value / $store_count;
            //    return number_format($delivery_amount, 2);
        } else {
            $delivery_amount = $delivery_country_default_amount->entity_value + $delivery_static_amount->entity_value / $store_count;
            //    return number_format($delivery_amount, 2);
        }
        $cart_data = Cart::content();
        //    echo "<pre>";

        if (isset($callType) && $callType == 'discount_add') {
            return number_format($delivery_amount, 2);
        }

        $stores = array();
        $store_products = array();
        $coupon_detail = array();
        foreach ($cart_data as $row) :
            $stores[$row->options['store_id']] = $row->rowId;
            $store_products[$row->options['store_id']][] = $row;
            $store_discount[$row->options['store_id']] = $row->options['discount']['store_discount'];
            if (!empty($row->options['discount']['coupon_detail']) && count($row->options['discount']['coupon_detail']) >= 1)
                $coupon_detail = $row->options['discount']['coupon_detail'];
        endforeach;

        if ($coupon_detail) {
            foreach ($coupon_detail as $coupon_details) {
                $free_shipping = $coupon_details['free_shipping'];
                $percentage = $coupon_details['percentage'];
                $coupon_type = $coupon_details['coupons_type'];
                $coupon_store_id = $coupon_details['store_id'];
                $d_amount = 0;
                if ($free_shipping == 1) {
                    if (strtolower($coupon_type) == 'percentage') {
                        $value = ($percentage / 100) * $delivery_amount;
                        $d_amount = $delivery_amount - $value;
                    } else {
                        if ($delivery_amount >= $percentage) {
                            $d_amount = $delivery_amount - $percentage;
                        }
                    }
                    if (!empty($coupon_store_id)) {
                        if ($coupon_store_id == $store_id) {
                            $delivery_amount = $d_amount;
                        }
                    } else {
                        $delivery_amount = $d_amount;
                    }
                }
            }
        }
        return number_format($delivery_amount, 2);
    }

}

if (!function_exists('helper_max_product_price')) {

    function helper_max_product_price() {
        $product = new App\Products();
        $price = $product::max_product_price();
        return $price;
    }

}
if (!function_exists('helper_min_product_price')) {

    function helper_min_product_price() {
        $product = new App\Products();
        $price = $product::min_product_price();
        return $price;
    }

}
if (!function_exists('helper_replace_string')) {

    /**
     * General Error log
     * @params type  $token_value =  array("@ApplicantName" => "replace value goes here ", "@ApartmentAddress" => "Replace value goes here");
     */
    function helper_replace_string($string_text, $token_value) {

        return $string_text = strtr($string_text, $token_value);
    }

}

if (!function_exists('helper_sms_send')) {

    /**
     * General Error log
     * @params type  $token_value =  array("to" => "replace value goes here ", "@ApartmentAddress" => "Replace value goes here");
     */
    function helper_sms_send($to = "", $message, $name = "boksha", $from = 'Boksha LLC') {
        if (!config('services.boksha.nexmo_sms') || env('APP_ENV') != "production") {
            return false;
        }

        $phone_number = str_replace("-", "", $to);
        $phone_number = str_replace(" ", "", $phone_number);
        $data = array(
            'api_key' => 'ff9cc087',
            'api_secret' => '00d0ea80d1431ea9',
            'to' => $phone_number,
            'from' => $from,
            'text' => $message
        );

        $fields = http_build_query($data);

        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://rest.nexmo.com/sms/json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            //  echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

}

function Decode_Serialize($QUERY_STRING) {
    /*
     * Decode form.serelize() jQuery Post String
     * Return like $_POST['Form_Input_Name or ID']
     */
    $a = explode('&', $QUERY_STRING);
    $i = 0;
    $store = array();
    while ($i < count($a)) {
        $b = explode('=', $a[$i]);
        $array_name = htmlspecialchars(urldecode($b[0]));
        $array_value = htmlspecialchars(urldecode($b[1]));
        $store[$array_name] = $array_value;
        $i++;
    }
    /*
     * Convert Array as an Object
     * return(object)$store;
     * Use ........Object->Form_Input_Name or ID
     * or
     * Use as An Array .........$var["Form_Input_Name or ID"]
     */
    return $store;
}

//site related functions
function filter_coupon_codes($coupon_code = "", $store_id = null, $return_html = false) {
    $coupon_array = array();
//          print_r($coupon_code->coupon);
//        die();
    //print_r($coupon->coupon);
    if (!empty($coupon_code)) {
        foreach ($coupon_code->coupon as $coupon_key => $coupon_val) {
            if ($coupon_val->store_id == $store_id || $coupon_val->store_id == null) {
                $coupon_array[$coupon_val->coupon_code] = $coupon_val;
            } else if ($store_id == null) {
                $coupon_array[$coupon_val->coupon_code] = $coupon_val;
            }
        }
    } else {
        return "";
    }
    $html = "";
    if ($return_html) {
        if (!empty($coupon_array) && count($coupon_array) >= 1) {
            $html = '<div><b>Coupon Detail</b></div>';
            foreach ($coupon_array as $coupon_item) {
                if ($store_id == null) {
                    $html .= '<div>Coupon Code: ' . $coupon_item->coupon_code . ' | Discount:' . $coupon_item->percentage;
                } else {
                    $html .= '<div>Coupon Title: ' . $coupon_item->coupon_title . ' | Discount:' . $coupon_item->percentage;
                }

                if ($coupon_item->coupons_type == 'value') {
                    $html .= ' AED</div>';
                } else {
                    $html .= '%</div>';
                }
            }
        }
        return $html;
    }
    return $coupon_array;
}

if (!function_exists('helper_prepare_url')) {

    function helper_prepare_url($url, $internal = true, $validate = false) {
        $path = "";
        if (!empty($url) && $internal) {
            if ($validate == true && filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
                return "";
            }
            $components = parse_url($url);
            $path = $components['path'];
            if ($components['query']) {
                $path = $path . "?" . $components['query'];
            }
            return url($path);
        }
        return $url;
    }

}

function set_complete_order_status($order_id, $store_id, $order_number) {

    $no_of_store = 0;
    $no_of_status_accepted = 0;
    $no_of_status_rejected = 0;
    $no_of_status_cancelled = 0;
    $no_of_status_waiting = 0;
    $no_of_status_partially_accepted = 0;
    $no_of_status_readytoship = 0;

    $stores_count = DB::table('order_store_status')
                    ->select(
                            'store_id'
                    )
                    ->where('order_id', '=', $order_id)->count();

    $OrderToStoreStatus = OrderStoreStatus::where([
                ["order_number", '=', $order_number],
                ["order_id", '=', $order_id]
            ])->get();

    $no_of_store = OrderToStore::where([
                ["order_id", '=', $order_id]
            ])->select("DISTINCT(store_id)")->count();

    if ($no_of_store != count($OrderToStoreStatus)) {
        $no_of_p = 0;
        if (!empty($OrderToStoreStatus)) {
            foreach ($OrderToStoreStatus as $ordstStore) {
                if ($ordstStore->status == "Accepted" || $ordstStore->status == "partially_accepted") {
                    $no_of_p++;
                }
            }
        }

        if ($no_of_p > 0) {
            Orders::updateOrderStatus('partially_accepted', $order_id);
        } else {
            //Orders::updateOrderStatus('in_progress',$order_id); // will discuss with client
        }
    }

    if ($no_of_store == count($OrderToStoreStatus)) {
        if (!empty($OrderToStoreStatus)) {
            foreach ($OrderToStoreStatus as $orderstatusStore) {

                if ($orderstatusStore->status == "Accepted") {
                    $no_of_status_accepted++;
                } else if ($orderstatusStore->status == "Rejected") {
                    $no_of_status_rejected++;
                } else if ($orderstatusStore->status == "waiting") {
                    $no_of_status_waiting++;
                } else if ($orderstatusStore->status == "cancelled") {
                    $no_of_status_cancelled++;
                } else if ($orderstatusStore->status == "partially_accepted") {
                    $no_of_status_partially_accepted++;
                }

                if ($orderstatusStore->shipment_status == "Ready" && $orderstatusStore->status != "Rejected") {
                    $no_of_status_readytoship++;
                }
            }
        }

        if ($no_of_status_accepted == $no_of_store) {

            Orders::updateOrderStatus('Accepted', $order_id);
        }

        if ($no_of_status_rejected == $no_of_store) {
            Orders::updateOrderStatus('Rejected', $order_id);
        }

        if ($no_of_status_cancelled == $no_of_store) {
            Orders::updateOrderStatus('Cancelled', $order_id);
        }
        if ($no_of_status_partially_accepted == $no_of_store) {
            Orders::updateOrderStatus('partially_accepted', $order_id);
        }

        if ($no_of_status_partially_accepted > 0) {
            Orders::updateOrderStatus('partially_accepted', $order_id);
        }

        if ($no_of_status_rejected > 0 && $no_of_status_accepted == 0 && $no_of_status_cancelled == 0 && $no_of_status_waiting == 0 && $no_of_status_partially_accepted == 0) {
            Orders::updateOrderStatus('Rejected', $order_id);
        }

        if ($no_of_status_cancelled > 0 && $no_of_status_accepted == 0 && $no_of_status_waiting == 0 && $no_of_status_partially_accepted == 0) {
            Orders::updateOrderStatus('Cancelled', $order_id);
        }

        if (($no_of_status_accepted > 0 && $no_of_status_rejected > 0) || ($no_of_status_accepted > 0 && $no_of_status_cancelled > 0)) {

            Orders::updateOrderStatus('partially_accepted', $order_id);
        }
    }
}

function set_order_final_status($order_id, $store_id, $order_number) {

    $orderStatus = "";
    $no_of_orderitems = 0;
    $no_of_accepted = 0;
    $no_of_rejected = 0;
    $no_of_waiting = 0;

    $no_of_readytoship = 0;

    $orderStoreProducts = OrderToStore::where([
                ["order_id", '=', $order_id],
                ['store_id', '=', $store_id]
            ])->get();

    if (!empty($orderStoreProducts)) {
        foreach ($orderStoreProducts as $orderProducts) {
            $no_of_orderitems++;
            if ($orderProducts->boutique_status == "Accepted") {
                $no_of_accepted++;
            } else if ($orderProducts->boutique_status == "Rejected") {
                $no_of_rejected++;
            } else if ($orderProducts->boutique_status == "waiting") {
                $no_of_waiting++;
            } else if ($orderProducts->boutique_status == "cancelled") {
                $no_of_cancelled++;
            }

            if ($orderProducts->boutique_shipment_status == "Ready" && $orderProducts->boutique_status != "Rejected") {
                $no_of_readytoship++;
            }
        }
    }

    $no_of_deductRejected = $no_of_orderitems - $no_of_rejected;
    $no_of_deductCancelled = $no_of_orderitems - $no_of_cancelled;
    $no_of_deduct_Cancelled_Rejected = $no_of_orderitems - ($no_of_cancelled + $no_of_rejected);



    if ($no_of_accepted == $no_of_orderitems) {

        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) == 0) {
            $OrderToStoreStatus = new OrderStoreStatus();
        }
        $OrderToStoreStatus->order_number = $order_number;
        $OrderToStoreStatus->store_id = $store_id;
        $OrderToStoreStatus->order_id = $order_id;
        $OrderToStoreStatus->status = "Accepted";
        $OrderToStoreStatus->save();
        if ($no_of_accepted > 1) {
            $emailObj = new EmailsFunc();
            $emailObj->boutique_approve_order($store_id, $order_id);
        }
        set_complete_order_status($order_id, $store_id, $order_number);
    } else if ($no_of_cancelled == $no_of_orderitems) {

        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) == 0) {
            $OrderToStoreStatus = new OrderStoreStatus();
        }
        $OrderToStoreStatus->order_number = $order_number;
        $OrderToStoreStatus->store_id = $store_id;
        $OrderToStoreStatus->order_id = $order_id;
        $OrderToStoreStatus->status = "cancelled";
        $OrderToStoreStatus->save();
        if ($no_of_rejected > 1) {
            $emailObj = new EmailsFunc();
            $emailObj->boutique_cancel_order($store_id, $order_id);
        }
        set_complete_order_status($order_id, $store_id, $order_number);
    } else if ($no_of_rejected == $no_of_orderitems) {

        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) == 0) {
            $OrderToStoreStatus = new OrderStoreStatus();
        }
        $OrderToStoreStatus->order_number = $order_number;
        $OrderToStoreStatus->store_id = $store_id;
        $OrderToStoreStatus->order_id = $order_id;
        $OrderToStoreStatus->status = "Rejected";
        $OrderToStoreStatus->save();
        if ($no_of_rejected > 1) {
            $emailObj = new EmailsFunc();
            $emailObj->boutique_reject_order($store_id, $order_id);
        }
        set_complete_order_status($order_id, $store_id, $order_number);
    } else if ($no_of_accepted > 0) {
        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) == 0) {
            $OrderToStoreStatus = new OrderStoreStatus();
        }
        $OrderToStoreStatus->order_number = $order_number;
        $OrderToStoreStatus->store_id = $store_id;
        $OrderToStoreStatus->order_id = $order_id;
        $OrderToStoreStatus->status = "partially_accepted";
        $OrderToStoreStatus->save();
        // if($no_of_accepted > 1){
        //   $emailObj = new EmailsFunc();
        //   $emailObj->boutique_approve_order($store_id,$order_id);
        // }
        set_complete_order_status($order_id, $store_id, $order_number);
    } else if ($no_of_rejected > 0 && $no_of_accepted == 0 && $no_of_waiting == 0 && $no_of_cancelled == 0) {
        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) == 0) {
            $OrderToStoreStatus = new OrderStoreStatus();
        }
        $OrderToStoreStatus->order_number = $order_number;
        $OrderToStoreStatus->store_id = $store_id;
        $OrderToStoreStatus->order_id = $order_id;
        $OrderToStoreStatus->status = "Rejected";
        $OrderToStoreStatus->save();
        if ($no_of_rejected > 1) {
            $emailObj = new EmailsFunc();
            $emailObj->boutique_reject_order($store_id, $order_id);
        }
        set_complete_order_status($order_id, $store_id, $order_number);
    } else if ($no_of_cancelled > 0 && $no_of_accepted == 0 && $no_of_waiting == 0) {
        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) == 0) {
            $OrderToStoreStatus = new OrderStoreStatus();
        }
        $OrderToStoreStatus->order_number = $order_number;
        $OrderToStoreStatus->store_id = $store_id;
        $OrderToStoreStatus->order_id = $order_id;
        $OrderToStoreStatus->status = "cancelled";
        $OrderToStoreStatus->save();
        if ($no_of_rejected > 1) {
            $emailObj = new EmailsFunc();
            $emailObj->boutique_cancel_order($store_id, $order_id);
        }
        set_complete_order_status($order_id, $store_id, $order_number);
    }
    // }else if ($no_of_accepted == $no_of_deductRejected){
    //       $OrderToStoreStatus = OrderStoreStatus::where([
    //           ["order_number",'=',$order_number],
    //           ['store_id','=',$store_id]
    //       ])->first();
    //
     //       if (count($OrderToStoreStatus)==0) {
    //           $OrderToStoreStatus = new OrderStoreStatus();
    //       }
    //       $OrderToStoreStatus->order_number=$order_number;
    //       $OrderToStoreStatus->store_id=$store_id;
    //       $OrderToStoreStatus->order_id=$order_id;
    //       $OrderToStoreStatus->status="partially_accepted";
    //       $OrderToStoreStatus->save();
    //       if($no_of_accepted > 1){
    //         $emailObj = new EmailsFunc();
    //         $emailObj->boutique_approve_order($store_id,$order_id);
    //       }
    //  }
    // }else if ($no_of_accepted == $no_of_deductCancelled){
    //       $OrderToStoreStatus = OrderStoreStatus::where([
    //           ["order_number",'=',$order_number],
    //           ['store_id','=',$store_id]
    //       ])->first();
    //
     //       if (count($OrderToStoreStatus)==0) {
    //           $OrderToStoreStatus = new OrderStoreStatus();
    //       }
    //       $OrderToStoreStatus->order_number=$order_number;
    //       $OrderToStoreStatus->store_id=$store_id;
    //       $OrderToStoreStatus->order_id=$order_id;
    //       $OrderToStoreStatus->status="partially_accepted";
    //       $OrderToStoreStatus->save();
    //       if($no_of_accepted > 1){
    //         $emailObj = new EmailsFunc();
    //         $emailObj->boutique_approve_order($store_id,$order_id);
    //       }
    // }else if ($no_of_accepted == $no_of_deduct_Cancelled_Rejected){
    //       $OrderToStoreStatus = OrderStoreStatus::where([
    //           ["order_number",'=',$order_number],
    //           ['store_id','=',$store_id]
    //       ])->first();
    //
     //       if (count($OrderToStoreStatus)==0) {
    //           $OrderToStoreStatus = new OrderStoreStatus();
    //       }
    //       $OrderToStoreStatus->order_number=$order_number;
    //       $OrderToStoreStatus->store_id=$store_id;
    //       $OrderToStoreStatus->order_id=$order_id;
    //       $OrderToStoreStatus->status="partially_accepted";
    //       $OrderToStoreStatus->save();
    //       if($no_of_accepted > 1){
    //         $emailObj = new EmailsFunc();
    //         $emailObj->boutique_approve_order($store_id,$order_id);
    //       }
    // }

    if ($no_of_readytoship == $no_of_orderitems) {
        //Order is now completely Ready to ship
        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) >= 1) {
            $OrderToStoreStatus->shipment_status = "Ready";
            $OrderToStoreStatus->save();
            if ($no_of_readytoship > 1) {
                $emailObj = new EmailsFunc();
                $emailObj->boutique_order_ready_for_ship($OrderToStoreStatus->store_id, $OrderToStoreStatus->order_id);
            }
        }
    }

    if ($no_of_readytoship == $no_of_deductRejected) {
        //Order is now completely Ready to ship
        $OrderToStoreStatus = OrderStoreStatus::where([
                    ["order_number", '=', $order_number],
                    ['store_id', '=', $store_id]
                ])->first();

        if (count($OrderToStoreStatus) >= 1) {
            $OrderToStoreStatus->shipment_status = "Ready";
            $OrderToStoreStatus->save();
            if ($no_of_readytoship > 1) {
                $emailObj = new EmailsFunc();
                $emailObj->boutique_order_ready_for_ship($OrderToStoreStatus->store_id, $OrderToStoreStatus->order_id);
            }
        }
    }
}

function get_raw_query($query) {
    $sql = $query->toSql();
    foreach ($query->getBindings() as $binding) {
        $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
        $sql = preg_replace('/\?/', $value, $sql, 1);
    }
    return $sql;
}

if (!function_exists('log_emails')) {

    function log_emails($subject, $email_data) {
        $mail_log = new \App\MailLog();
        $mail_log->send_to = $email_data->to[0]['address'];
        $mail_log->subject = $subject;
        $mail_log->content = $email_data->content;
        $mail_log->save();
    }

}

if (!function_exists('get_buyer_credit')) {

    function get_buyer_credit() {
        $credit = '';
        if (Auth::check()) {
            $credit = Credit::where('user_id', Auth::user()->id)->get()[0];
        }
        return $credit;
    }

}

if (!function_exists('update_buyer_credit')) {

    function update_buyer_credit($order_id) {
        $order = Orders::findOrFail($order_id);

        if (count($order) > 0) {

            $buyer_credit = isset($order->buyer_credit) ? json_decode($order->buyer_credit) : '';
            $amount_used = $order->buyer_credit_amount_used;

            $credit = Credit::where('id', $buyer_credit->id)->get()[0];
            if ($credit) {
                $new_amount = $credit->amount - $amount_used;
                if ($new_amount >= 0) {
                    $credit->amount = $new_amount;
                    $credit->save();
                }

                if ($amount_used > 0) {
                    $description = $amount_used . ' used in order';

                    $credit_log = new CreditLog();
                    $credit_log->buyer_credit_id = $credit->id;
                    $credit_log->log_type = 1;
                    $credit_log->amount = $amount_used;
                    $credit_log->order_number = $order->order_number;
                    $credit_log->description = $description;
                    $credit_log->save();
                }
            }
        }
    }

}
if (!function_exists('calculate_product_discount_percentage')) {

    function calculate_product_discount_percentage($product_id = null, $product = null) {
        if (!$product) {
            $product = Products::findOrFail($id);
        }

        $price = $product->price;
        $sale_price = $product->sale_price;

        $discount_amount = $price - $sale_price;

        $percentage = ( $discount_amount / $price) * 100;
        return round($percentage);
    }
    function get_url_for_addtocart(){

       $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
     
        $save_url= Session::put('current_url', $url);
        
        return $save_url;
    }
    function add_edit_tags($tags_array,$price,$sale_price,$product_id,$is_edit=null){
        

            
        if($sale_price<=($price/2))
        {
             $value1='Clearence';
             $value2='Sales';
        }else{
             $value1='Sales';
             $value2='Clearence';
        }

        if(empty($sale_price) || ($sale_price<1))
        {
            //If Sale Price is Empty
                $value1='Sales';
                $value2='Clearence';
                $tag_delete_data1 = DB::table('tags')                
                            ->where('tags.tag_type','=',$value1)                
                            ->first();
                $tag_delete_data2 = DB::table('tags')                
                            ->where('tags.tag_type','=',$value2)                
                            ->first();
                 if (($key = array_search($tag_delete_data1->id, $tags_array)) !== false) {
                    unset($tags_array[$key]);
                }
                 if (($key = array_search($tag_delete_data2->id, $tags_array)) !== false) {
                    unset($tags_array[$key]);
                }



        }else{

            $tag_data = DB::table('tags')                
                        ->where('tags.tag_type','=',$value1)                
                        ->first();
            $tag_delete_data = DB::table('tags')                
                        ->where('tags.tag_type','=',$value2)                
                        ->first();

       
            if (($key = array_search($tag_delete_data->id, $tags_array)) !== false) {
                unset($tags_array[$key]);
            }
            if (($key = array_search($tag_data->id, $tags_array)) !== true) {
                $tags_array[]=$tag_data->id;
               
            }
        }
          
 
        if($is_edit=='edit'){
                
                ProductToTags::where(['fk_product_id' => $product_id])->delete();
            }
        if (!empty($tags_array)) {
         
            foreach ($tags_array as $tag) {
                $product_to_tags = new ProductToTags();
                $product_to_tags->fk_product_id = $product_id;
                $product_to_tags->fk_tags_id = $tag;
                $product_to_tags->save();
            }
        }
        
        return true;
    }
   

}

function callMailchimpAPI($method, $url, $data=null)
{
    $curl = curl_init();
    /*$info = curl_getinfo($curl);
    echo "<pre>";
    print_r( $info );*/
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
        case "PATCH":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
        if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);                              
        break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE"); 
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        default:
        if($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
    
    $headers = array(  
        "--user: 93353d176c81e3391109a76455f50045-us7",
        "Authorization: Basic b3dhaXNfdGFhcnVmZjo5MzM1M2QxNzZjODFlMzM5MTEwOWE3NjQ1NWY1MDA0NS11czc=",
        "Content-Type: application/json",
        "Postman-Token: 163f7134-68ca-45bb-9420-ebf2bef7f447",
        "cache-control: no-cache"
     );
    curl_setopt_array($curl, 
    [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $headers, 
        CURLOPT_HEADER => false,
        CURLOPT_URL => $url.'?apikey=93353d176c81e3391109a76455f50045-us7'
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function createMailChimpStore($id)
{
    $curl = curl_init();
    $store_name = "store_";
    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://us7.api.mailchimp.com/3.0//ecommerce/stores/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => '{
      "id" : "'.$store_name.$id.'",
      "list_id" : "317fbacff8",
      "name" : "'.$store_name.$id.'",
      "domain" : "http://boksha.eshmar.com/storew/inspiration",
      "email_address" : "inspiratiossn@boksha.com",
      "currency_code" : "AED"
    }',
     CURLOPT_HTTPHEADER => array(
        "--user: 93353d176c81e3391109a76455f50045-us7",
        "Authorization: Basic b3dhaXNfdGFhcnVmZjo5MzM1M2QxNzZjODFlMzM5MTEwOWE3NjQ1NWY1MDA0NS11czc=",
        "Content-Type: application/json",
        "Postman-Token: 8621048d-e026-4135-b456-400b3f3ec523",
        "cache-control: no-cache"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
}

/* Store Product */
function curlMailchimpStore($data_array, $product)
{
    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores";
    $data = callMailchimpAPI('GET', $url, false);
    $result = json_decode($data);

    $store_ids = array();
    foreach ($result->stores as $store) 
    {
         $store_ids[] =  $store->id;            
    }
    /* Distinct Srore */
    if (in_array("store_".$product->title, $store_ids))
    {
        $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_$product->title/products";
        $data = callMailchimpAPI('POST', $url, $data_array);
    }
    else
    {
        createMailChimpStore($product->title);
        $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_$product->title/products";
        $data = callMailchimpAPI('POST', $url, $data_array);
    }
    /* Common store */
    if (in_array("store_all_products", $store_ids))
    {
        $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_all_products/products";
        $data = callMailchimpAPI('POST', $url, $data_array);
    }
    else
    {
        $id = 'all_products';
        createMailChimpStore($id);
        $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_all_products/products";
        $data = callMailchimpAPI('POST', $url, $data_array);
    }
}

/* Update Product */
function curlMailchimpUpdate($data_array, $product)
{

    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_$product->title/products/$product->id";
    $data = callMailchimpAPI('PATCH', $url, $data_array);

    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_all_products/products/$product->id";
    $data = callMailchimpAPI('PATCH', $url, $data_array);
}
    /*Delete Product */
function curlMailchimpDelete($id)
{
    $delete_id = curl_product_query($id);
    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_$delete_id->title/products/$id";
    $data = callMailchimpAPI('DELETE', $url, false);

    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_all_products/products/$id";
    $data = callMailchimpAPI('DELETE', $url, false);
}

function curl_data_array($product)
{
    $product_url = "http://boksha.com/product/$product->slug";
    return  $data_array = '
            {
                "id": "'.$product->id.'", 
                "title": "'.$product->name.'", 
                "handle": "'.$product->slug.'", 
                "url": "'.$product_url.'", 
                "description": "'.$product->description.'", 
                "type": "'.$product->slug.'",
                "vendor": "'.$product->title.'", 
                "image_url": "'.$product->image_path.'", 
                "variants": [
                    { 
                        "id": "'.$product->id.'",
                        "title": "'.$product->name.'",
                        "url": "'.$product_url.'",
                        "sku": "",
                        "price": "'.$product->price.'",
                        "inventory_quantity": 0,
                        "image_url": "'.$product->image_path.'",
                        "backorders": "0",
                        "visibility": "visible",
                        "created_at": "'.$product->created_at.'",
                        "updated_at": "'.$product->updated_at.'"
                    }
                ]
            }';
}

function curl_product_query($id)
{
    return $product = DB::table('products')
        ->join('stores', 'stores.id', '=', 'products.store_id')
        ->leftJoin('product_images', 'products.id', 'product_images.product_id')
        ->select('products.*', 'stores.title','product_images.image_path')
        ->whereNull('products.deleted_at')
        ->groupBy("products.id")
        ->where('products.id',  $id)
        ->first();
}

function curl_product_image_query($id)
{
     return $product = DB::table('products')
        ->join('stores', 'stores.id', '=', 'products.store_id')
        ->leftJoin('product_images', 'products.id', 'product_images.product_id')
        ->select('Products.id','stores.title','product_images.image_path','product_images.product_id')
        ->whereNull('products.deleted_at')
        ->groupBy("products.id")
        ->where('products.id',  $id)
        ->where('product_images.is_featured',  1)
        ->first();
}

function curl_Image_array($product)
{
        return $image_update = '
        {
            "id": "'.$product->id.'", 
            "url": "'.$product->image_path.'"
        }';
}

function CurlImageUpdate($data_array, $product)
{

    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_$product->title/products/$product->id/images";
    //dd($data_array);
    $data = callMailchimpAPI('POST', $url, $data_array);

    $url = "https://us7.api.mailchimp.com/3.0/ecommerce/stores/store_all_products/products/$product->id/images";
    $data = callMailchimpAPI('POST', $url, $data_array);
}

