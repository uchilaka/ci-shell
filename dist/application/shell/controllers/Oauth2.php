<?php

require_once makepath(__DIR__, 'BaseController.php');

class Oauth2 extends BaseController {

    var $error = null;

    public function __construct() {
        parent::__construct();
    }

    public function token() {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();
        $this->oauth2lib->server->handleTokenRequest($request, $response);
        $response->send();
    }
    
    public function passive_signup() {
        try {
            if (!App::requestIsPost()) {
                throw new Exception('POST request is required', 400);
            }
            /** @TODO include link to documentation **/
            if(!$this->isSecureAccess()) {
                throw new Exception('oauth/passive_signup requires supported secondary authentication', 401);
            }
            $this->load->library('form_validation');
            $rules = [
                [
                    'field' => 'email',
                    'label' => 'Email Address',
                    'rules' => 'required|valid_email|is_unique[users.email]'
                ],
                [
                    'field' => 'username',
                    'label' => 'Username',
                    'rules' => 'required|min_length[5]|max_length[255]|is_unique[users.username]'
                ],
                [
                    'field' => 'first_name',
                    'label' => 'First Name',
                    'rules' => 'required'
                ],
                [
                    'field' => 'last_name',
                    'label' => 'Last Name',
                ],
            ];
            $this->form_validation->set_rules($rules);
            if (!empty($this->POST['email'])) {
                // check account
                $this->account = $this->User->getByEmail($this->POST['email']);
                if (!empty($this->account)) {
                    throw new Exception("Account already exists!", 409);
                }
            }
            if (!$this->form_validation->run()) {
                // validation failed
                $this->set('errors', App::parseErrorsFromRules($rules));
                throw new Exception("Validation failed: " . App::summarizeErrorsFromRules($rules), 400);
            }
            // validation passed
            $this->User->save();
            $account = $this->User->getById($this->User->getInsertedId());
            $this->set('Account', $account);
            $this->set('status', 200);
            $this->set('status_msg', 'OK');
        } catch (Exception $ex) {
            $this->set('status', $ex->getCode());
            $this->set('status_msg', $ex->getMessage());
        }
        $this->respond();
    }
    
    function userinfo() {
        try {
            if (!empty($this->GET['id_token'])) {
                $socialProfile = $this->getAuth0LoggedInUser();
                $this->set('Profile', $socialProfile);
                // check if user exists
                $this->account = $this->User->getByEmail($socialProfile->email);
                // save account attributes
                $this->User->initialize($socialProfile);
            } else {
                $this->account = $this->oauth2lib->getUserById();
            }
            $this->set('Account', $this->account);
            if (empty($this->account)) {
                throw new Exception('No user account found', 404);
            }
            $this->set('status', 200);
            $this->set('status_msg', 'OK');
        } catch (Exception $ex) {
            $this->set('status', $ex->getCode());
            $this->set('status_msg', $ex->getMessage());
        }
        $this->respond();
    }    
    
    /** @depracated switched to Auth0 * */
    public function callback() {
        $platform = $this->safeRequestVariable('platform', App::METHOD_GET);
        $do = $this->safeRequestVariable('do', App::METHOD_GET);
        try {
            /** Authenticate against social media * */
            $social_profile = [];
            $user_attributes = [];
            $user_info = [];
            switch ($platform) {
                case 'fb':
                    // Facebook callback requires client-side pre-processing - completion is indicated by the do=callback GET parameter
                    if ($do === 'callback') {
                        $params = [
                            'fields' => 'id,email,first_name,last_name,gender,verified,picture,link'
                                //'access_token'=>$this->safeRequestVariable('access_token', App::METHOD_GET)
                        ];
                        $resp = $this->curl->exec(FACEBOOK_GRAPH_URI . 'me', App::METHOD_GET, $params, [
                            'Authorization: Bearer ' . $this->safeRequestVariable('access_token', App::METHOD_GET)
                        ]);
                        if (empty($resp)) {
                            throw new Exception("No authentication response", 400);
                        }
                        $json_resp = json_decode($resp);
                        if (!empty($json_resp->error)) {
                            throw new Exception($json_resp->error->message, $json_resp->error->code);
                        }
                        // got Facebook profile information
                        $social_profile = [
                            'domain_name' => 'facebook.com',
                            'profile_id' => $json_resp->id,
                            'profile_url' => $json_resp->link,
                            'user_id' => null // set when account is located
                        ];
                        $user_attributes = [
                            'photo_url' => empty($json_resp->picture) ? null : $json_resp->picture->data->url,
                            'gender' => empty($json_resp->gender) ? null : $json_resp->gender,
                            'user_id' => null // set when account is located
                        ];
                        $user_info = [
                            'first_name' => $json_resp->first_name,
                            'last_name' => $json_resp->last_name,
                            'email' => $json_resp->email
                        ];
                    }
                    break;
            }
            $error = $this->safeRequestVariable('error', App::METHOD_GET);

            if ($do === 'passthrough') {
                // do nothing - pass on to client
            } else {
                // social account was found - verify local account
                if (!empty($user_info) && empty($error)) {
                    try {
                        $this->account = $this->User->singleSignOn($user_info);
                    } catch (Exception $ex) {
                        if ($ex->getCode() === 404) {
                            // no account found - create new account
                            $this->account = $this->User->initialize($user_info, $user_attributes, $social_profile);
                        } else {
                            // have other error handler take over
                            throw $ex;
                        }
                    }
                    // OAuth authentication
                    if (!empty($this->account)) {
                        $oauthClient = $this->OAuthClient->initialize($this->account);
                        // print_r($oauthClient);
                        $params = [
                            'grant_type' => 'client_credentials',
                            'client_id' => $oauthClient->client_id,
                            'client_secret' => $oauthClient->client_secret,
                            'response_type' => 'token'
                        ];
                        $auth_resp = $this->curl->exec(base_url('api/v3/oauth2/token'), 'POST', $params);
                        if (empty($auth_resp)) {
                            throw new Exception("Authentication failed - no token created", 401);
                        }
                        $credentials = json_decode($auth_resp, TRUE);
                        // pass on credentials to client
                        redirect(base_Url('auth/callback?do=passthrough&') . http_build_query(array_merge($credentials, $this->User->convertToArray($this->account))));
                    }
                }
            }
        } catch (Exception $ex) {
            // redirect with error
            $this->error = [
                'code' => $ex->getCode(),
                'error' => $ex->getMessage()
            ];
            //redirect(base_url('auth/callback?') . http_build_query($params));
        }
        $this->load->view('header');
        $this->load->view('auth_callback', [
            'nav_view' => $this->nav_view,
            'error' => $this->error,
            'account' => $this->account
        ]);
    }


    /** @depracated * */
    function verify() {
        $this->oauth2lib->handleRequest(Oauth2lib::REQUESTTYPE_RESOURCE);
        try {
            $this->account = $this->oauth2lib->getUser();
            if (empty($this->account)) {
                throw new Exception("No account found", 404);
            }
            $this->set('account', $this->account);
            $credentials = $this->oauth2lib->getCredentials();
            $this->set('credentials', $credentials);
            $this->set('status', 200);
            $this->set('status_msg', 'OK');
            $this->respond();
        } catch (Exception $ex) {
            $this->set('status', $ex->getCode());
            $this->set('status_msg', $ex->getMessage());
        }
        $this->respond();
    }

}
