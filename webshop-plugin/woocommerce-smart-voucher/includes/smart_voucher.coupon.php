<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WOOCOMMERCE_SMARTVOUCHER_LIBS_PATH . '/vendor/autoload.php';

use SmartVoucher\Client as SmartVoucherClient;
use SmartVoucher\Webshop as SmartVoucherWebshop;
use SmartVoucher\Voucher as Voucher;
use SmartVoucher\RedeemVoucher as RedeemVoucher;
use SmartVoucher\Util as SmartVoucherUtil;
use SmartVoucher\SmartVoucherException;
use kornrunner\Solidity;

class SmartVoucherCoupon  { //extends WC_Legacy_Coupon
  
  protected static $instance;
  public const DISCOUNT_TYPE = 'smart_voucher_coupon';
  	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'coupons';
  public $settings;
  protected $data = array(
		'code'                        => '',
		'amount'                      => 0,
		'date_created'                => null,
		'date_modified'               => null,
		'date_expires'                => null,
		'discount_type'               => 'fixed_cart',//self::DISCOUNT_TYPE,
		'description'                 => '',
		'usage_count'                 => 0,
		'individual_use'              => false,
		'product_ids'                 => array(),
		'excluded_product_ids'        => array(),
		'usage_limit'                 => 0,
		'usage_limit_per_user'        => 0,
		'limit_usage_to_x_items'      => null,
		'free_shipping'               => false,
		'product_categories'          => array(),
		'excluded_product_categories' => array(),
		'exclude_sale_items'          => false,
		'minimum_amount'              => '',
		'maximum_amount'              => '',
		'email_restrictions'          => array(),
		'used_by'                     => array(),
		'virtual'                     => true,
	);
  
  public function __construct($data = '') {
    //parent::__construct($data);
    $this->init();
  }
  
