<?php
/**
 * Plugin Name: WooCommerce Smart Voucher
 * Plugin URI: https://github.com/w-sys/woocommerce-smart-voucher
 * Description: Create and use SmartVouchers
 * Version: 1.0
 * Requires at least: 4.4
 * Author: Grigor Farishyan
 * Author URI: https://websystems.am
 * License: GPLv2
 * Text Domain: woocommerce-smart-voucher
 * Domain Path: /languages
 * 
 * 
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN', 'woocommerce-smart-voucher');
define('WOOCOMMERCE_SMARTVOUCHER_REST_BASE', 'woocommerce-smart-voucher/v1');
define('WOOCOMMERCE_SMARTVOUCHER_LIBS_PATH', dirname(__FILE__));

require_once WOOCOMMERCE_SMARTVOUCHER_LIBS_PATH . '/vendor/autoload.php';

use SmartVoucher\Client as SmartVoucherClient;
use SmartVoucher\Webshop as SmartVoucherWebshop;
use SmartVoucher\Voucher as Voucher;
use SmartVoucher\Util as SmartVoucherUtil;
use SmartVoucher\PartnerRequest;
use SmartVoucher\SmartVoucherException;
use kornrunner\Solidity;


function woocommerce_smart_voucher_missing_wc_notice() {
  	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Smart Voucher requires WooCommerce to be installed and active. You can download %s here.', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_smart_voucher_init');
register_activation_hook(__FILE__, array('WooCommerceSmartVoucher', 'activate_plugin'));

class WooCommerceSmartVoucher {
  
  public static $instance;
  private $options;
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
    //add_action('rest_api_init', array($this, 'rest_init'));
    $this->options = get_option('woocommerce_smart_voucher');
    include_once plugin_dir_path(__FILE__) . 'includes/smart_voucher.post_type.php';
    include_once plugin_dir_path(__FILE__) . 'includes/smart_voucher.coupon.php';
    
    add_filter('woocommerce_product_data_tabs', array($this, 'woocommerce_product_data_tabs'));
    
    if (is_admin()) {
      //add_action('wp_ajax_generate_site_wallet', array($this, 'generate_site_wallet'));
      add_action('admin_init', array($this, 'admin_init'));
      add_action('admin_menu', array($this, 'admin_menu'));
      add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
      add_action('woocommerce_product_data_panels', array($this, 'woocommerce_product_data_panels'));
      add_action('woocommerce_admin_process_product_object', array($this, 'woocommerce_admin_process_product_object'));
    }
        
    add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
    //when order complete check do we have a smart voucher product in it.
    
    add_action('woocommerce_order_status_completed', array($this, 'maybe_create_smart_voucher'));
    add_action('woocommerce_order_status_processing', array($this, 'maybe_create_smart_voucher'));
    
    add_action('woocommerce_smart_voucher_create_vouchers', array($this, 'createSmartVouchers'));
    add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'formated_meta_data'), 10, 2);
  }
  
  public function woocommerce_admin_process_product_object($product) {
    if (isset($_POST['_smart_voucher_price'])) {
      $product->update_meta_data('_smart_voucher', $_POST['_smart_voucher']);
      $product->update_meta_data('_smart_voucher_price', $_POST['_smart_voucher_price']);
    }
  }
  
  
  
  private function log($type, $str) {
    
  }
  
  public function formated_meta_data($formatted_meta, $instance) {
    $meta = $instance->get_meta_data('smart_voucher_coupon');
    
    if (empty($meta)) {
      return $formatted_meta;
    }
    
    foreach ($meta as $item) {
      
      if (empty($item->value))
        continue;

      $formatted_meta[$item->id] = (object) array(
        'key' => $item->key,
        'value' => $item->value,
        'display_key' => __('Smart Voucher'),
        'display_value' => str_replace("|", "<br>", $item->value),
      );
    }
    
    return $formatted_meta;
  }
  
  public function maybe_create_smart_voucher($order_id) {
    //check that this order is completed
    global $wpdb;
    $order = new WC_Order( $order_id );
    $smart_vouchers = array();
    $email = $order->get_billing_email();
    
    foreach ( $order->get_items() as $item) {
      if (!$item->is_type( 'line_item' )) 
        continue;
      
      $qty = $item->get_quantity();
      if ($qty == 0)
        continue;
      
      $product = $item->get_product();
      if ( ! $product ) {
			  continue;
			}
        
      if (!$product->is_virtual()) {
        continue;
      }
      
      if (!($product->get_meta('_smart_voucher') === 'yes')) 
        continue;
      
      if (empty($product->get_meta('_smart_voucher_price')))
        continue;
      
      //may be we need to create smart voucher
      //add a hook to check
      $coupon_codes = array();
      //first of all check do we have smart vouchers with same order_id and compare counts
      $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->prefix . 'posts p INNER JOIN '.$wpdb->prefix.'postmeta pm 
        ON p.ID=pm.post_id
        WHERE p.post_type=%s AND pm.meta_key=%s AND pm.meta_value=%d', array(SmartVoucher::SMART_VOUCHER_POST_TYPE, '_order', $order_id)));
      
      $qty -= $count;
            
      for ($i = 0; $i < $qty; $i++) {
        $params = array(
          'initial_price' => $product->get_meta('_smart_voucher_price'),
          'order_email' => $email,
          'product' => $product,
          'order' => $order_id,
        );

        try {
          $webshop = $this->getSmartVoucherClient()->getWebshop($this->options['wallet']);
          $voucher = Voucher::createFromArray(array(
              'amount' => $product->get_meta('_smart_voucher_price') * 100,
              'initialAmount' => $product->get_meta('_smart_voucher_price') * 100,
              'nonce' => $webshop->getNonce(),
              'webshopAddr' => $this->options['wallet'],
            ));
          
          try {
            $this->getSmartVoucherClient()->createVoucher($voucher);
            $coupon_codes[] = $params['coupon_code'] = $voucher->getVoucherCode();
            $error = NULL;
            //update order meta data
            
            $smart_voucher_post = array(
              'post_type' => SmartVoucher::SMART_VOUCHER_POST_TYPE,
              'post_title' => 'Draft',
              'post_status' => 'draft',
              'ping_status' => false,
              'meta_input' => array(
                '_initial_amount' => $params['initial_price'],
                '_order' => $params['order'],
                '_coupon_code' => $voucher->getVoucherCode(),
                '_email' => $params['order_email'],
               ),
             );
            
            wp_insert_post($smart_voucher_post, $error);
          } catch (\Exception $voucher_e) {
            return;
          }
        } catch (\Exception $e) {
          return;
        }
        
        $smart_vouchers[] = $params;
      }
      
      if (!empty($coupon_codes)) {
        wc_update_order_item_meta($item->get_id(), 'smart_voucher_coupon', implode('|', $coupon_codes));
      }
    }
    
    //if (empty($smart_vouchers)) {
    //  return;
    //}
    
    //do_action('woocommerce_smart_voucher_create_vouchers', $smart_vouchers);
  }
  
  private function getSmartVoucherClient() {
    static $client;
    
    if (!isset($client)) {
      $client = new SmartVoucherClient();
      $client->setPrivateKey($this->options['private_key']);
    }
    
    return $client;
  }
  
  /**
   * Actually create vouchers
   */
  public function createSmartVouchers(array $vouchers) {
    //get store Information
    $error = NULL;
    
    //we don't want to trigger smart_voucher staff here.
    foreach ($vouchers as $smart_voucher) {
      wp_insert_post(array(
        'post_type' => SmartVoucher::SMART_VOUCHER_POST_TYPE,
        'post_title' => 'Draft',
        'post_status' => 'draft',
        'ping_status' => false,
        'meta_input' => array(
          '_initial_amount' => $smart_voucher['initial_price'],
          '_email' => $smart_voucher['order_email'],
          '_order' => $smart_voucher['order'],
          '_coupon_code' => isset($smart_voucher['coupon_code']) ? $smart_voucher['coupon_code'] : '',
        ),
      ), $error);
    }
    
  }
  
  public function woocommerce_product_data_tabs($tabs) {
    $tabs['smart_voucher'] = array(
      'label' => __('Smart Voucher', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN),
      'target' => 'smart_voucher_data',
      'class' => array('show_if_virtual'),
      'priority' => 80,
    );
    
    return $tabs;
  }
  
  public function woocommerce_product_data_panels($array) {
    ?>
    <div id="smart_voucher_data" class="panel woocommerce_options_panel hidden">
      <div class="options_group show_if_virtual">
        <?php 
          woocommerce_wp_checkbox(array(
            'id' => '_smart_voucher',
            'wrapper_class' => '',
            'label' => __('Sell as a smart voucher', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN),
            'description' => __('This product represented as a Smart voucher.'),
          ));
          woocommerce_wp_text_input(array(
            'id' => '_smart_voucher_price',
            'wrapper_class' => '',
            'class' => 'short wc_input_price',
            'label' => __('Voucher Initial Balance', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN) . ' (' . get_woocommerce_currency_symbol() . ')',
            'description'   => __( 'Initial balance to set when Smart Voucher will be created', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN ),
            'data_type' => 'price',
          ));
        ?>
      </div>
    </div>
    <?php
  }
  
  
  public function admin_init() {
    
    register_setting( 'woocommerce_smart_voucher', 'woocommerce_smart_voucher', array($this, 'validateSettings'));
    
    add_settings_section( 'woocommerce_smart_voucher_account_section', 
      __('Webshop Account', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 
      array($this, 'section_text'), 'woo-smart-voucher-admin' );
      
    add_settings_field('wallet', 
      __('Wallet', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 
      array($this, 'settings_wallet'), 
      'woo-smart-voucher-admin',
      'woocommerce_smart_voucher_account_section'
    );
        
    add_settings_field('private_key', 
      __('Private Key', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 
      array($this, 'settings_private_key'), 
      'woo-smart-voucher-admin',
      'woocommerce_smart_voucher_account_section'
    );
    
    add_settings_field('website', 
      __('Website', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 
      array($this, 'settings_website'), 
      'woo-smart-voucher-admin',
      'woocommerce_smart_voucher_account_section'
    );
    
    add_settings_field('email', 
      __('Email', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 
      array($this, 'settings_email'), 
      'woo-smart-voucher-admin',
      'woocommerce_smart_voucher_account_section'
    );
    
  }
  
  public function register_scripts() {
    wp_register_script('woocommerce-smart-voucher-js', plugins_url('/js/smart-voucher.js', __FILE__), array(
      'jquery',
      'jquery-ui-dialog',
    ), true);
    wp_register_style('woocommerce-smart-voucher-styles', plugins_url('/css/front.css', __FILE__));
    //wp_enqueue_script('woocommerce-smart-voucher-js');
    wp_enqueue_style('woocommerce-smart-voucher-styles');
  }
  
  public function admin_scripts() {
    wp_register_script('woocommerce-smart-voucher-web3', plugins_url('/js/web3.min.js', __FILE__));
    wp_register_script('woocommerce-smart-voucher-admin', plugins_url('/js/admin.js', __FILE__), array(
      'jquery',
      'jquery-ui-dialog',
      'woocommerce-smart-voucher-web3'
    ),null, true);
    wp_enqueue_script('woocommerce-smart-voucher-web3');
    wp_enqueue_script('woocommerce-smart-voucher-admin');
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    wp_enqueue_style( 'woocommerce-smart-voucher-admin',  plugins_url('/css/admin.css', __FILE__));
    
    wp_localize_script('woocommerce-smart-voucher-admin', 'settings', array(
      'wallet_register_url' => rest_url(WOOCOMMERCE_SMARTVOUCHER_REST_BASE . '/registerWallet'),
      'nonce' => wp_create_nonce( 'wp_rest' ),
    ));
  }
  
  
  public function validateSettings($input) {
    $has_errors = false;
    //add_settings_error('woocommerce_smart_voucher', 'test', print_r($input, true), 'error');
    if (empty($input['wallet'])) {
      add_settings_error('woocommerce_smart_voucher', 'wallet', __('Invalid Wallet specified'), 'error');
      $has_errors = true;
    }
    
    if (empty($input['private_key'])) {
      add_settings_error('woocommerce_smart_voucher', 'private_key', __('Invalid private key specified'), 'error');
      $has_errors = true;
    }
    
    $site_url = get_site_url();
    $website = parse_url($site_url, PHP_URL_HOST);
    $input['website'] = $website;
    
    if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
      $had_errors = true;
      add_settings_error('woocommerce_smart_voucher', 'email', __('Specify valid email address'), 'error');
    }
       
    
    if (!$has_errors) {
      $webshop = SmartVoucherWebshop::createFromArray(array(
        'wallet' => $input['wallet'],
        'email' => $input['email'],
        'website' => $website,
      ));
      
      $client = new SmartVoucherClient();
      /*try {
        $wallet = $client->getWebshop($webshop->getWallet());
        if (!empty($wallet)) {
          add_settings_error('woocommerce_smart_voucher', 'private_key', "Wallet is: ", 'error');
        }
      } catch (SmartVoucherException $e) {
        //add_settings_error('woocommerce_smart_voucher', 'private_key', $e->getMessage(), 'error');
      }*/
      
      if (empty($wallet)) {
        try {
          $client->registerWebshop($webshop);
        } catch (SmartVoucherException $e) {
          add_settings_error('woocommerce_smart_voucher', 'CA', $e->getMessage(), 'error');
        }
      }
    }
    
    return $input;
  }
  
  public function settings_website() {
    print sprintf("%s://%s", ($_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http', $_SERVER['SERVER_NAME']);
  }
  
  public function settings_private_key() {
    $description = __('Please specify private key file  on your system.');
    $private_key = isset($_POST['private_key']) ? $_POST['private_key'] :  $this->options['private_key'];
    $private_key = sanitize_text_field($private_key);
    print '<input type="text" name="woocommerce_smart_voucher[private_key]" id="private_key" value="'.$private_key.'"><br>' . $description;
  }
    
  public function settings_wallet() {
    $description = sprintf(__('Please specify your existing wallet or <a href="#" class="obtain-wallet">Generate</a> a one.'), '');
    $wallet = isset($_POST['wallet']) ? $_POST['wallet'] :  $this->options['wallet'];
    $wallet = sanitize_text_field($wallet);

    print '<input type="text" name="woocommerce_smart_voucher[wallet]" id="wallet" value="'.$wallet.'"><br>' . $description;
  }
  
  public function settings_email() {
    $email = isset($_POST['email']) ? $_POST['email'] :  $this->options['email'];
    $email = sanitize_text_field($email);
    print '<input type="text" name="woocommerce_smart_voucher[email]" value="'.$email.'">';
  }
  
  
  
  public function admin_menu() {
    
    $page_title = $menu_title = __('Smart Voucher', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN);
    
    add_menu_page($page_title, $menu_title, 'manage_options', 'woo-smart-voucher', '', '', 6); //array($this, 'render_settings_page')
    add_submenu_page('woo-smart-voucher', __('Settings', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), __('Settings', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 'manage_options', 'settings', array($this, 'render_settings_page'));
    add_submenu_page('woo-smart-voucher', __('Partners', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), __('Partners', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 'manage_options', 'partners', array($this, 'render_partners_page'));
    
    add_submenu_page('woo-smart-voucher', __('Subscribers', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), __('Subscribers', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 'manage_options', 'subscribers', array($this, 'render_subscribers_page'));
    
    add_options_page( __('Settings', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), __('Settings', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN), 'manage_options', "woo-smart-voucher", array($this, 'render_settings_page'));
  }
  
  public function section_text($args) {
    print "";
  }
  
  public function render_settings_page() {
    //locate_template(plugin_basename(__FILE__) . );
    include_once dirname(__FILE__) . '/templates/settings.php';
  }
  
  public function render_partners_page() {
    $client = new SmartVoucherClient();
    
    $partners = array();
    $settings = get_option('woocommerce_smart_voucher');
    $client->setPrivateKey($settings['private_key']);
    
    $webshop_details = NULL;
    $existings_partners_ids = array();
    $existings_partners = array();
    try {
      $webshop = $client->getWebshop($settings['wallet']);
    } catch (\Exception $e) {
      print "Could not retrieve webshop details.";
      return;
    }
    if ($webshop) {
      foreach ($webshop->getPublishers() as $publisher ) {
        $existings_partners_ids[$publisher->getWallet()] = $publisher->getWallet();
      }
    }
    
    try {
      foreach ($client->getWebshops() as $partner) {
        if ($partner->getWallet() == $webshop->getWallet())
          continue;
        //build tree
        if (isset($existings_partners_ids[$partner->getWallet()])) {
          $existings_partners[$partner->getWallet()] = $partner->getWebsite();
        } else {
          $partners[$partner->getWallet()] = $partner->getWebsite();
        }
      }
    } catch (\Exception $e) {
      print "Something went wrong";
      return;
    }
    
    if ($_POST) {
      $remove_partners = array();
      $add_partners = array();
      
      if (empty($_POST['partners']) && !empty($existings_partners_ids)) {
        $remove_partners = $existings_partners_ids;
      } else if ($_POST['partners']) {
        $ids = array_keys($_POST['partners']);
        $posted_partners = array_combine($ids, $ids);
        $add_diff = array_diff($posted_partners, $existings_partners_ids);
        $remove_diff = array_diff($existings_partners_ids, $posted_partners);
        
        if (!empty($remove_diff)) {
          $remove_partners = array_combine($remove_diff, $remove_diff);
        }
        
        if (!empty($add_diff)) {
          $add_partners = array_combine($add_diff, $add_diff);
        }
      }
      
      $actions = array();
      
      if (!empty($remove_partners)) {
        $actions['removePartner'] = $remove_partners;
      }
      
      if (!empty($add_partners)) {
        $actions['addPartner'] = $add_partners;
      }
      
      if (!empty($actions)) {
        foreach ($actions as $action => $partner_list) {
          try {
              $requester_shop = $client->getWebshop($settings['wallet']);
            } catch (\Exception $e) {
              print "Something went wrong";
              return;
            }
          
            $requester = new PartnerRequest();
            $requester->setNonce($requester_shop->getNonce());
            $requester->setWebshopAddr($settings['wallet']);
            try {
              $result = $client->$action($requester, $partner_list);
              if ($result) {
                if ($action == 'removePartner') {
                  foreach ($partner_list as $e_partner) {
                    $partners[$e_partner] = $existings_partners[$e_partner];
                    unset($existings_partners[$e_partner]);
                  }
                } else {
                  foreach ($partner_list as $e_partner) {
                    $existings_partners[$e_partner] = $partners[$e_partner];
                    unset($partners[$e_partner]);
                  }
                }
              }
            } catch (\Exception $e) {
              print 'Something went wrong ' . $e->getMessage();
            }
         }
      }
    }
    
    natsort($partners);
    natsort($existings_partners);
    
    print sprintf("<h2>%s</h2>", __('Partners', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN));
    print '<form method="post">';
    print "<ul>";
    
    foreach ($existings_partners as $wallet => $name) {
      print sprintf('<li><input type="checkbox" name="partners[%s]" value="1" checked> %s', $wallet, $name);
    }
    
    foreach ($partners as $wallet => $name) {
      print sprintf('<li><input type="checkbox" name="partners[%s]" value="1"> %s', $wallet, $name);
    }
    
    print "</ul>";
    if (!empty($partners) || !empty($existings_partners)) {
      print submit_button();
    }
    
    print "</form>";
    
  }
  
  public function render_subscribers_page() {
    $client = new SmartVoucherClient();
    $settings = get_option('woocommerce_smart_voucher');
    try {
      $webshop = $client->getWebshop($settings['wallet']);
    } catch (\Exception $e) {
      print "Could not retrieve webshop details.";
      return;
    }
    
    print "<ul>";
    $has_subscribers = false;
    foreach ($webshop->getSubscribers() as $subscriber) {
      print sprintf("<li>%s</li>", esc_html($subscriber->getWebsite()));
      $has_subscribers = true;
    }
    print "</ul>";
    
    if (!$has_subscribers) {
      print "No Subscribers found yet.";
    }
    
  }
  
  public function activate_plugin() {
    
  }
}


function woocommerce_smart_voucher_init() {
  load_plugin_textdomain( WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
  if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_smart_voucher_missing_wc_notice' );
		return;
	}
  
  WooCommerceSmartVoucher::get_instance();
}
