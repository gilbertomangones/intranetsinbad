(function ($) {
var controlvisto = true;

//console.log("Llamado superior.");
  Drupal.behaviors.funcionesBehavior = {
    attach: function (context, settings) {

            var meta = 0;
            var interno = 0;
            var externo = 0;
            var prodinterno = 0;
            var prodexterno = 0;
 
            $('#node-actividad-ejecutada-edit-form').on( "submit", function( e ) {
            	let mostrarmsg = false;
            	let mensaje = "";
            	let asistentes = 0;
    			let sumsexo = 0;
     			
            	e.stopImmediatePropagation();
            	//e.preventDefault();
            	asistentes = parseInt($('#edit-field-numero-asistentes-0-value').val());
				var genero_fe = parseInt($('#edit-field-participantes-de-genero-fe-0-value').val());
            	var genero_ma = parseInt($('#edit-field-participantes-de-genero-ma-0-value').val());
            	var genero_tr = parseInt($('#edit-field-participantes-de-genero-tr-0-value').val());
            	var genero_nr = parseInt($('#edit-field-no-reporta-sexo-0-value').val());
   				sumsexo = genero_fe + genero_ma + genero_tr + genero_nr;
    			if (sumsexo != asistentes)	{
                 	mensaje += "El número de asistentes no coincide con la sumatoria por sexo.\n";
                	mostrarmsg = true;
                	$('#edit-group-participantes-por-genero').addClass('errorBackColor');
                 }
            	else
                {
                	$('#edit-group-participantes-por-genero').removeClass('errorBackColor');
                }
            	var cero_cinco = parseInt($('#edit-field-numero-asistentes-0-5-0-value').val());
            	var seis_doce = parseInt($('#edit-field-numero-asistentes-6-12-0-value').val());
            	var trece_18 = parseInt($('#edit-field-numero-asistentes-13-18-0-value').val());
            	var diecinueve_27 = parseInt($('#edit-field-numero-asistentes-19-27-0-value').val());
            	var veinte_28 = parseInt($('#edit-field-numero-asistentes-28-60-0-value').val());
            	var sesenta_1 = parseInt($('#edit-field-numero-asistentes-61-mas-0-value').val());
            	var noreportaedad = parseInt($('#edit-field-no-reporta-edad-0-value').val());
            	var sumaedad = cero_cinco + seis_doce + trece_18 + diecinueve_27 + veinte_28 + sesenta_1 + noreportaedad;
            	if (asistentes != sumaedad) {
                	mensaje += "El número de asistentes no coincide con la sumatoria de asistentes por edad.\n";
                	mostrarmsg = true;
                	$('#edit-group-numero-de-asistentes-por-e').addClass('errorBackColor');
                
                }
            	else
                {
                	$('#edit-group-numero-de-asistentes-por-e').removeClass('errorBackColor');
                }
            	var cisgenero = parseInt($('#edit-field-cisgenero-0-value').val());
            	var transgenero = parseInt($('#edit-field-transgenero-0-value').val());
            	var otro_genero = parseInt($('#edit-field-otro-ident-genero-0-value').val());
            	var noreporta_genero = parseInt($('#edit-field-no-reporta-genero-0-value').val());
            	var suma_genero = cisgenero + transgenero + otro_genero + noreporta_genero;
            	//console.log("Genero: "+suma_genero);
            	if (asistentes != suma_genero){
            		mensaje += "El número de asistentes no coincide con la sumatoria de asistentes por género.\n";
                	mostrarmsg = true;
                	$('#edit-group-identidad-de-genero').addClass('errorBackColor');
            	}
            	else
                {
                	$('#edit-group-identidad-de-genero').removeClass('errorBackColor');
                }
            	var heterosexual = parseInt($('#edit-field-heterosexual-0-value').val());
            	var homosexual = parseInt($('#edit-field-homosexual-0-value').val());
            	let bisexual = parseInt($('#edit-field-bisexual-0-value').val());
            	let noreporta_orientacion = parseInt($('#edit-field-no-reporta-orient-sexual-0-value').val());
            	let suma_orientacion = heterosexual + homosexual + bisexual + noreporta_orientacion;
            	//console.log("Orientación: "+suma_genero);
            	if (asistentes != suma_orientacion) {
            		mensaje += "El número de asistentes no coincide con la sumatoria de asistentes por orientación Sexual.\n";
                	mostrarmsg = true;
                	$('#edit-group-orientacion-sexual').addClass('errorBackColor');
            	}
            	else
                {
                	$('#edit-group-orientacion-sexual').removeClass('errorBackColor');
                }
            	if (mostrarmsg == true /*&& controlvisto == true*/){
            		alert(mensaje);
                	controlvisto = false;
                	
                }
            	if(mostrarmsg == true)
                {
                	return false;
                }
			});
            jQuery('#edit-field-proc-externo-0-value').keyup(function(){
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
    		
    }
  }

})(jQuery);