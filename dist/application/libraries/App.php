<?php
interface LarCityAppInterface {

    // declare a private $supportedContexts array
    public function getContext();
    public function testContexts();
    public function contextIs($keyInSupportedContexts);
    
}

/** A NOTE ON APP CONTEXTS
 * =======================
 * We all have to deal with environment variables. Contexts give us a way to "pre-set" what the criteria for an 
 * environment is, and have our app automatically switch it's configuration as it gets deployed to different 
 * environments, with a "Hail Mary" context if all else fails
 **/
class App implements LarCityAppInterface {

    var $CI;

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const HASH_TYPE = 'sha256';
    
    const CONTEXT_DEV = 'env.dev';
    const CONTEXT_PROD = 'env.prod';
    const CONTEXT_ROGUE = 'env.rogue';
    const CONTEXT_HAILMARY = 'env.testfailed_hailmary';

    // we will initialize this in the constructor
    private $contexts = [];
    // store the first positive context result string here 
    private $instanceContext = null;
    private $contextAudit = [];

    public function __construct() {
        $this->CI = & get_instance();
        $this->contexts = [
            self::CONTEXT_PROD => [
                'test' => (preg_match("/^localhost/", $_SERVER['HTTP_HOST']) !== 1)
            ],
            self::CONTEXT_DEV => [
                'test' => (preg_match("/^localhost/", $_SERVER['HTTP_HOST']) === 1 && is_dir('/Users/Shared/www'))
            ],
            self::CONTEXT_ROGUE => [
                'test' => (is_dir('/Applications/MAMP/htdocs/') and preg_match("/^localhost/", $_SERVER['HTTP_HOST']) === 1)
            ]
        ];
        $this->testContexts();
    }

    public function testContexts() {
        $this->contextAudit['host'] = $_SERVER['HTTP_HOST'];
        // clear results of last context test - if something is in there
        $this->instanceContext = null;
        // test for the app's current context
        foreach ($this->contexts as $contextKey => $possibleContext) {
            $passedTest = false;
            if ($possibleContext['test']) {
                $passedTest = true;
                $this->instanceContext = $contextKey;
            }
            $this->contextAudit[$contextKey] = [
                'key' => $contextKey,
                'test' => $possibleContext['test'],
                'passed' => $passedTest
            ];
            if($passedTest) {
                // No further tests needed ONCE you identify one. 
                // In the constructor initialization of your contexts, arrage the contexts in their order of 
                // ID priority (prod, then dev, then rogue - for example) so this works for you
                break;
            }
        }
        // If all the tests fail - Hail Mary!
        if(empty($this->instanceContext)) {
            $this->instanceContext = self::CONTEXT_HAILMARY;
        }
    }
    
    public function getLastContextAudit() {
        return $this->contextAudit;
    }

    public function getContext() {
        return $this->instanceContext;
    }

    // check what the current app run context is
    public function contextIs($keyInSupportedContexts, $retestContext = true) {
        if($retestContext) {
            $this->testContexts();
        }
        return strtolower($keyInSupportedContexts) === strtolower($this->instanceContext);
    }

    public static function inventory() {
        echo "Made it past the autoload!";
    }

    static function protocolToHTTP($url) {
        return preg_replace("/^https\:/", "http:", $url, 1);
    }

    static function protocolToHTTPS($url) {
        if (!server_mode('development')) {
            return preg_replace("/^http\:/", "https:", $url, 1);
        } else {
            return $url;
        }
    }

    static function mkpath() {
        $bits = array_map(function( $str ) {
            return rtrim($str, DIRECTORY_SEPARATOR);
        }, func_get_args());
        if (is_array($bits)) {
            return implode(DIRECTORY_SEPARATOR, $bits);
        }
    }

    function parsePutVars() {
        $_SERVER['REQUEST_METHOD'] === "PUT" ? parse_str(file_get_contents('php://input', false, null, -1, $_SERVER['CONTENT_LENGTH']), $_PUT) : $_PUT = array();
        global $_PUT;
    }

    static function requestMethod() {
        return strtoupper(trim($_SERVER['REQUEST_METHOD']));
    }

    static function requestIs($method) {
        // return strtolower($_SERVER['REQUEST_METHOD']) === strtolower(trim($method));
        return self::requestMethod() === $method;
    }

    static function requestIsPost() {
        //return $_SERVER['REQUEST_METHOD'] === BaseController::HTTPREQUEST_POST;
        return self::requestMethod() === BaseController::HTTPREQUEST_POST;
    }

