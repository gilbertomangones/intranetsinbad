Drupal.behaviors.sinbad = {
    attach: function (context, settings) {
        var url = window.location.href;
        console.log('url:' + url);
        $('button.navbar-toggler').click(function() {
            if (!$('#superfish-main-accordion').hasClass('sf-expanded')) {
              $('#superfish-main-toggle').click().hide();
            }
          });
       /* 
        document.getElementById("block-logosinbad").addEventListener("click", open_close_menu);
        var side_menu = document.getElementById("menu_side");
        var btn_open = document.getElementById("block-logosinbad");
        var body_content = document.getElementById("body_content");
        
        jQuery('h2 a').hide();
        jQuery('#footer_large').hide();
        function open_close_menu(){
            body_content.classList.toggle("body_move");
            side_menu.classList.toggle("menu__side_move");
            
            $( "#block-accordionmainnavigation .ui-accordion .ui-accordion-content" ).addClass( "closemenu" );
            //jQuery('h2 a').show(2000);
            //jQuery(".order-2").width(64);
            //jQuery('#footer_large').show(2000);
        }
        jQuery("accordion_menus_block").click(function(){
            //jQuery(".order-2").width(250);
            jQuery(".order-2").animate({ width: 250 }, 'swing');
            jQuery('h2 a').show();
        });
        jQuery("#ui-id-1, #ui-id-5, #ui-id-3, #ui-id-7, #ui-id-9, #ui-id-11").click(function(params) {
            
            jQuery(".order-2").width(250);
            jQuery(".order-2").animate({ width: 250 }, 'swing');
            jQuery('h2 a').show();
            jQuery('.logo_header_small').hide();
            jQuery('#footer_large').show();
            jQuery('#footer_small').hide();
            jQuery('.logo-header').show();
            //jQuery('h2 a').toogle();
        })
        jQuery(".boton-cerrar").click(function(){
            jQuery(".order-2").width(80);
            jQuery('h2 a').hide();
            jQuery(".order-2").animate({ width: 80 }, 'swing');
            jQuery('.logo_header_small').show();
            jQuery('.logo-header').hide();
            jQuery('#footer_large').hide();
            jQuery('#footer_small').show();
        })
        */
    }
  };