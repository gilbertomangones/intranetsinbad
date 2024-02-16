<?php
phpinfo();

   header ("Content-type: image/png");
   $imagen = imagecreate (300,150); //argumentos pixeles ancho y alto
   $color_fondo = imagecolorallocate ($imagen, 0, 255, 0); //Rgb color verde
   imagepng ($imagen); //presentación de la imagen en el navegador

?>