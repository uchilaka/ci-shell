<?php

class Curl {
    
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    public function exec($uri, $method="GET", $params=null, $headers=array()) {
        if(!is_array($params) && $params):
            throw new Exception("Parameters must be null or an array");
        endif;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        // set response code
        
        if(!empty($headers) && !is_array($headers)):
            throw new Exception("Headers must be null or an array");
        endif;
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $query_string=  null;
        if($params && is_array($params)):
            $query_string = http_build_query($params);
        endif;
        
        switch(strtolower($method)):
            case "post":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                break;
            
            default:
                $uri_parts = explode("?", $uri);
                if(count($uri_parts)>1) {
                    $queries = $uri_parts[1];
                    $queries .= "&" . $query_string;
                    $uri = $uri_parts[0] . "?" . $queries;
                } else {
                    $uri .= "?" . $query_string;
                }
                curl_setopt($ch, CURLOPT_URL, $uri);
                break;
        endswitch;
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
}
