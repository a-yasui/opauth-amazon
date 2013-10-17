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
        if (array_key_exists('code', $_GET) && !empty($_GET['code'])){
            $code = $_GET['code'];
            $url = 'https://api.amazon.com/auth/o2/tokeninfo';
            $params = array(
                'grant_type'=>'authorization_code',
                'code' => $code,
                'client_id' => $this->strategy['client_id'],
                'client_secret' => $this->strategy['client_secret'],
                'redirect_uri' => $this->strategy['redirect_uri'],
                'grant_type' => 'authorization_code'
            );
            $response = $this->serverPost($url, $params, null, $headers);
            
            $results = json_decode($response);error_log($result);
            
            if (!empty($results) && !empty($results->access_token)){
                $userinfo = $this->userinfo($results->access_token);
                $this->auth = array(
                    'provider' => 'Amazon',
                    'uid' => $userinfo->user_id,
                    'info' => array(
                        'name' => $userinfo->entry->displayName,
                        'image' => $userinfo->entry->thumbnailUrl
                    ),
                    'credentials' => array(
                        'token' => $results->access_token,
                        'expires' => date('c', time() + $results->expires_in)
                    ),
                    'raw' => $userinfo
                );
                
                // if (!empty($userinfo->entry->profileUrl)) $this->auth['info']['urls']['amazon'] = $userinfo->entry->profileUrl;
                
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
        $headers = array(
            'Authorization' => 'bearer ' . $access_token
        );

        $userinfo = $this->serverGet('https://api.amazon.com/user/profile', array(), null, $headers);
        if (!empty($userinfo)){
            return json_decode($userinfo);
        }
        else{
            $error = array(
                    'provider' => 'Mixi',
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