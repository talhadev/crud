<?php

namespace App\Http\Controllers\Api\vendor;
use App\helpers\courier_tracker;
use App\helpers\curl_request;
use App\helpers\helpers;
use App\Models\Orderfailure;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Config;
use PDF;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Api\Models\Orderproduct;
use \Cviebrock\EloquentSluggable\Services\SlugService;
use App\Models\Stores;
use Illuminate\Support\Str;
use Input;

use App\Http\Controllers\Api\vendor\ResponseController;
use App\Models\Shipping;
use phpDocumentor\Reflection\Types\Null_;
use Response;
use DB;


class test extends Controller
{

    /*  public function __construct(Request $request)
        {
            $payload_json = json_decode($request->payload, true);
            $params = ['payload' => '{"action":"checkAuth","dataPacket":{"token":""},  "time_stamp": "2017-06-16 04:33:58"}'];
            $headers = ['Authorization' => $request->header('Authorization'), 'Accept' => $request->header('Accept')];
            $url = Config::get('urls.navigation_urls.brain_server', true);
            $headers = helpers::get_header_array($headers);
           $response      = curlRequest($url, $headers, $params);
           $authorization = json_decode($response, true);
           if ($authorization['action'] == 'Authorization') {
               $response  = generateResponse($payload_json['action'], 500, false, 'Invalid token');
               dd($response);
          }

        }*/

    /*    public function actions(Request $request)
        {

            $payload = $request->payload;
            $payload_json = json_decode($payload, true);
            $action = $payload_json['action'];

            if (method_exists($this, $action)) {
                $function_name = Str::lower($action);
                $call = $this->$function_name($request);
                return $call;
            } else {
                $response = helpers::generateResponse($action, 500, false, 'Invalid action');
                return $response;
            }
        }*/

    public function successOrders($payload)
    {
        dd($payload);
        $response_data = array();
        $payload_json = json_decode($request->payload, true);
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id']),
            array(
                'store_id' => 'required|integer',)
        );

        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = helpers::generateResponse($payload_json ['action'], 500, false, $error_messages);
            return $response;
        } else {
            $orderList = Shipping::where('status', '=', '1')->where('store_id', $payload_json['dataPacket']['store_id'])->get();

            if ($orderList == true) {
                if (count($orderList) > 0) {
                    foreach ($orderList as $data) {
                        $orderinfo = json_decode($data->orderinfo, true);
                        $order_info = $orderinfo['datapacket'];
                        foreach ($order_info as $info) {
                            $response = [
                                'store_id' => $data->store_id,
                                'store_name' => $order_info['orderinfo']['store_name'],
                                'order_id' => $order_info['orderinfo']['order_id'],
                                'courier_company' => $data->courier_name,
                                'order_tracking_id' => $data->order_tracking_id,
                                'status' => 'Success',
                                'created_at' => $data->created_at
                            ];
                            array_push($response_data, $response);
                            break;
                        }
                    }
                    $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);
                    return $response;
                }
                $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                return $response;
            }
        }
    }

