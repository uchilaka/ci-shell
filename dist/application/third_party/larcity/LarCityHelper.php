<?php
namespace LarCity\CodeIgniter\Shell;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'helpers.php';

class LarCityHelper {
    
    static function mkpath() {
        return call_user_func_array('makepath', func_get_args());
    }
    
}
