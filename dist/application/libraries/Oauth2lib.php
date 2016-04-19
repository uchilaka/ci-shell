<?php
use LarCity\CodeIgniter\Shell;

class Oauth2lib {
    
    const REQUESTTYPE_RESOURCE = 'resource';
    const REQUESTTYPE_ACCESS_TOKEN = 'access_token';
    private $CI;
    // oauth server
    var $server;
    // oauth data store
    private $store;
    
    public function __construct() {
        $this->CI =& get_instance();
        $authdb = $this->CI->load->database('default', TRUE);
        // cleanup
        $authdb->query("delete from oauth_access_tokens where DATEDIFF(STR_TO_DATE(expires, '%Y-%m-%d %H:%i:%s'), CURDATE()) < -30;");
        $oauth_config = array(
            'dsn'=>"mysql:dbname={$authdb->database};host={$authdb->hostname};port={$authdb->port}",
            'username'=>$authdb->username,
            'password'=>$authdb->password
        );
        // $this->store = new OAuth2\Storage\Pdo($oauth_config);
        $this->store = new Shell\Auth\OAuth2LarcityPdo($oauth_config);
        // setup oauth server
        $token_lifetime=60 * 60 * 24 * 30;
        $this->server = new OAuth2\Server($this->store, [ 'access_lifetime'=> $token_lifetime, 'refresh_token_lifetime'=>$token_lifetime * 2 ]);
        // add grant type (simplest grant type)
        $this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->store));
        // add the "Authorization Code" grant type - where the "MAGIC" happens
        $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->store));
        $this->server->addGrantType(new OAuth2\GrantType\UserCredentials($this->store));
        $this->server->addGrantType(new OAuth2\GrantType\RefreshToken($this->store, [
            'always_issue_new_refresh_token'=>true
        ]));
        /** @TODO enforce scopes **/
        $defaultScope = 'basic';
        $supportedScopes = array(
            'basic',
            'cloudstorage',
            'email',
            'admin'
        );
        $memory = new OAuth2\Storage\Memory(array(
            'default_scope'=>$defaultScope,
            'supported_scopes'=>$supportedScopes
        ));
        $scopeUtil = new OAuth2\Scope($memory);
        $this->server->setScopeUtil($scopeUtil);
    }
    
    public function getServer() {
        return $this->server;
    }
    
    public function getCredentials() {
        if(!$this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
            $this->server->getResponse()->send();
            die;
        }
        $response = $this->server->getResponse();
        return $this->server->getAccessTokenData(OAuth2\Request::createFromGlobals(), $response);
    }
    
    /** @TODO depracate and use getUserById instead for implementations going forward **/
    public function getUser() {
        $credentials = $this->getCredentials();
        $this->CI->set('credentials', $credentials);
        if(!empty($credentials['user_id'])) {
            $account = $this->CI->User->getByUsername($credentials['user_id']);
            return $account;
        }
    }
    
    public function getUserById() {
        $credentials = $this->getCredentials();
        // $this->CI->set('credentials', $credentials);
        if(!empty($credentials['user_id'])) {
            $account = $this->CI->User->getById($credentials['user_id']);
            if(!empty($account)) {
                $account->credentials = $credentials;
            }
            return $account;
        }
    }
    
    public function userHasScope( $scope ) {
        $scopeUtil = $this->server->getScopeUtil();
        $response = $this->server->getResponse();
        $accessToken = $this->server->getAccessTokenData(OAuth2\Request::createFromGlobals(), $response);
        return $scopeUtil->checkScope($scope, $accessToken['scope']);
    }
    
    public function handleRequest( $request_type, $scope=null ) {
        switch($request_type) {
            case self::REQUESTTYPE_RESOURCE:
                if(!$this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
                    $this->server->getResponse()->send();
                    die;
                }
                /** Check scopes **/
                if(!empty($scope)) {
                    $scopeUtil = $this->server->getScopeUtil();
                    $response = $this->server->getResponse();
                    $accessToken = $this->server->getAccessTokenData(OAuth2\Request::createFromGlobals(), $response);
                    if(!$scopeUtil->checkScope($scope, $accessToken['scope'])) {
                        $response = new OAuth2\Response(['message'=>'Your access token does not bear the required scope.'], 401);
                        $response->send();
                        die();
                    }
                }
                break;
            
            default:
                /** @TODO make sure scope is implemented against restricted resources **/
                // Delete tokens that are expired
                $this->server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
        }
    }

}
