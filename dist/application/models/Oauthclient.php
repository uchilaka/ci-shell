<?php
require_once makepath(__DIR__, 'BaselineModel.php');

class OAuthClient extends BaselineModel {
    
    var $CI;
    
    public function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
    }
    
    public function getId() {
        $db = $this->load->database('default', TRUE);
        $new_id = random_string('alnum', 6);
        $query = $db->get_where('oauth_clients', ['client_id' => $new_id], 1);
        while($query->num_rows()>0) {
            $new_id = random_string('alnum', 6);
            $query = $db->get_where('oauth_clients', ['client_id' => $new_id], 1);
        }
        return $new_id;
    }
    
    public function initialize( $account ) {
        $db = $this->load->database('default', TRUE);
        $query= $db->get_where("oauth_clients", ['user_id' => $account->id], 1);
        if($query->num_rows()<1) {
            // no client found - create one
            $params = [
                'user_id' => $account->id,
                'client_id' => $this->getId(),
                'client_secret' => random_string('alnum', 12),
                'grant_types' => 'client_credentials',
                'scope' => 'user'
            ];
            $db->insert('oauth_clients', $params);
            // re-query
            $query= $db->get_where("oauth_clients", ['user_id' => $account->id], 1);
            return $query->result()[0];
        }
        return $query->result()[0];
    }
    
}
