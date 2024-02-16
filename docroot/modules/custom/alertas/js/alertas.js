(function ($) {
  Drupal.behaviors.alertasBehavior = {
    attach: function (context, settings) {
    		$('#edit-field-avance-meta-proceso-0-value, #edit-field-avance-meta-producto-0-value, #edit-field-avance-impacto-0-value, #edit-field-valor-producto-2-0-value').on('input blur paste', function(){
                $(this).val($(this).val().replace(/[^0-9\.]/g,''))
               });
    
    }
  };

})(jQuery);