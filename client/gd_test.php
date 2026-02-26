<?php
echo "GD loaded: "; var_dump(extension_loaded('gd'));
echo "<br>imagecreatetruecolor: "; var_dump(function_exists('imagecreatetruecolor'));