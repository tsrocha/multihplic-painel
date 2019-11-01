<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Help extends Model
{
    public static function slug($name){
        $primary = array(".", "'", ',', '´', '%', '/', '”', "'",'-','(', ')', '!', '?', ' ','à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','O','Ù','Ü','Ú','Ÿ',);
        $secundary = array('-', '','-','', '','', '', "",'','', '','', '', '-','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','o','U','U','U','Y',);
        $result = str_replace($primary, $secundary, $name);
        return strtolower($result);
    }
}
