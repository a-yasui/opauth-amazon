<?php
/**
* Amazon strategy for Opauth
* based on http://login.amazon.com/website
* 
* More information on Opauth: http://opauth.org
* 
* @copyright    Copyright © 2013 Atsushi Yasui (https://github.com/a-yasui)
* @link         http://opauth.org
* @package      Opauth.AmazonStrategy
* @license      MIT License
*/

/**
* Amazon strategy for Opauth
* based on http://login.amazon.com/website
* 
* @package Opauth.Amazon
*/
class AmazonStrategy extends OpauthStrategy{
    
    /**
     * Compulsory config keys, listed as unassociative arrays
     */
    public $expects = array('client_id', 'client_secret');
    
    /**
     * Optional config keys, without predefining any default values.
     */
    public $optionals = array('redirect_uri', 'scope', 'state');
    
    /**
     * Optional config keys with respective default values, listed as associative arrays
     * eg. array('scope' => 'email');
     */
    public $defaults = array(
        'redirect_uri' => '{complete_url_to_strategy}oauth2callback',
        'scope' => 'profile'
    );
    
    /**
     * Auth request
     */
    public function request(){
            $url = 'https://www.amazon.com/ap/oa';
            $params = array(
                'client_id' => $this->strategy['client_id'],
                'redirect_uri' => $this->strategy['redirect_uri'],
                'response_type' => 'token',
                'state' => $this->strategy['state'],
                'scope' => $this->strategy['scope']
            );

            foreach ($this->optionals as $key){
                    if (!empty($this->strategy[$key])) $params[$key] = $this->strategy[$key];
            }
            
            $this->clientGet($url, $params);
    }
    
    /**
     * Internal callback, after OAuth
     */
    public function oauth2callback(){
        if (array_key_exists('access_token', $_GET) && !empty($_GET['access_token'])){
            $access_token = $_GET['access_token'];
            $url = 'https://api.amazon.com/auth/o2/tokeninfo';
            $params = array(
                'grant_type'=>'authorization_code',
                'access_token' => $access_token,
                'client_id' => $this->strategy['client_id'],
                'client_secret' => $this->strategy['client_secret'],
                'redirect_uri' => $this->strategy['redirect_uri'],
                'grant_type' => 'authorization_code'
            );
            $response = $this->serverGet($url, $params, null, $headers);
            $results = json_decode($response);
            
            if (!empty($results) && !empty($results->aud)){
                if ($results->aud != $this->strategy['client_id']) {
                    $error = array(
                        'provider' => 'Amazon',
                        'code' => 'access_token_error',
                        'message' => 'Failed when attempting to not match access token',
                        'raw' => array(
                            'response' => $response,
                            'headers' => $headers
                        )
                    );
                    error_log("[AmazonStrategy.php][oauth2callback] Not set Access Token");
                    $this->errorCallback($error);
                }

                $userinfo = $this->userinfo($access_token);
                $this->auth = array(
                    'provider' => 'Amazon',
                    'uid' => $userinfo->user_id,
                    'info' => array(
                        'name' => $userinfo->name,
                        'image' => "",
                    ),
                    'credentials' => array(
                        'token' => $access_token,
                        'expires' => date('c', time() + $results->expires_in)
                    ),
                    'raw' => $userinfo
                );
                
                $this->callback();
            }
            else{
                $error = array(
                    'provider' => 'Amazon',
                    'code' => 'access_token_error',
                    'message' => 'Failed when attempting to obtain access token',
                    'raw' => array(
                        'response' => $response,
                        'headers' => $headers
                    )
                );
                $this->errorCallback($error);
            }
        }
        else{
            $error = array(
                'provider' => 'Amazon',
                'code' => 'oauth2callback_error',
                'raw' => $_GET
            );
            $this->errorCallback($error);
        }
    }
    
    /**
     * Queries People API for user info
     *
     * @param string $access_token 
     * @return array Parsed JSON results
     */
    private function userinfo($access_token){
        $userinfo = self::httpRequest('https://api.amazon.com/user/profile',
            array('http'=>array('header'=>"Authorization: bearer ".$access_token)), $headers);
        if (!empty($userinfo)){
            return json_decode($userinfo);
        }
        else{
            $error = array(
                    'provider' => 'Amazon',
                    'code' => 'userinfo_error',
                    'message' => 'Failed when attempting to query for user information',
                    'raw' => array(
                            'response' => $userinfo,
                            'headers' => $headers
                    )
            );

            $this->errorCallback($error);
        }
    }
}