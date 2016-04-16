<?php

require_once makepath(__DIR__, 'BaselineModel.php');

class User extends BaselineModel {

    private $plainPassword;
    var $CI;
    var $fields = ['id', 'first_name', 'last_name', 'email', 'scopes'];

    public function __construct() {
        parent::__construct();
        $this->CI = & get_instance();
    }

    function getPlainPassword() {
        return $this->plainPassword;
    }

    function parse(&$account) {
        unset($account->password);
    }

    function getByUsername($username) {
        $db = $this->load->database('default', TRUE);
        $q = $db->get_where('users', ['username' => $username], 1);
        if ($q->num_rows() > 0) {
            $account = $q->result()[0];
            $this->parse($account);
            return $account;
        }
    }

    function getById($userId) {
        $db = $this->load->database('default', TRUE);
        $q = $db->get_where('users', ['id' => $userId], 1);
        if ($q->num_rows() > 0) {
            $account = $q->result()[0];
            $this->parse($account);
            return $account;
        }
    }

    function getByEmail($email) {
        $db = $this->load->database('default', TRUE);
        $q = $db->get_where('users', ['email' => $email], 1);
        if ($q->num_rows() > 0) {
            $account = $q->result()[0];
            $this->parse($account);
            return $account;
        }
    }

    public function convertToArray($account) {
        $array = [];
        foreach ($this->fields as $f) {
            $array[$f] = $account->$f;
        }
        return $array;
    }

    function singleSignOn($attributes) {
        if (empty($attributes['email']) and empty($attributes['username'])) {
            throw new Exception("email or username must be available for Single Sign On", 400);
        }
        $db = $this->load->database('default', TRUE);
        if (!empty($attributes['email'])) {
            // login via email
            $query = $db->get_where('users', ['email' => $attributes['email']], 1);
        } else {
            // login via username
            $query = $db->get_where('users', ['username' => $attributes['username']], 1);
        }
        if ($query->num_rows() < 1) {
            throw new Exception("No account found", 404);
        }
        // account found
        $account = $query->result()[0];
        $this->parse($account);
        // login user
        $sess_account = [];
        foreach ($this->fields as $f) {
            $sess_account[$f] = $account->$f;
        }
        $this->CI->session->set_userdata($sess_account);
        return $account;
    }

    function initialize($socialProfile, $scopes = 'user') {
        $saveSocialProfile = [];
        $attributes = [];
        $userInfo = [];
        $profileUserId = explode('|', $socialProfile->user_id)[1];
        if (preg_match("/^facebook/", $socialProfile->user_id)) {
            // process from facebook
            $saveSocialProfile = [
                'domain_name' => 'facebook.com',
                'profile_id' => $profileUserId,
                'profile_url' => $socialProfile->link
            ];
            $attributes = [
                'photo_url' => empty($socialProfile->picture) ? null : $socialProfile->picture,
                'gender' => empty($socialProfile->gender) ? null : $socialProfile->gender
            ];
        } elseif (preg_match("/^google/", $socialProfile->user_id)) {
            // process from google
            $saveSocialProfile = [
                'domain_name' => 'google.com',
                'profile_id' => $profileUserId,
                    //'profile_url' => $socialProfile->link
            ];
            $attributes = [
                'photo_url' => empty($socialProfile->picture) ? null : $socialProfile->picture,
                'gender' => empty($socialProfile->gender) ? null : $socialProfile->gender
            ];
        }
        if (empty($saveSocialProfile)) {
            throw new Exception("No supported social profile found", 400);
        }
        $this->CI->set('postfields', $this->CI->POST);
        foreach ($this->fields as $f) {
            if (!empty($this->CI->POST[$f])) {
                $userInfo[$f] = $this->CI->POST[$f];
            }
        }
        $db = $this->load->database('default', TRUE);
        $account = $this->getByEmail($socialProfile->email);
        if (empty($account)) {
            // new user
            $userInfo['username'] = empty($userInfo['username']) ? $userInfo['email'] : $userInfo['username'];
            $userInfo['password'] = App::hash(random_string('alnum', 8)); // placeholder password
            $userInfo['scopes'] = $scopes;
            $db->insert('users', $userInfo);
            $userId = $db->insert_id();
            if (empty($userId)) {
                throw new Exception("Create account failed - no user_id was returned", 400);
            }
            $attributes['user_id'] = $userId;
            $saveSocialProfile['user_id'] = $userId;
            $db->insert('user_attributes', $attributes);
            $db->insert('user_social_profiles', $saveSocialProfile);
        } else {
            // user exists - do updates
            $userInfo['email'] = $account->email; // POST->email may be empty
            $match = ['user_id' => $account->id];
            // update user attributes
            $db->where($match);
            $db->from('user_attributes');
            if ($db->count_all_results() > 0) {
                $db->update('user_attributes', $attributes, $match);
            } else {
                $attributes['user_id'] = $account->id;
                $db->insert('user_attributes', $attributes);
            }
            // update social media profile
            $match['domain_name'] = $saveSocialProfile['domain_name'];
            $db->where($match);
            $db->from('user_social_profiles');
            if ($db->count_all_results() > 0) {
                $db->update('user_social_profiles', $saveSocialProfile);
            } else {
                $saveSocialProfile['user_id'] = $account->id;
                $db->insert('user_social_profiles', $saveSocialProfile);
            }
        }
        return $this->singleSignOn($userInfo);
    }

    function save($id = null) {
        $packet = []; $this->fields = array_merge($this->fields, ['username']);
        foreach ($this->fields as $f) {
            if (!empty($this->CI->POST[$f])) {
                $packet[$f] = $this->CI->POST[$f];
            }
        }
        // remove scope - must be managed through different method
        if (!empty($packet['scopes'])) {
            unset($packet['scopes']);
        }
        $db = $this->load->database('default', true);
        if (empty($id)) {
            $packet['scopes'] = 'user';
            $this->plainPassword = random_string('alnum', 6);
            $packet['password'] = App::hash($this->plainPassword);
            $db->insert('users', $packet);
            $this->insertedId = $db->insert_id();
        } else {
            $db->limit(1)
                    ->where('id', $id)
                    ->update('users', $packet);
        }
    }

}
