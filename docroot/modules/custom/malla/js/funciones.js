
(function ($) {
  Drupal.behaviors.funcionesBehavior = {
    attach: function (context, settings) {
        // Attach a click listener to the clear button.
    //var clearBtn = document.getElementById('sub_528');
    
    //clearBtn.addEventListener('click', function() {
    $(".sumar").click(function() {
        var valor;
        valor = $(this).attr('id');
        var res = valor.substring(4); // Extraer el codigo del subprograma
        var sumproc = 0;
        var sumprod = 0;
        var x = 0, y = 0, z = 0, w = 0;
        // Recorrer todos los meses
        for (var mes = 1; mes <= 12; mes++) {
            switch(mes) {
              case 1:
                var mesvar = 'ene';
                break;
              case 2:
                var mesvar = 'feb';
                break;
              case 3:
                var mesvar = 'marz';
                break;
              case 4:
                var mesvar = 'abr';
                break;
             case 5:
                var mesvar = 'may';
                break;
             case 6:
                var mesvar = 'jun';
                break;
             case 7:
                var mesvar = 'jul';
                break;
             case 8:
                var mesvar = 'ago';
                break;
             case 9:
                var mesvar = 'sep';
                break;
             case 10:
                var mesvar = 'oct';
                break;
             case 11:
                var mesvar = 'nov';
                break;
             case 12:
                var mesvar = 'dic';
                break;
            }  
            if ($('#'+mesvar+'_proc_mnc_'+res).val().length > 0){
                x = parseFloat($('#'+mesvar+'_proc_mnc_'+res).val());    
            }else{
                x = 0;    
            }
            if ($('#'+mesvar+'_proc_mc_'+res).val().length > 0) {
                y = parseFloat($('#'+mesvar+'_proc_mc_'+res).val());
            }else{
                y = 0;
            }
            if ($('#'+mesvar+'_prod_mnc_'+res).val().length > 0) {
                z = parseFloat($('#'+mesvar+'_prod_mnc_'+res).val());
            }else{
                z = 0;
            }
            if ($('#'+mesvar+'_prod_mc_'+res).val().length > 0){
                w = parseFloat($('#'+mesvar+'_prod_mc_'+res).val());
            }else{
                w = 0;
            }
            
            sumproc += (x+y);
            sumprod += (z+w);
        }
        $('#totalproc_'+res).val(sumproc);
        $('#totalprod_'+res).val(sumprod);
            
        console.log(sumproc);
        console.log(sumprod);
       

        /*jQuery("#edit-table tr td input").each(function() {
          console.log(jQuery(this).val());
        });
        */
    });

    }
  };

})(jQuery);