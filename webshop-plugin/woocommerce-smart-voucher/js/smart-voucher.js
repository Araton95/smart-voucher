jQuery( function( $ ) {
  $("body").on('click', '.smart-voucher-change-coupon-amount', function(e){
    e.preventDefault();
    if ($("#smart-voucher-change-coupon-amount-dialog").length == 0) {
      let dialogCont = $('<div id="smart-voucher-change-coupon-amount-dialog">' + 
       '<div class="change-coupon-amount-dialog-content"></div>' + 
       '</div>');
       
      $("body").append(dialogCont);
      $("#smart-voucher-change-coupon-amount-dialog").dialog({
        modal: true,
        autoOpen: false,
        width: 350
      });
    }
    
    let coupon = $(this).attr('data-coupon');
    
    $.post(smart_voucher_coupon.ajax_url, {
      'action': 'override_smart_voucher',
      'coupon': coupon,
      'cart': null,
    }, function(response) {
      render_smart_voucher_response(response);
      $("#smart-voucher-change-coupon-amount-dialog").dialog('open');
    }, 'json');
  });
  
  $("body").on('click', '.btn-smart-voucher-override-amount', function(e){
    e.preventDefault();
    let amount = $("#smart-voucher-amount").val();
    let coupon = $("#smart-voucher-form-coupon").val();
        $.post(smart_voucher_coupon.ajax_url, {
          'action': 'override_smart_voucher',
          'coupon': coupon,
          'amount': amount,
          'smart_voucher_override_amount': 1
        }, function(resp) {
          if (resp.success) {
            $("#smart-voucher-change-coupon-amount-dialog").dialog('close');
          }
          render_smart_voucher_response(resp);
        }, 'json');
  });
  
  function render_smart_voucher_response(response) {
    if (response.form) {
      $(".change-coupon-amount-dialog-content").html(response.form);
    } else if (response.error) {
      $(".change-coupon-amount-dialog-content").html(response.error);
    } else if (response.success) {
      $(".change-coupon-amount-dialog-content").empty();
      $( '.cart_totals' ).replaceWith( response.content );
      $( document.body ).trigger( 'updated_cart_totals' );
    }
  }
});
