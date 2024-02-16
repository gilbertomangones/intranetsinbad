(function ($) {
  Drupal.behaviors.funcionesBehavior = {
    attach: function (context, settings) {
            
            var meta = 0;
            var interno = 0;
            var externo = 0;
            var prodinterno = 0;
            var prodexterno = 0;
            var metaprod = 0; 
            var enero = 0;
            var febrero = 0;
            
				
              $('tbody').scroll(function(e) { //detect a scroll event on the tbody
                $('thead').css("left", -$("tbody").scrollLeft()); //fix the thead relative to the body scrolling
                $('thead th:nth-child(1)').css("left", $("tbody").scrollLeft()); //fix the first cell of the header
                $('tbody td:nth-child(1)').css("left", $("tbody").scrollLeft()); //fix the first column of tdbody
              });

    		jQuery("#exportarexcel").click(function(e){
                
                jQuery(".table-responsive").table2excel({
                    exlude: ".noExl",
                    name: "servicios",
                    filename: "serviciosbibliotecas.xls"
                });
            });
    		
    		jQuery("#exportarexcel").click(function(e){
                
                jQuery("#alertas-alertasavancesform .table-responsive").table2excel({
                    exlude: ".noExl",
                    name: "Metas",
                    filename: "Cumplimineto metas.xls"
                });
            });

            jQuery('#edit-field-proc-externo-0-value').keyup(function(){
                interno = $('#edit-field-proc-interna-0-value').val();
                externo = $('#edit-field-proc-externo-0-value').val();
                var meta = Number(interno) + Number(externo);
                    jQuery("#edit-field-meta-sesiones-0-value").val(meta);
                });
            jQuery('#edit-field-proc-interna-0-value').keyup(function(){
                interno = $('#edit-field-proc-interna-0-value').val();
                externo = $('#edit-field-proc-externo-0-value').val();
                var meta = Number(interno) + Number(externo);
                    jQuery("#edit-field-meta-sesiones-0-value").val(meta);
                });

            jQuery('#edit-field-prod-externo-0-value').keyup(function(){
                prodinterno = $('#edit-field-prod-interno-0-value').val();
                prodexterno = $('#edit-field-prod-externo-0-value').val();
                var metaprod = Number(prodinterno) + Number(prodexterno);
                    jQuery("#edit-field-numero-asistentes-0-value").val(metaprod);
                });
            jQuery('#edit-field-prod-interno-0-value').keyup(function(){
                prodinterno = $('#edit-field-prod-interno-0-value').val();
                prodexterno = $('#edit-field-prod-externo-0-value').val();
                var metaprod = Number(prodinterno) + Number(prodexterno);
                    jQuery("#edit-field-numero-asistentes-0-value").val(metaprod);
                });
            
            var total_porc = 0;
            jQuery('#totalporcenje input').keyup(function(){
                
                enero = $('#edit-field-porc-plan-enero-0-value').val();
                febrero = $('#edit-field-porc-plan-febrero-0-value').val();
                marzo = $('#edit-field-porc-plan-marzo-0-value').val();
                abril = $('#edit-field-porc-plan-abril-0-value').val();
                mayo = $('#edit-field-porc-plan-mayo-0-value').val();
                junio = $('#edit-field-porc-plan-junio-0-value').val();
                julio = $('#edit-field-porc-plan-julio-0-value').val();
                agosto = $('#edit-field-porc-plan-agosto-0-value').val();
                septiembre = $('#edit-field-porc-plan-sep-0-value').val();
                octubre = $('#edit-field-porc-plan-octubre-0-value').val();
                noviembre = $('#edit-field-porc-plan-nov-0-value').val();
                diciembre = $('#edit-field-porc-plan-dic-0-value').val();

                var total_porc = Number(enero) + Number(febrero) + Number(marzo) + Number(abril) + Number(mayo)+Number(junio)+Number(julio)+Number(agosto)+Number(septiembre)+Number(octubre)+Number(noviembre)+Number(diciembre);                
                 var total = jQuery("#edit-field-total-porcentaje-ejecucion-0-value").val(total_porc);
                 $('edit-field-total-porcentaje-ejecucion-0-value').prop( "disabled", true );
            });
          
    		jQuery('#edit-field-agenda-value').change(function()
            {
                if ($(this).is(':checked')) {
                	jQuery("#edit-field-es-planaccion-0").removeAttr('checked');
                	jQuery("#edit-field-es-planaccion-1").attr('checked', 'checked');
                }else{
                	jQuery("#edit-field-es-planaccion-1").removeAttr('checked');
                	jQuery("#edit-field-es-planaccion-0").attr('checked', 'checked');
                }
            });
            
	    }
  };

})(jQuery);
