<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
  <h2><?php print __('Smart voucher Settings', WOOCOMMERCE_SMARTVOUCHER_TEXT_DOMAIN) ?></h2>
  <?php settings_errors(); ?>
  <form method="post" action="options.php">
    <?php 
      settings_fields('woocommerce_smart_voucher');
      do_settings_sections( 'woo-smart-voucher-admin' );
      submit_button();
    ?>
  </form>
  <?php 
  /*
  <div id="smart-voucher-wallet-register" title="<?php print esc_html(__('Register wallet'));?>">
    <div class="form-item"><label><?php print __('Wallet') ?></label><input type="text" id="smart-voucher-wallet-register-wallet" name="wallet" readonly></div>
    <div class="form-item"><label><?php print __('Private Key') ?></label><textarea  name="wallet" readonly></textarea></div>
    <div class="form-item"><label><?php print __('Public Key') ?></label><textarea name="wallet" readonly></textarea></div>
    <a href="#" class="download"><?php print __('Download public & private files')?></a>
  </div>
  */?>
</div>