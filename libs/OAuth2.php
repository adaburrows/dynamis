<?php

/* OAuth2
 * ------
 * Basic implementation of an OAuth2 base class
 * An OAuth2 consumer wishing to store tokens must create it's own storage class.
 * ==============================================================================
 * This code is being released under an MIT style license:
 *
 * Copyright (c) 2010 Jillian Ada Burrows
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * ------------------------------------------------------------------------------
 * Original Author: Jillian Ada Burrows
 * Email:           jill@adaburrows.com
 * Website:         <http://www.adaburrows.com>
 * Github:          <http://github.com/jburrows>
 * Twitter:         @jburrows
 * ------------------------------------------------------------------------------
 */


// make sure we can access the http_request class
app::getLib('http_request');

class OAuth2 extends http_request {

    //User Authentication URL
    protected $user_auth;
    //OAuth2 API Domain
    protected $domain;
    //OAuth2 Endpoint
    protected $api_version;
    //Endpoint for token retrieval
    protected $token_endpoint;
    //OAuth2 App ID
    protected $app_id;
    //OAuth2 App Key
    protected $app_key;
    //OAuth2 App Secret
    protected $app_secret;
    //OAuth2 Redirect URI (on your server)
    protected $redirect_uri;
    protected $permissions_delim;

    //the token info
    public $access_token;
    public $expiration;
    public $refresh_token;

    public function __construct() {
        //call parent constructor
        parent::__construct();

        $this->user_auth = '';
        $this->domain = '';
        $this->api_version = '';
        $this->token_endpoint = '';
        $this->permissions_delim = ' ';

        //set up the specifics for connecting via OAuth2
        $this->request_params['port'] = 443;
        $this->request_params['scheme'] = 'tls://';
        $this->request_params['host'] = $this->domain;
    }

    /* init($client_params)
     * --------------------
     * Initializes needes variables for authenticating with an OAuth2 Provider.
     */
    public function init($client_params) {
        $defaults = array(
          'app_id' => NULL,
          'app_key' => NULL,
          'app_secret' => NULL,
          'redirect_uri' => NULL,
        );
        $params = array_merge($defaults, $client_params);
        //set up all the needed app values:
        $this->app_id = $params['app_id'];
        $this->app_key = $params['app_key'];
        $this->app_secret = $params['app_secret'];
        $this->redirect_uri = $params['redirect_uri'];
    }

    /* set_redirect_uri
     * ----------------
     * Sets which URI the user is redirected after authentication
     */
    public function set_redirect_uri($uri) {
        $this->redirect_uri = $uri;
    }

    /* auth_redirect
     * -------------
     * Redirects the user to the autication page if necessary.
     * You can set the permissions you would like by passing in an array of permissions:
     *     auth_redirect( array( 'publish_stream', 'create_event', 'rsvp_event' ) );
     * You must see the specs for API you are trying to use.
     */
    public function auth_redirect($response_type = 'code', $permissions = null) {
        if (!isset($_GET['code'])) {
            $redirect_url = $this->user_auth;
            $query_parts = array(
				'response_type'=> $response_type,
				'client_id' => $this->app_id,
				'redirect_uri' => $this->redirect_uri
				);
            if($permissions != null) {
                $query_parts['scope'] = implode($this->permission_delim, $permissions);
            }
			$redirect_url .= http_build_query($query_parts);
            header("Location: $redirect_url");
            exit(0);
        }
    }

    /* get_user_token
     * ---------------
     * After authentication is requested this function will return true if 
     * the access token was retreived successfully. The access token is stored
     * in $this->token.
     */
    public function get_user_token() {
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $_GET['code'],
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret
        );
        $this->_update_token($params);
        return $this->access_token != null;
    }

	/* get_application_token
     * ---------------------
     * After the application token is requested this function will return true
	 * if the access token was retreived successfully. The access token is
	 * stored in $this->token.
     */
	public function get_application_token() {
		$params = array(
			'grant_type' => 'client_credentials',
			'client_id' => $this->app_id,
			'client_secret' => $this->app_secret
		);
		$this->update_token($params);
		return $this->access_token != null;
	}

    /* refresh_user_token
     * ------------------
     * After authentication is requested this function will return true if 
     * the access token was retreived successfully. The access token is stored
     * in $this->token.
     */
    public function refresh_user_token() {
        $this->request_params['query_params'] = array(
            'grant_type' => 'refresh_token',
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
        );
        $this->_update_token();
        return $this->access_token != null;
    }

    /* _update_token()
     * ----------------
     * Executes the oauth token endpoint and sets the proper properties
     *
     * NOTE: Override this function in each implementation since each serivce
     * may return its format of access tokens.
     */
    protected function _update_token() {
    }

    /* set_token()
     * -----------
     * Sets an access token to use
     * Used to restore access using a stored token
     */
    public function set_token($token) {
        $this->access_token = $token;
    }

	/* set_token()
     * -----------
     * Sets an access token to use
     * Used to restore access using a stored token
     */
	public function get_token() {
		return $this->access_token;
	}

	/* _add_auth
     * ---------
     * Adds the access_token param to requests
     */
	protected function _add_auth($data) {
		$return = array_merge(
			array('access_token' => $this->access_token),
			$data
		);
		return ($return);
	}

	/* no_auth_get()
     * -------------
     * Requests an object's basic info from the OAuth2 service.
     * Returns returns raw text response.
     */
	public function no_auth_get($object, $params = array()) {
		return parent::get($object, $params);
	}

    /* get()
     * -----
     * Requests an object's basic info from the OAuth2 service.
     * Returns returns raw text response.
     */
    public function get($object, $params = array()) {
        return parent::get("{$this->api_version}{$object}", $this->_add_auth($params));
    }

    /* post()
     * ------
     * Posts the specified data to the object location.
     * Returns returns raw text response.
     */
    public function post($object, $data, $content_type = null) {
        return parent::post("{$this->api_version}{$object}", $this->_add_auth($data), $content_type);
    }

    /* put()
     * ------
     * Puts the specified data to the object location.
     * Returns returns raw text response.
     */
    public function put($object, $data, $content_type = null) {
        return parent::put("{$this->api_version}{$object}", $this->_add_auth($data), $content_type);
    }

    /* delete()
     * --------
     * Deletes an object if you have permissions.
     * Returns returns raw text response.
     */
    public function delete($object, $params = array()) {
        return parent::delete("{$this->api_version}{$object}", $this->_add_auth($params));
    }

}