public function test(){

        $this->load->model('checkout/order');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            // Customer
            if (!isset($this->session->data['customer'])) {
                $json['error'] = $this->language->get('error_customer');
            }

            // Payment Address
            if (!isset($this->session->data['payment_address'])) {
                $json['error'] = $this->language->get('error_payment_address');
            }

            // Payment Method
            if (!$json && !empty($this->request->post['payment_method'])) {
                if (empty($this->session->data['payment_methods'])) {
                    $json['error'] = $this->language->get('error_no_payment');
                } elseif (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
                    $json['error'] = $this->language->get('error_payment_method');
                }

                if (!$json) {
                    $this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
                }
            }

            if (!isset($this->session->data['payment_method'])) {
                $json['error'] = $this->language->get('error_payment_method');
            }

            // Shipping
            if ($this->cart->hasShipping()) {
                // Shipping Address
                if (!isset($this->session->data['shipping_address'])) {
                    $json['error'] = $this->language->get('error_shipping_address');
                }

                // Shipping Method
                if (!$json && !empty($this->request->post['shipping_method'])) {
                    if (empty($this->session->data['shipping_methods'])) {
                        $json['error'] = $this->language->get('error_no_shipping');
                    } else {
                        $shipping = explode('.', $this->request->post['shipping_method']);

                        if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                            $json['error'] = $this->language->get('error_shipping_method');
                        }
                    }

                    if (!$json) {
                        $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                    }
                }

                if (!isset($this->session->data['shipping_method'])) {
                    $json['error'] = $this->language->get('error_shipping_method');
                }
            } else {
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
            }

            // Cart
            if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
                $json['error'] = $this->language->get('error_stock');
            }

            // Validate minimum quantity requirements.
            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    $json['error'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);

                    break;
                }
            }

            if (!$json) {
                $order_data = array();

                // Store Details
                $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
                $order_data['store_id'] = $this->config->get('config_store_id');
                $order_data['store_name'] = $this->config->get('config_name');
                $order_data['store_url'] = $this->config->get('config_url');

                // Customer Details
                $order_data['customer_id'] = $this->session->data['customer']['customer_id'];
                $order_data['customer_group_id'] = $this->session->data['customer']['customer_group_id'];
                $order_data['firstname'] = $this->session->data['customer']['firstname'];
                $order_data['lastname'] = $this->session->data['customer']['lastname'];
                $order_data['email'] = $this->session->data['customer']['email'];
                $order_data['telephone'] = $this->session->data['customer']['telephone'];
                $order_data['fax'] = $this->session->data['customer']['fax'];
                $order_data['custom_field'] = $this->session->data['customer']['custom_field'];

                // Payment Details
                $order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
                $order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
                $order_data['payment_company'] = $this->session->data['payment_address']['company'];
                $order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
                $order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
                $order_data['payment_city'] = $this->session->data['payment_address']['city'];
                $order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
                $order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
                $order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
                $order_data['payment_country'] = $this->session->data['payment_address']['country'];
                $order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
                $order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
                $order_data['payment_custom_field'] = $this->session->data['payment_address']['custom_field'];

                if (isset($this->session->data['payment_method']['title'])) {
                    $order_data['payment_method'] = $this->session->data['payment_method']['title'];
                } else {
                    $order_data['payment_method'] = '';
                }

                if (isset($this->session->data['payment_method']['code'])) {
                    $order_data['payment_code'] = $this->session->data['payment_method']['code'];
                } else {
                    $order_data['payment_code'] = '';
                }

                // Shipping Details
                if ($this->cart->hasShipping()) {
                    $order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
                    $order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
                    $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
                    $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
                    $order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
                    $order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
                    $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
                    $order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
                    $order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
                    $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
                    $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
                    $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
                    $order_data['shipping_custom_field'] = $this->session->data['shipping_address']['custom_field'];

                    if (isset($this->session->data['shipping_method']['title'])) {
                        $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                    } else {
                        $order_data['shipping_method'] = '';
                    }

                    if (isset($this->session->data['shipping_method']['code'])) {
                        $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                    } else {
                        $order_data['shipping_code'] = '';
                    }
                } else {
                    $order_data['shipping_firstname'] = '';
                    $order_data['shipping_lastname'] = '';
                    $order_data['shipping_company'] = '';
                    $order_data['shipping_address_1'] = '';
                    $order_data['shipping_address_2'] = '';
                    $order_data['shipping_city'] = '';
                    $order_data['shipping_postcode'] = '';
                    $order_data['shipping_zone'] = '';
                    $order_data['shipping_zone_id'] = '';
                    $order_data['shipping_country'] = '';
                    $order_data['shipping_country_id'] = '';
                    $order_data['shipping_address_format'] = '';
                    $order_data['shipping_custom_field'] = array();
                    $order_data['shipping_method'] = '';
                    $order_data['shipping_code'] = '';
                }

                // Products
                $order_data['products'] = array();

                foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        $option_data[] = array(
                            'product_option_id'       => $option['product_option_id'],
                            'product_option_value_id' => $option['product_option_value_id'],
                            'option_id'               => $option['option_id'],
                            'option_value_id'         => $option['option_value_id'],
                            'name'                    => $option['name'],
                            'value'                   => $option['value'],
                            'type'                    => $option['type']
                        );
                    }

                    $order_data['products'][] = array(
                        'product_id' => $product['product_id'],
                        'name'       => $product['name'],
                        'model'      => $product['model'],
                        'option'     => $option_data,
                        'download'   => $product['download'],
                        'quantity'   => $product['quantity'],
                        'subtract'   => $product['subtract'],
                        'price'      => $product['price'],
                        'total'      => $product['total'],
                        'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
                        'reward'     => $product['reward']
                    );
                }

                // Gift Voucher
                $order_data['vouchers'] = array();

                if (!empty($this->session->data['vouchers'])) {
                    foreach ($this->session->data['vouchers'] as $voucher) {
                        $order_data['vouchers'][] = array(
                            'description'      => $voucher['description'],
                            'code'             => token(10),
                            'to_name'          => $voucher['to_name'],
                            'to_email'         => $voucher['to_email'],
                            'from_name'        => $voucher['from_name'],
                            'from_email'       => $voucher['from_email'],
                            'voucher_theme_id' => $voucher['voucher_theme_id'],
                            'message'          => $voucher['message'],
                            'amount'           => $voucher['amount']
                        );
                    }
                }

                // Order Totals
                $this->load->model('extension/extension');

                $order_data['totals'] = array();
                $total = 0;
                $taxes = $this->cart->getTaxes();

                $sort_order = array();

                $results = $this->model_extension_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('total/' . $result['code']);

                        $this->{'model_total_' . $result['code']}->getTotal($order_data['totals'], $total, $taxes);
                    }
                }

                $sort_order = array();

                foreach ($order_data['totals'] as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $order_data['totals']);

                if (isset($this->request->post['comment'])) {
                    $order_data['comment'] = $this->request->post['comment'];
                } else {
                    $order_data['comment'] = '';
                }

                $order_data['total'] = $total;

                if (isset($this->request->post['affiliate_id'])) {
                    $subtotal = $this->cart->getSubTotal();

                    // Affiliate
                    $this->load->model('affiliate/affiliate');

                    $affiliate_info = $this->model_affiliate_affiliate->getAffiliate($this->request->post['affiliate_id']);

                    if ($affiliate_info) {
                        $order_data['affiliate_id'] = $affiliate_info['affiliate_id'];
                        $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                    } else {
                        $order_data['affiliate_id'] = 0;
                        $order_data['commission'] = 0;
                    }
                } else {
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                }

                $this->model_checkout_order->editOrder($order_id, $order_data);

                // Set the order history
                if (isset($this->request->post['order_status_id'])) {
                    $order_status_id = $this->request->post['order_status_id'];
                } else {
                    $order_status_id = $this->config->get('config_order_status_id');
                }

                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);

                $json['success'] = $this->language->get('text_success');
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
    }



}
