<?php

require_once FCPATH . 'application' . DS . 'third_party' . DS . 'bshaffer' . DS . 'oauth2-server-php' . DS . 'src' . DS . 'OAuth2' . DS . 'Autoloader.php';

# require_once "/Applications/MAMP/htdocs/assets/common/php/oauth2-server/src/OAuth2/Autoloader.php";
OAuth2\Autoloader::register();

/**
 * Description of LarcityOAuthPdo
 * @author uche
 */
class OAuth2LarcityPdo extends OAuth2\Storage\Pdo {
    /** oauth_users is a Read-Only table since it is being derived as a view from the `users` table * */

    /** Password algorithm for LarCity.com is needed to verify user credentials * */
    protected $CI;

    public function __construct($connection, $config = array()) {
        //parent::__construct($connection, $config);
        if (!$connection instanceof \PDO) {
            if (is_string($connection)) {
                $connection = array('dsn' => $connection);
            }
            if (!is_array($connection)) {
                throw new \InvalidArgumentException('First argument to OAuth2\Storage\Pdo must be an instance of PDO, a DSN string, or a configuration array');
            }
            if (!isset($connection['dsn'])) {
                throw new \InvalidArgumentException('configuration array must contain "dsn"');
            }
            // merge optional parameters
            $connection = array_merge(array(
                'username' => null,
                'password' => null,
                    ), $connection);
            $connection = new \PDO($connection['dsn'], $connection['username'], $connection['password']);
        }
        $this->db = $connection;

        // debugging
        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->config = array_merge(array(
            'client_table' => 'oauth_clients',
            'access_token_table' => 'oauth_access_tokens',
            'refresh_token_table' => 'oauth_refresh_tokens',
            'code_table' => 'oauth_authorization_codes',
            'user_table' => 'oauth_users',
            'user_view' => 'oauth_users_vw',
            'jwt_table' => 'oauth_jwt',
            'scope_table' => 'oauth_scopes',
            'public_key_table' => 'oauth_public_keys',
                ), $config);
        $this->CI = & get_instance();
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null) {
        // if it exists, update it.
        if (empty($client_secret)) {
            throw new Exception("Public clients can ONLY be edited manually by system admins", 400);
        }
        if ($this->getClientDetails($client_id)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_secret=:client_secret, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope, user_id=:user_id where client_id=:client_id', $this->config['client_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, client_secret, redirect_uri, grant_types, scope, user_id) VALUES (:client_id, :client_secret, :redirect_uri, :grant_types, :scope, :user_id)', $this->config['client_table']));
        }

        return $stmt->execute(compact('client_id', 'client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id'));
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
        /** @TODO if this client is public, set fetch and update the user_id of the current user * */
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $created_at = date('c');
        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET client_id=:client_id, expires=:expires, user_id=:user_id, scope=:scope where access_token=:access_token', $this->config['access_token_table']));
        } else {
            // update user Id if grant_type = 'password'
            if (!empty($this->CI->POST['username'])) {
                $db = $this->CI->load->database('default', TRUE);
                $db->where('username', $this->CI->POST['username']);
                $db->from('oauth_users_vw');
                $db->select('id,username,first_name,last_name');
                $db->limit(1);
                $q = $db->get();
                if ($q->num_rows() < 1) {
                    $message = "No valid user found. OAuth operation aborted";
                    header("HTTP/1.0 404 {$message}");
                    echo $message;
                    exit();
                }
                $account = $q->result()[0];
                $user_id = $account->id;
            }
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (access_token, client_id, expires, created_at, user_id, scope) VALUES (:access_token, :client_id, :expires, :created_at, :user_id, :scope)', $this->config['access_token_table']));
        }
        return $stmt->execute(compact('access_token', 'client_id', 'user_id', 'expires', 'created_at', 'scope'));
    }

    public function getAccessToken($access_token) {
        $token = parent::getAccessToken($access_token);
        /** Opportunity to customize access token fetch * */
        /*
          $stmt = $this->db->prepare(sprintf('SELECT * from %s where access_token = :access_token', $this->config['access_token_table']));
          $token = $stmt->execute(compact('access_token'));
          if ($token = $stmt->fetch()) {
          // convert date string back to timestamp
          $token['expires'] = strtotime($token['expires']);
          }
         */
        return $token;
    }

    public function setUser($username, $password, $firstName = null, $lastName = null) {
        throw new Exception('User is read-only via the OAuth API. To create a new user, use the signup link on our home page', 400);
        /*
          // do not store in plaintext
          // $password = sha1($password);
          $password = $this->CI->app->hash($password, null, true);
          // if it exists, update it.
          if ($this->getUser($username)) {
          $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, first_name=:firstName, last_name=:lastName where username=:username', $this->config['user_table']));
          } else {
          $stmt = $this->db->prepare(sprintf('INSERT INTO %s (username, password, first_name, last_name) VALUES (:username, :password, :firstName, :lastName)', $this->config['user_table']));
          }
          return $stmt->execute(compact('username', 'password', 'firstName', 'lastName'));
         */
    }

    protected function checkPassword($user, $password) {
        $password_string = App::hash($password);
        return $user['password'] === $password_string;
    }

    public function getUser($username) {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where username=:username', $this->config['user_view']));
        $stmt->execute(array('username' => $username));
        if (!$userInfo = $stmt->fetch()) {
            return false;
        }
        // the default behavior is to use "username" as the user_id
        return array_merge(array(
            'user_id' => $username
                ), $userInfo);
    }

}
