<?php

/* OAuth2
 * Basic implementation of an OAuth2 base class
 * ==============================================================================
 * -- Version alpha 0.1 --
 * The source code is fairly well documented, except for the base class it
 * derives from.
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
 * Geoloqi:        <http://www.Geoloqi.com/jillian.burrows>
 * Twitter:         @jburrows
 * ------------------------------------------------------------------------------
 * Use at your own peril! J/K
 *
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
            $redirect_url .= "?response_type={$response_type}&client_id={$this->app_id}&redirect_uri={$this->redirect_uri}";
            if($permissions != null) {
                $redirect_url .=
                  '&scope='.implode($this->permission_delim, $permissions);
            }
            header("Location: $redirect_url");
            exit(0);
        }
    }

    /* get_token
     * ---------
     * After authentication is requested this function will return true if 
     * the access token was retreived successfully. The access token is stored
     * in $this->token.
     */

    public function token() {
        $this->request_params['query_params'] = array(
            'grant_type' => 'authorization_code',
            'code' => $_GET['code'],
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret
        );
        $this->_update_token();
        return $this->access_token != null;
    }

    /* refresh_token
     * -------------
     * After authentication is requested this function will return true if 
     * the access token was retreived successfully. The access token is stored
     * in $this->token.
     */

    public function refresh_token() {
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
     * NOTE: Override this function in each implementation
     */

    protected function _update_token() {
    }

    /* restore_token()
     * ------------------
     * Sets an access token to use
     * Used to restore access using a stored token
     */

    public function restore_token($token) {
        $this->access_token = $token;
    }

    /* get()
     * -----
     * Requests an object's basic info from . Returns data
     * as a hash with keys that match the expected values as specified at:
     */

    public function get($object) {
        $this->request_params['path'] = "{$this->api_version}/{$object}";
        $object = $this->do_request() ? $this->get_data() : null;
        return $object;
    }
    
    /* post()
     * ------
     * Posts the specified data to the object location.
     * Returns data as a hash.
     */

    public function post($object, $post_data, $content_type = null) {
        $this->request_params['method'] = 'POST';
        $this->request_params['path'] = "{$this->api_version}/{$object}";
        $this->request_params['body'] = $post_data;
        $this->request_params['content-type'] = $content_type;
        $object = $this->do_request() ? $this->get_data() : null;
        return $object;
    }

    /* delete()
     * --------
     * Deletes an object if you have permissions.
     */

    public function delete($object) {
        $this->request_params['method'] = 'DELETE';
        $this->request_params['path'] = "{$this->api_version}/{$object}";
        $this->request_params['query_params'] = array(
            'access_token' => $this->token
        );
        $object = $this->do_request() ? $this->get_data() : null;
        return $object;
    }

}
