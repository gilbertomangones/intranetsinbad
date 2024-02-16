(function ($) {
 $.fn.planilla = function(data) {
 	console.log(data);
   
 	let message = '';
 	$(".alternativo").removeClass("verde rojo");
 	if (data['planilla'] != ""){
    	message = "Planilla en 0";
    	if (data['total'] != 0){
        	message = "Consulta exitosa.";
        	$(".alternativo").addClass("verde");
        	// Llenar campos con valores de parámetro
 			$("#edit-field-numero-asistentes-0-value").val(data['total']);
    		$("#edit-field-participantes-de-genero-fe-0-value").val(data['mujeres']);
    		$("#edit-field-participantes-de-genero-ma-0-value").val(data['hombres']);
 			$("#edit-field-participantes-de-genero-tr-0-value").val(data['intersexuales']);
 			$("#edit-field-no-reporta-sexo-0-value").val(data['no_reporta_sexo']);
 			$("#edit-field-numero-asistentes-0-5-0-value").val(data['asistentes_0_5']);
 			$("#edit-field-numero-asistentes-6-12-0-value").val(data['asistentes_6_12']);
 			$("#edit-field-numero-asistentes-13-18-0-value").val(data['asistentes_13_18']);
 			$("#edit-field-numero-asistentes-19-27-0-value").val(data['asistentes_19_27']);
 			$("#edit-field-numero-asistentes-28-60-0-value").val(data['asistentes_28_60']);
 			$("#edit-field-numero-asistentes-61-mas-0-value").val(data['asistentes_61']);
 
 			$("#edit-field-cisgenero-0-value").val(data['Cisgenero']);
 			$("#edit-field-transgenero-0-value").val(data['Transgenero']);
 			$("#edit-field-otro-ident-genero-0-value").val(data['Otro_identidad_genero']);
 			$("#edit-field-no-reporta-genero-0-value").val(data['No_reporta_genero']);
 
 			$("#edit-field-heterosexual-0-value").val(data['Heterosexuales']);
 			$("#edit-field-homosexual-0-value").val(data['Homosexuales']);
 			$("#edit-field-bisexual-0-value").val(data['Bisexuales']);
 			$("#edit-field-no-reporta-orient-sexual-0-value").val(data['No_reporta_orientacion']);
 
 			$("#edit-field-p-discap-fisica-0-value").val(data['Fisica']);
 			$("#edit-field-p-discap-multiple-0-value").val(data['Multiple']);
 			$("#edit-field-p-discap-auditiva-0-value").val(data['Auditiva']);
 			$("#edit-field-p-discap-visual-0-value").val(data['Visual']);
 			$("#edit-field-p-discap-psicosocial-0-value").val(data['Psicosocial']);
 			$("#edit-field-p-discap-cognitiva-0-value").val(data['Cognitiva']);
 			$("#edit-field-p-discap-sordo-ceguera-0-value").val(data['Sordo-ceguera']);
 			$("#edit-field-ninguna-discapacidad-0-value").val(data['Ninguna_discapacidad']);
 			$("#edit-field-no-reporta-discapacidad-0-value").val(data['No_reporta_discapacidad']);
 
 			$("#edit-field-campesino-poblacion-rural-0-value").val(data['Campesinos']);
 			$("#edit-field-persona-victima-conflicto-0-value").val(data['Victimas_conflicto']);
 			$("#edit-field-lgbtiq-0-value").val(data['LGBTIQ']);
 			$("#edit-field-persona-activ-sexual-pagad-0-value").val(data['Actividades_sexuales']);
 			$("#edit-field-persona-privada-libertad-0-value").val(data['Privadas_libertad']);
 			$("#edit-field-persona-migrante-refugia-0-value").val(data['Migrantes']);
 			$("#edit-field-artesano-0-value").val(data['Artesanos']);
 			$("#edit-field-firmante-paz-0-value").val(data['Firmantes']);
 			$("#edit-field-persona-habitante-calle-0-value").val(data['Habitantes_calle']);
 			$("#edit-field-afrodescendiente-afrocolom-0-value").val(data['Afros']);
 			$("#edit-field-negro-a-0-value").val(data['Comunidad_Negras']);
 			$("#edit-field-palenquero-a-0-value").val(data['Palenqueros']);
 			$("#edit-field-raizal-0-value").val(data['Raizales']);
 			$("#edit-field-rrom-o-gitano-a-0-value").val(data['Grom']);
 			$("#edit-field-indigena-0-value").val(data['Indigenas']);
 			$("#edit-field-persona-cuidadora-0-value").val(data['Cuidadoras']);
 			$("#edit-field-ninguna-poblacion-0-value").val(data['Ninguna_poblacion']);
 			$("#edit-field-no-reporta-poblacion-0-value").val(data['No_reporta_poblacion']);
 			$(".alternativo").text( message );
        
 			//var enlace = '<a href="https://intranet.biblored.net/planilla/planilla.php?identificador="+ data["planilla"] +"&tipo_identificador=id_planilla">Planialla Digital</a>;
 			$("#edit-field-enlace-planilla-digital-0-value").html('<a href="https://intranet.biblored.net/planilla/planilla.php?identificador='+ data["planilla"] +'&tipo_identificador=id_planilla" target="_blank">Enlace Planilla digital</a>');
        }else{
        	message = "Planilla existe, pero no fue diligenciada.";
        	$(".alternativo").addClass("rojo");
        	$(".alternativo").text( message );
        }
    }else{
    	message = "Planilla no existe."
    	$(".alternativo").addClass("rojo");
    	$(".alternativo").text( message );
    }
 	
 	
 };
})(jQuery);