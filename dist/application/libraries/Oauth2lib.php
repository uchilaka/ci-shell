<?php
use LarCity\CodeIgniter\Shell;

require_once App::mkpath(dirname(__DIR__), 'third_party', 'larcity', 'auth', 'LarCityOAuth2Pdo.php');

class Oauth2lib {
    
    const REQUESTTYPE_RESOURCE = 'resource';
    const REQUESTTYPE_ACCESS_TOKEN = 'access_token';
    protected $CI;
    // oauth data store
    protected $store;
    // database group
    protected $db_group;
    // oauth server
    var $server;
    
    public function __construct( $config=[ 'db_group' => 'default' ] ) {
        $this->CI =& get_instance();
        // set $db group from config
        $this->db_group = $config['db_group'];
        // set authentication group in controller
        $this->CI->set('db_group', $this->db_group);
        // load authentication database
        $authdb = $this->CI->load->database($this->db_group, TRUE);
        // cleanup
        $authdb->query("delete from oauth_access_tokens where DATEDIFF(STR_TO_DATE(expires, '%Y-%m-%d %H:%i:%s'), CURDATE()) < -30;");
        $oauth_config = array(
            'dsn'=>"mysql:dbname={$authdb->database};host={$authdb->hostname};port={$authdb->port}",
            'username'=>$authdb->username,
            'password'=>$authdb->password
        );
        // $this->store = new OAuth2\Storage\Pdo($oauth_config);
        $this->store = new LarCityOAuth2Pdo($oauth_config);
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
    
    public function setDatabaseGroup($groupIndexInConfigFile) {
        $this->db_group = $groupIndexInConfigFile;
    }
    
    public function getLifeEndTime() {
        $date = new DateTime();
        $date->add(new DateInterval('P30D')); // 30 days
        return $date->format("Y-m-d H:i:s");
    }

    public static function getValidScopes() {
        return explode(',', self::validScopes);
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
