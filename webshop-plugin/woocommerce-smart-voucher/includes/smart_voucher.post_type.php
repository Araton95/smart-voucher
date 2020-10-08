<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WOOCOMMERCE_SMARTVOUCHER_LIBS_PATH . '/vendor/autoload.php';

use SmartVoucher\Client as SmartVoucherClient;
use SmartVoucher\Webshop as SmartVoucherWebshop;
use SmartVoucher\Voucher as Voucher;
use SmartVoucher\Util as SmartVoucherUtil;
use SmartVoucher\SmartVoucherException;
use kornrunner\Solidity;

class SmartVoucher {
  public static $instance;
  public const SMART_VOUCHER_POST_TYPE = 'smart_voucher';
  private $settings;
  public function __construct() {
    $this->init();
    
  }
  
  public static function get_instance() {
	 if ( null === self::$instance ) {
		self::$instance = new self();
	 }
   
	 return self::$instance;
  }
  
  public function init() {
    $this->settings = get_option('woocommerce_smart_voucher');
    register_post_type(self::SMART_VOUCHER_POST_TYPE, array(
      'labels' => array(
        'name' => __('Coupons', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN),
        'singular_name' => __('Coupon', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN),
      ),
      'description' => __('Manage Smart Voucher coupons', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN),
      'public' => false,
      'hierarchical' => false,
      'exclude_from_search' => true,
      'publicly_queryable' => false,
      'show_ui' => true,
      'show_in_menu' => sprintf('edit.php?post_type=%s', self::SMART_VOUCHER_POST_TYPE),
      'show_in_rest' => false,
      'has_archive' => false,
      'rewrite' => false,
      'feeds' => false,
      'delete_with_user' => false,
      'supports' => false,
      'register_meta_box_cb' => array($this, 'metaboxes')
    ));
    
    register_post_meta(self::SMART_VOUCHER_POST_TYPE, '_initial_amount', array(
      'type' => 'number',
      'single' => true,
      'default' => 0,
      'show_in_rest' => false,
     ));
    
    register_post_meta(self::SMART_VOUCHER_POST_TYPE, '_coupon_code', array(
      'type' => 'string',
      'single' => true,
      'default' => '',
      'show_in_rest' => false,
     ));
    
    register_post_meta(self::SMART_VOUCHER_POST_TYPE, '_email', array(
      'type' => 'string',
      'single' => true,
      'default' => '',
      'show_in_rest' => false,
     ));
    
    register_post_meta(self::SMART_VOUCHER_POST_TYPE, '_order', array(
      'type' => 'integer',
      'single' => true,
      'default' => '',
      'show_in_rest' => false,
     ));
    
    
    if (is_admin()) {
      add_action('admin_print_scripts', array($this, 'disable_autosave'));
      add_action('admin_menu', array($this, 'admin_menu'));
      add_filter('manage_' . self::SMART_VOUCHER_POST_TYPE . '_posts_columns', array($this, 'smart_voucher_post_columns'));
      add_action( 'manage_'.self::SMART_VOUCHER_POST_TYPE .'_posts_custom_column', array($this, 'smart_voucher_post_column'), 10, 2 );
      add_filter( 'posts_join', array($this, 'admin_search_meta_join') );
      add_filter( 'posts_where', array($this, 'admin_search_add_where'));
    }
    
    add_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'), 10);
  }
  
  public function admin_search_meta_join($join) {
    global $pagenow, $wpdb;
    
    if ( is_admin() && 'edit.php' === $pagenow && isset($_GET['post_type']) && self::SMART_VOUCHER_POST_TYPE === $_GET['post_type'] && ! empty( $_GET['s'] ) ) {    
        $join .= 'LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
    }
    return $join;
  }
  
