<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// Autoload libraries
require_once makepath(dirname(__DIR__), 'third_party', 'autoload.php');

use LarCity\CodeIgniter\Shell;

class BaseController extends CI_Controller {

    const HTTPREQUEST_GET = 'GET';
    const HTTPREQUEST_POST = 'POST';
    const HTTPREQUEST_DEL = 'DELETE';
    const HTTPREQUEST_PUT = 'PUT';
    const HTTPREQUEST_PATCH = 'PATCH';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';

    var $nav_view;
    var $footer_view;
    var $overlay_view;
    var $help_view;
    var $account;
    var $auth0RawToken;
    var $auth0DecodedToken;
    var $auth0ClientId;
    var $auth0Secret;
    var $viewParameters;
    
    var $GET = [];
    var $POST = [];
    var $PUT = [];

    public function __construct() {
        parent::__construct();
        $this->DATA = (object) ['status' => 404, 'status_msg' => 'Resource not found'];
        $this->GET = $this->input->get(null, true);
        $this->POST = $this->input->post(null, true);
        $this->nav_view = $this->load->view('nav', null, true);
        $this->overlay_view = $this->load->view('overlay', null, true);
        $this->help_view = $this->load->view('help', null, true);
        $this->viewParameters = [
            'nav_view' => $this->nav_view,
            'overlay_view' => $this->overlay_view,
            'help_view' => $this->help_view,
            'footer_view' => $this->load->view('footer', null, true)
        ];
        if($this->isSecureAccess()) {
            $this->requireLogin();
        }
    }
    
    public function isSecureAccess() {
        $segments = $this->uri->segment_array();
        return in_array('secure', $segments);
    }

    public function requireLogin() {
        // check for ID token
        if (!empty($this->GET['id_token'])) {
            //$this->auth0RawToken = $this->app->getAccessTokenFromAuthorizationHeader();
            $this->auth0RawToken = $this->GET['id_token'];
            $this->auth0Secret = config_item('auth0')['secret'];
            $this->auth0ClientId = config_item('auth0')['client_id'];
            try {
                $this->auth0DecodedToken = \Auth0\SDK\Auth0JWT::decode($this->auth0RawToken, $this->auth0ClientId, $this->auth0Secret);
            } catch (Exception $ex) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Invalid token";
                exit();
            }
        } else {
            // use OAuth2 against LarCity 
            $this->load->library('oauth2lib');
            $this->oauth2lib->handleRequest(Oauth2lib::REQUESTTYPE_RESOURCE);
        }
    }

    public function getAuth0LoggedInUser() {
        $this->requireLogin();
        $URL = config_item('auth0')['api_uri'] . "tokeninfo";
        //$resp = $this->curl->exec($URL, 'GET', [ 'id_token' => $this->auth0RawToken ], []);
        $resp = $this->curl->exec($URL, 'GET', [ 'id_token' => $this->auth0RawToken ], []);
        if (empty($resp)) {
            throw new Exception('No profile data returned', 404);
        }
        return json_decode($resp);
    }

    public function requireLibrary($libName, $libData = NULL, $libAlias = null) {
        $libNickName = empty($libAlias) ? $libName : $libAlias;
        if (empty($this->$libNickName)) {
            $this->load->library($libName, $libData, $libNickName);
        }
    }

    public function safeRequestVariable($key, $method = self::HTTPREQUEST_GET, $default = null) {
        switch ($method) {
            case self::HTTPREQUEST_POST:
                return empty($this->POST[$key]) ? $default : $this->POST[$key];

            default:
                return empty($this->GET[$key]) ? $default : $this->GET[$key];
        }
    }

    function set($key, $value = null) {
        $this->DATA->$key = $value;
    }

    function set_all($json) {
        $this->DATA = $json;
    }

    function json($key) {
        if (!empty($this->DATA->$key))
            return $this->DATA->$key;
        else
            return false;
    }

    function renderJson($data, $headers = null, $CORS = false) {
        if (!empty($headers) and is_array($headers)) {
            foreach ($headers as $header) {
                $this->output->set_header($header);
            }
        }
        if ($CORS and defined('IN_GCLOUD') and IN_GCLOUD) {
            $this->output->set_header("Access-Control-Allow-Origin: *");
        }
        $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode($data));
    }

    function respond() {
        $format = empty($this->GET['format']) ? self::FORMAT_JSON : $this->GET['format'];
        switch ($format) {
            case self::FORMAT_JSON:
                $this->renderJson($this->DATA, ["HTTP/1.1 {$this->json('status')} {$this->json('status_msg')}"]);
                break;

            case self::FORMAT_XML:
                $data_array = json_decode(json_encode($this->DATA), TRUE);
                header("Content-Type: text/xml");
                $xmlData = Array2XML::createXML('Data', $data_array);
                echo $xmlData->saveXML();
                break;
        }
    }

    function not_found() {
        $this->respond();
    }

}