    public function getAuthorizationHeader() {
        return $this->CI->input->get_request_header('Authorization', TRUE);
    }

    public function getAccessTokenFromAuthorizationHeader() {
        if ($this->oauthHeaderIsPresent()) {
            $authHeader = $this->getAuthorizationHeader();
            $matches = [];
            preg_match("/Bearer\s+(.*)/i", $authHeader, $matches);
            if (count($matches) > 1) {
                return $matches[1];
            }
        }
    }

    public function oauthHeaderIsPresent() {
        $auth = $this->CI->input->get_request_header('Authorization', TRUE);
        return !empty($auth);
    }

    static function toAscii($str, $replace = array(), $delimiter = '-') {
        setlocale(LC_ALL, 'en_US.UTF8');
        if (!empty($replace)) {
            $str = str_replace((array) $replace, ' ', $str);
        }
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        return $clean;
    }

    public function rgb2hex($rgb) {
        if (empty($rgb) || count($rgb) < 3) {
            $rgb = array(0, 0, 0);
        }
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }

    public function hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);
        //return implode(",", $rgb); // returns the rgb values separated by commas
        return $rgb; // returns an array with the rgb values
    }

    static function parseErrorsFromRules($rules) {
        $errors = [];
        foreach ($rules as $rule) {
            if (form_error($rule['field'])) {
                $errors[$rule['field']] = ['message' => form_error($rule['field'])];
            }
        }
        return $errors;
    }

    static function summarizeErrorsFromRules($rules, $limit = 1) {
        $errors = App::parseErrorsFromRules($rules);
        $summary = [];
        $at = 0;
        $suffix = '';
        foreach ($errors as $fieldName => $err) {
            $summary[] = $err['message'];
            if ($at >= $limit) {
                $suffix.=' and ' . count($errors) - $limit . ' more error(s) were reported';
                break;
            }
        }
        return implode(' // ', $summary) . $suffix;
    }

    public static function hash($string, $type = null, $salt = false) {
        if (empty($type)) {
            $type = self::HASH_TYPE;
        }
        $type = strtolower($type);

        if ($type === 'blowfish') {
            return self::_crypt($string, $salt);
        }
        if ($salt) {
            if (!is_string($salt)) {
                //$salt = self::SALT;
                $salt = config_item('encryption_key');
            }
            $string = $salt . $string;
        }

        if (!$type || $type === 'sha1') {
            if (function_exists('sha1')) {
                return sha1($string);
            }
            $type = 'sha256';
        }

        if ($type === 'sha256' && function_exists('mhash')) {
            return bin2hex(mhash(MHASH_SHA256, $string));
        }

        if (function_exists('hash')) {
            return hash($type, $string);
        }
        return md5($string);
    }

    protected static function _crypt($password, $salt = false) {
        if ($salt === false) {
            $salt = self::_salt(22);
            $salt = vsprintf('$2a$%02d$%s', array(self::$hashCost, $salt));
        }

        if ($salt === true || strpos($salt, '$2a$') !== 0 || strlen($salt) < 29) {
            trigger_error(__d(
                            'cake_dev', 'Invalid salt: %s for %s Please visit http://www.php.net/crypt and read the appropriate section for building %s salts.', array($salt, 'blowfish', 'blowfish')
                    ), E_USER_WARNING);
            return '';
        }
        return crypt($password, $salt);
    }

    public function log_query() {
        $sql = $this->CI->db->last_query();
        $this->CI->db->insert('_querylog', array('sql' => $sql, 'ip_address' => $this->CI->input->ip_address()));
        $this->queries[] = $sql;
    }

    public function query_log() {
        return $this->queries;
    }

    static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    static function encode($text) {
        $num = rand(0, 100);
        if ($num < 35) {
            return App::base64url_encode(sha1(App::SALT) . $text . md5(App::SALT));
        } else if ($num >= 35 and $num < 60) {
            return App::base64url_encode(md5(App::SALT) . $text . sha1(App::SALT));
        } else if ($num >= 60 and $num < 75) {
            return App::base64url_encode($text . md5(App::SALT) . md5(App::SALT));
        } else if ($num >= 75 and $num < 90) {
            return App::base64url_encode($text . sha1(App::SALT) . sha1(App::SALT));
        } else {
            return App::base64url_encode($text . sha1(App::SALT) . md5(App::SALT));
        }
    }

    static function decode($text) {
        return str_replace(md5(App::SALT), '', str_replace(sha1(App::SALT), '', App::base64url_decode($text)));
    }

    static function context() {
        
    }

}
