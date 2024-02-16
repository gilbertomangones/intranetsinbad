(function ($) {
  Drupal.behaviors.funcionesplanBehavior = {
    attach: function (context, settings) {
        // Attach a click listener to the clear button.
    //var clearBtn = document.getElementById('sub_528');
    
     
     $('input.numero').keyup(function(event) {
  		// skip for arrow keys
  		if(event.which >= 37 && event.which <= 40){
    		event.preventDefault();
  		}
  		$(this).val(function(index, value) {
    		return value
      		.replace(/\D/g, "")
      		.replace(/([0-9])([0-9]{0})$/, '$1$2')  
      		.replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ".")
    		;
  		});
	});
    
    $(".sumarcumplimiento").click(function() {
    
        var total_cumpl = 0;
        var valor;
        valor = $(this).attr('id');
        var res = valor.substring(5); // Extraer el codigo del subprograma // cump_xxx
        var enero = 0, feb=0, marzo =0, abril = 0, mayo = 0, junio=0, julio = 0, agosto=0, sep=0, oct=0, nov=0, dic=0;

        // Sumar % cumplimiento
            if ($('#cumpl_enero_'+res).val().length > 0) {
                enero = parseFloat($('#cumpl_enero_'+res).val());
            }
            if ($('#cumpl_febrero_'+res).val().length > 0){
                feb = parseFloat($('#cumpl_febrero_'+res).val());
            }
            if ($('#cumpl_marzo_'+res).val().length > 0){
                marzo = parseFloat($('#cumpl_marzo_'+res).val());
            }
            if ($('#cumpl_abril_'+res).val().length > 0){
                abril = parseFloat($('#cumpl_abril_'+res).val());
            }
            if ($('#cumpl_mayo_'+res).val().length > 0){
                mayo = parseFloat($('#cumpl_mayo_'+res).val());
            }
            if ($('#cumpl_junio_'+res).val().length > 0){
                junio = parseFloat($('#cumpl_junio_'+res).val());
            }
            if ($('#cumpl_julio_'+res).val().length > 0){
                julio = parseFloat($('#cumpl_julio_'+res).val());
            }
            if ($('#cumpl_agosto_'+res).val().length > 0){
                agosto = parseFloat($('#cumpl_agosto_'+res).val());
            }
            if ($('#cumpl_septiembre_'+res).val().length > 0){
                sep = parseFloat($('#cumpl_septiembre_'+res).val());
            }
            if ($('#cumpl_octubre_'+res).val().length > 0){
                oct = parseFloat($('#cumpl_octubre_'+res).val());
            }
            if ($('#cumpl_noviembre_'+res).val().length > 0){
                nov = parseFloat($('#cumpl_noviembre_'+res).val());
            }
            if ($('#cumpl_diciembre_'+res).val().length > 0){
                dic = parseFloat($('#cumpl_diciembre_'+res).val());
            }

            total_cumpl = enero + feb + marzo + abril + mayo + junio + julio + agosto + sep + oct + nov + dic;

            $('#totalcumpl_'+res).val(total_cumpl);
    });

    $(".sumarplanesaccion").click(function() {
        
        var valor;
        valor = $(this).attr('id');
        var res = valor.substring(4); // Extraer el codigo del subprograma
        var sumproc = 0;
        var sumprod = 0;
        var x = 0, y = 0, z = 0, w = 0;
        
        // Recorrer todos los meses
            
            if ($('#proc_mnc_'+res).val().length > 0){
                x = parseFloat($('#proc_mnc_'+res).val());    
            }else{
                x = 0;    
            }
            if ($('#proc_mc_'+res).val().length > 0) {
                y = parseFloat($('#proc_mc_'+res).val());
            }else{
                y = 0;
            }
            if ($('#prod_mnc_'+res).val().length > 0) {
                z = parseFloat($('#prod_mnc_'+res).val());
            }else{
                z = 0;
            }
            if ($('#prod_mc_'+res).val().length > 0){
                w = parseFloat($('#prod_mc_'+res).val());
            }else{
                w = 0;
            }
            
            sumproc = (x+y);
            sumprod = (z+w);
        
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