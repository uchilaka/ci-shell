<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ModelQueryException extends Exception {
} 

class BaselineModel extends CI_Model {
    
    const USERROLE_ADMIN = 'admin';
    const USERROLE_MGR = 'manager';

    protected $insertedId;
    protected $account;
    var $CI;
    
    public function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
    }
    
    function getInsertedId() {
        return $this->insertedId;
    }
    
    function setUser( $userId ) {
        $this->account = $this->CI->User->getById($userId);
        if(empty($this->account)) {
            throw new ModelQueryException("No user account found for ID {$userId}", 404);
        }
    }
    
    protected function userHasRole($role_key) {
        $this->requireLogin();
        $roles = explode('|', $this->account->scopes);
        return in_array($role_key, $roles);
    }
    
    protected function getLoggedInUser() {
        if (empty($this->account)) {
            // try to get user account from oauth
            $this->account = $this->CI->oauth2lib->getUser();
        }        
        return $this->account;
    } 
    
    protected function requireLogin() {
        if(empty($this->account)) {
            $this->getLoggedInUser();
        }
        // If account is still empty - throw auth required exception
        if(empty($this->account)) {
            throw new Exception("Login is required", 401);
        }
    }
    
    protected function genToken($num=12) {
        return strtoupper(random_string('alnum', $num));
    }
    
}
