<?php

/**
 * @notes
 * For customization, read Auth0's documentation for php: https://auth0.com/docs/quickstart/backend/php/
 * 
 * @requires uchilaka/ci-shell/dist/application/controllers/BaseController.php 
 * */

namespace UChilaka\CodeIgniter;

class Auth0client {

    private $decodedToken;
    protected $CI;
    protected $config;
    protected $userProfile;

    public function __construct() {
        $this->CI = & get_instance();
        // load configuration from file
        $this->config = config_item('auth0');
        // This assumes you are utilizing the BaseController.php class
        if ($this->CI->isSecureAccess()) {
            $this->requireLogin();
        }
    }

    public function requireLogin() {
        // authenticate
        $authorizationHeader = $this->CI->input->get_request_header('Authorization', TRUE);

        if ($authorizationHeader == null) {
            header('HTTP/1.0 401 Unauthorized');
            echo "No authorization header sent";
            exit();
        }

        // // validate the token
        $token = str_replace('Bearer ', '', $authorizationHeader);
        // refer to /dist/mirror/application/config/auth0.example.php for what you need in your auth0.php config file 
        try {
            $this->decodedToken = \Auth0\SDK\Auth0JWT::decode($token, $this->config->client_Id, $this->config->secret);
        } catch (\Auth0\SDK\Exception\CoreException $e) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Invalid token";
            exit();
        }
        // authenticated successfully! - get profile
        $auth0Profile = $this->getProfile();
        
        // please reference uchilaka/ci-shell/dist/application/controllers/BaseController.php for info on the set, get and other helper controller methods
        $this->CI->set('Profile', $auth0Profile);
    }

    public function getProfile() {
        $this->requireLogin();
        $URL = "https://{$this->config['domain']}/tokeninfo";
        $resp = $this->curl->exec($URL, 'GET', [ 'id_token' => $this->auth0RawToken], []);
        if (empty($resp)) {
            throw new Exception('No profile data returned', 404);
        }
        return json_decode($resp);
    }

}
