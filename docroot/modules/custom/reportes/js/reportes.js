
(function ($) {
  Drupal.behaviors.funcionesBehavior = {
    attach: function (context, settings) {
      // aqui el codigo
      alert(5);    

      jQuery("#exportar").click(function (e) {
          window.open('data:application/vnd.ms-excel,' + $('#edit-table').html());
          e.preventDefault();
      });
    }
  };

})(jQuery);