  public static function get_instance() {
	 if ( null === self::$instance ) {
		self::$instance = new self();
	 }
   
	 return self::$instance;
  }
  
  
  public function init() {
    add_filter('woocommerce_coupon_discount_types', array($this, 'woocommerce_coupon_discount_types'));
    add_filter('wc_get_cart_coupon_types', array($this, 'woocommerce_coupon_discount_types'));
    add_filter('woocommerce_get_shop_coupon_data', array($this, 'woocommerce_shop_coupon_data'), 10, 2);
    add_filter('woocommerce_coupon_is_valid', array($this, 'validateVoucherCode'), 10, 2);
    add_action('woocommerce_order_status_completed', array($this, 'redeem_voucher'));
    add_action('woocommerce_order_status_processing', array($this, 'redeem_voucher'));
    add_filter('woocommerce_cart_totals_coupon_html', array($this, 'alter_cart_coupon_form'), 10, 3);
    add_action('wp_ajax_nopriv_override_smart_voucher', array($this, 'override_smart_voucher_amount'));
    add_action('wp_ajax_override_smart_voucher', array($this, 'override_smart_voucher_amount'));
    $this->settings = get_option('woocommerce_smart_voucher');
    //add_action('woocommerce_coupon_is_valid_for_cart', array($this, 'redeem_voucher'));
    
    //filter to set initial amount based on coupon code
    add_filter('woocommerce_smart_voucher_coupon_initial_amount', array($this, 'smart_voucher_coupon_initial_amount'), 10, 2);
    //add_action('woocommerce_admin_order_data_after_order_details', array($this, 'smart_voucher_coupon_order_meta'));
  }
  
  
  public function override_smart_voucher_amount() {
    //$coupon;
    //$cart_id
    //create a form 
    
    $coupon = $_POST['coupon'];
    $resp = new stdClass();
    
    if (empty($coupon)) {
      $resp->error = esc_html(__("Provide valid coupon", WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN));
      print json_encode($resp);
      wp_die();
    }
    
    try {
      $coupon_id = $this->getSmartVoucherClient()->validateVoucherCode($this->settings['wallet'], $coupon);
    } catch (\Exception $e) {
      $resp->error = __("Invalid coupon provided", WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
      print json_encode($resp);
      wp_die();
    }
    
    try {
      $cuopon_details = $this->getSmartVoucherClient()->getVoucher($coupon_id);
    } catch (\Exception $e) {
      $resp->error = __('Something went wrong try again', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
      print json_encode($resp);
      wp_die();
    }
    
    $max_amount = ($cuopon_details->getCurrentAmount() / 100.00);
    $form[] = '<form method="post">';
    $form[] = sprintf('<input type="text" name="amount" value="%s" size="10" id="smart-voucher-amount"> / %s', $max_amount, $max_amount);
    $form[] = '<input type="hidden" name="smart_voucher_override_amount" value="1">';
    $form[] = '<input type="hidden" name="coupon" id="smart-voucher-form-coupon" value="'.esc_attr($coupon).'">';
    $form[] =  wp_nonce_field( 'smart_voucher_coupon_code_'. $coupon, '_wpnonce', true, false );
    $form[] =  '<input type="submit" name="submit" value="Change" class="btn-smart-voucher-override-amount">';
    $form[] = '</form>';
    
    $has_error = null;
    if (!empty($_POST['smart_voucher_override_amount'])) {
      //check is decimal our amount
      $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, array(
        'options' => array(
          'min_range' => 0.1,
          'max_range' => $max_amount,
        ),
      ));
      
      if (!$amount) {
        $has_error = _('Invalid value entered', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
      } else {
        $session = WC()->session->get('smart_voucher_overrides');
        $session[$coupon] = $amount;
        WC()->session->set('smart_voucher_overrides', $session);
        WC()->cart->remove_coupon($coupon);
        WC()->cart->apply_coupon($coupon);
        WC()->cart->calculate_totals();
        
        ob_start();
        woocommerce_cart_totals();
        $content = ob_get_contents();
        ob_end_clean();
        $resp->success = true;
        $resp->content = $content;
        print json_encode($resp);
        wp_die();
      }
    }
    
    if ($has_error) {
      array_unshift($form, sprintf('<div class="error">%s</div>', $has_error));
    }
    
    $resp->form = implode("\n", $form);
    print json_encode($resp);
    wp_die();
  }
  
  public function alter_cart_coupon_form($coupon_html, $coupon, $discount_amount_html) {
    $voucher_id = $this->validateCouponCode($coupon->get_code());
    if (!$voucher_id)
        return $coupon_html;
    
    $voucher = $this->getSmartVoucherClient()->getVoucher($voucher_id);
    
    $coupon_html = str_replace($discount_amount_html, sprintf('<a class="smart-voucher-change-coupon-amount" data-coupon="%s" title="%s">%s</a>', $coupon->get_code(), esc_attr('Change amount', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), $discount_amount_html),$coupon_html);
    static $i10n;
    
    if (!isset($i10n)) {
      $i10n = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'coupons' => array(),
      );
    }
    
    $i10n['coupons'][] = $coupon->get_code();
    
    wp_localize_script('woocommerce-smart-voucher-js', 'smart_voucher_coupon', $i10n);
    wp_enqueue_style("wp-jquery-ui-dialog");
    wp_enqueue_script('woocommerce-smart-voucher-js');
    return $coupon_html;
  }
  
  /***
   * return voucher id if code is valid otherwise 
   * return false
   */
  private function validateCouponCode($code) {
    if ( strlen($code) !== 32) {
      return false;
    }
    
    $voucher_id = false;
    try {
      $voucher_id = $this->getSmartVoucherClient()->validateVoucherCode($this->settings['wallet'], $code);
    } catch (\Exception $e) {
      $voucher_id = false;
    }
    return $voucher_id;
  }
  
  
  public function validateVoucherCode($true, $instance) {
    $code = $instance->get_code();
    
    if ($instance->get_discount_type() !== 'fixed_cart')
      return $true;
    
    return $this->validateCouponCode($code);
  }
  
  public function woocommerce_coupon_discount_types($types) {
    $types['smart_voucher_coupon'] = __('Smart Voucher', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
    return $types;
  }
  
  public function woocommerce_shop_coupon_data($data, $code) {
    $code = trim($code);
    if (is_int($data))
      return $data;
    
    if (empty($code))
      return $data;
    
    if (strlen($code) !== 32) {
      return $data;
    }
    
    //check is this code valid 
    
    $voucher_id = $this->validateCouponCode($code);
    
    if (empty($voucher_id))
      return $data;

    //get voucher details
    try {
      $voucher = $this->getSmartVoucherClient()->getVoucher($voucher_id);
    }catch (\Exception $e) {
      return $data;
    }
    
    $amount = $voucher->getCurrentAmount();
    $amount = apply_filters('woocommerce_smart_voucher_coupon_initial_amount', $amount, $code);
    
    $data = array(
      'discount_type' => 'fixed_cart',
      'amount' => $amount,
      'individual_use' => false,
      'product_ids' => array(),
      'exclude_product_ids' => array(),
      'usage_limit' => '',
      'usage_limit_per_user' => '',
      'limit_usage_to_x_items' => '',
      'usage_count' => '',
      'expiry_date' => date("Y-m-d", strtotime("+10 years")),
      'free_shipping' => false,
      'product_categories' => array(),
      'exclude_product_categories' => array(),
      'exclude_sale_items' => false,
      'minimum_amount' => '',
      'maximum_amount' => '',
      'customer_email' => array(),
      'virtual' => true,
    );
    
    return $data;
  }
  
  private function is_smart_voucher_coupon($coupon) {
    if (empty($coupon) || strlen($coupon) != 32)
      return false;
    //validate coupon code
    return $this->validateCouponCode($coupon);
  }
  
    /**
   * Redeem smart voucher if it's used().
   */
  public function redeem_voucher($order_id) {
    $order = new WC_Order( $order_id );
    $coupons = $order->get_coupon_codes();
    
    $store_settings = get_option('woocommerce_smart_voucher');
    $private_key = $store_settings['private_key'];
    
    $redeemed_codes = $order->get_meta('smart_voucher_redeemed_codes');
    
    if (empty($redeemed_codes)) {
      $redeemed_codes = array();
    } else {
      $redeemed_codes = explode(',', $redeemed_codes);
    }
    
    array_walk($redeemed_codes, function(&$item, $key){
      $item = trim($item);
    });
    
    $errors = [];
    
    foreach ($order->get_coupons() as $coupon) {
      $data = $coupon->get_data();
      if (($rid = $this->is_smart_voucher_coupon($data['code']))) {
        
        if (in_array($data['code'], $redeemed_codes)) {
          continue;
        }
        
        try {
          $webshop = $this->getSmartVoucherClient()->getWebshop($store_settings['wallet']);
          $amount = $data['discount'] * 100;
          $nonce = $webshop->getNonce(); 
          $voucher = RedeemVoucher::createFromArray(array(
            'amount' => $amount,
            'webshopAddr' => $webshop->getWallet(),
            'id' => $rid,
            'nonce' => sprintf("%s", $nonce),
          ));

          try {
            $response = $this->getSmartVoucherClient()->redeemVoucher($voucher);
            $redeemed_codes[] = $data['code'];
          } catch (\Exception $re) {
            $errors[$data['code']] = $re->getMessage();
          }
        } catch (\Exception $e) {
          $errors[$data['code']] = $e->getMessage();
        }
      }
    }
    
    $order->add_meta_data('smart_voucher_redeemed_codes', implode(', ', $redeemed_codes));
    
    if (!empty($errors)) {
      $msg = [];
      foreach ($errors as $cp_code => $m) {
        $msg[] = sprintf("%s %s", $cp_code, $m);
      }
      $order->add_order_note( implode("\n", $msg) );
    }
    
    $order->save();
  }
    
  function smart_voucher_coupon_initial_amount($amount, $coupon) {
    //get the cart
    $cart = WC()->cart;
    
    if (empty($cart)) {
      return $amount;
    }
    
    $session = WC()->session->get('smart_voucher_overrides');
    if (isset($session)) {
      if (!empty($session[$coupon])) {
        return $session[$coupon];
      }
    }
    
    return $amount;
  }
  
  private function getSmartVoucherClient() {
    static $client;
    
    if (!isset($client)) {
      $client = new SmartVoucherClient();
      $client->setPrivateKey($this->settings['private_key']);
    }
    return $client;
  }
}

SmartVoucherCoupon::get_instance();