  public function admin_search_add_where($where) {
    global $pagenow, $wpdb;
    
     if ( is_admin() && 'edit.php' === $pagenow && isset($_GET['post_type']) && self::SMART_VOUCHER_POST_TYPE === $_GET['post_type'] && ! empty( $_GET['s'] ) ) {
       $where = preg_replace(
            "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where );
    }
    return $where;
  }
  
  function smart_voucher_post_columns($columns) {
    unset( $columns['title'] );
    $columns['coupon_code'] = __('Coupon code', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
    $columns['initial_amount'] = __('Initial amount', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
    $columns['customer_email'] = __('Customer Email', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
    $columns['assocciated_order'] = __('Assocciated Order', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
    
    return $columns;
  }
  
  function smart_voucher_post_column($column, $post_id) {
    switch ($column) {
      case 'coupon_code':
        echo get_post_meta( $post_id , '_coupon_code' , true ); 
      break;
      case 'initial_amount':
        echo get_post_meta( $post_id , '_initial_amount' , true ); 
      break;
      case 'customer_email':
        echo get_post_meta( $post_id , '_email' , true ); 
      break;
      case 'assocciated_order':
        echo get_post_meta( $post_id , '_order' , true ); 
      break;
    }
  }
  
  public function save_smart_voucher($post_id) {
    global $pagenow, $wpdb;
    if (isset($_POST) && is_admin() && 'post.php' === $pagenow && get_post_type($post_id) == self::SMART_VOUCHER_POST_TYPE) {
      if (empty($_GET['post']) || (!empty($_GET['post']) && $_GET['post'] == $post_id)) {
        $initial_amount = isset($_POST['_initial_amount']) ? $_POST['_initial_amount'] : '';
        $_order = isset($_POST['_order']) ? $_POST['_order'] : '';
        $_email = isset($_POST['_email']) ? $_POST['_email'] : '';
        
        remove_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'));
        update_post_meta($post_id, '_initial_amount',  $initial_amount);
        update_post_meta($post_id, '_email',  $_email);
        update_post_meta($post_id, '_order',  $_order);
        add_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'));
      }
    }

    $post = get_post($post_id);

    $coupon_code = get_post_meta($post_id, '_coupon_code', true);
    $initial_amount = get_post_meta($post_id, '_initial_amount', true);    
    //skip draft smart voucher
    
    if (!empty($coupon_code)) {
      return;
    }
    
    $post_status = get_post_status($post_id);
    
    if ($post_status == 'draft')
      return;
    
    $switch_to_draft = false;
    if (empty($initial_amount)) {
      $this->changePostStatus($post, $post_status, 'draft');
      return;
    }

    //create a new smart voucher
    $store_settings = get_option('woocommerce_smart_voucher');
    $private_key = $store_settings['private_key'];
    try {
      $webshop = $this->getSmartVoucherClient()->getWebshop($this->settings['wallet']);
      $smart_voucher_initial_amount = $initial_amount * 100;
      $nonce = $webshop->getNonce();
      $voucher = Voucher::createFromArray(array(
          'amount' => $smart_voucher_initial_amount,
          'initialAmount' => $smart_voucher_initial_amount,
          'nonce' => "$nonce",
          'webshopAddr' => $store_settings['wallet'],
       ));      
      try {
        $this->getSmartVoucherClient()->createVoucher($voucher);
        if (empty($voucher->getVoucherCode())) {
          $this->changePostStatus($post, $post_status, 'draft');
          return;
        }
      } catch (\Exception $e_voucher) {
        $this->changePostStatus($post, $post_status, 'draft');
        return;
      }
    } catch (\Exception $e) {
      $this->changePostStatus($post, $post_status, 'draft');
      return;
    }
    
    if (empty($voucher) || empty($voucher->getVoucherCode()))
      return;
    
    $error = NULL;
    $this->changePostStatus($post, $post_status, 'published');
    remove_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'));
    update_post_meta($post_id, '_coupon_code',  $voucher->getVoucherCode());
    $post->title = $voucher->getVoucherCode();
    wp_update_post($post, $error);
    add_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'));
  }
  
  private function changePostStatus($post, $old_status, $new_status) {
    $error = NULL;
    remove_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'));
    wp_transition_post_status($new_status, $old_status, $post);
    if ($new_status == 'draft') {
      $post->title = 'Draft';
    }
    wp_update_post($post, $error);
    add_action('save_post_' . self::SMART_VOUCHER_POST_TYPE, array($this, 'save_smart_voucher'));
  }
  
  public function disable_autosave() {
    global $post;
    if($post && get_post_type($post->ID) === self::SMART_VOUCHER_POST_TYPE) {
      wp_deregister_script('autosave');
    }
  }
  
  public function metaboxes($post) {
    add_meta_box('_coupon_code', 'Coupon', array($this, 'coupon_meta_box'));
    add_meta_box('_initial_amount', 'Initial Amount', array($this, 'initial_price_meta_box'));
    add_meta_box('_order', 'Assocciated Order', array($this, 'assocciated_order_meta_box'));
    add_meta_box('_email', 'Email account', array($this, 'email_account_meta_box'));
  }
  
  public function coupon_meta_box() {
    global $post;
    $coupon = '';
    if ($post->ID) {
      $coupon = get_post_meta($post->ID, '_coupon_code', true);
    }
    
    print "<span>" . esc_html($coupon). "</span>";
  }
  
  public function initial_price_meta_box() {
    global $post;
    $initial_price = '';
    if ($post->ID) {
      $initial_price = get_post_meta($post->ID, '_initial_amount', true);
    }
    
    print '<input type="text" id="_initial_amount" name="_initial_amount" value="' . esc_attr($initial_price) .'">';
  }
  
  public function assocciated_order_meta_box() {
    global $post;
    $oid = '';
    if ($post->ID) {
      $oid = get_post_meta($post->ID, '_order', true);
    }
    print '<input type="text" id="_order" name="_order" value="'. esc_attr($oid) .'">';
  }
  
  public function email_account_meta_box() {
    global $post;
    $email = '';
    if ($post->ID) {
      $email = get_post_meta($post->ID, '_email', true);
    }
    
    print '<input type="text" id="_email" name="_email" value="'. esc_attr($email) .'">';
  }
  
  public function admin_menu() {
    $page_title = "Smart voucher Coupons";
    $menu_title = "Coupons";
    add_submenu_page('woo-smart-voucher', $page_title, $menu_title, 'manage_options', sprintf('edit.php?post_type=%s', self::SMART_VOUCHER_POST_TYPE));
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


SmartVoucher::get_instance();
