<?php
/* Geoloqi
 * Implementation of the Geoloqi API
 *==============================================================================
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
 *------------------------------------------------------------------------------
 * Original Author: Jillian Ada Burrows
 * Email:           jill@adaburrows.com
 * Website:         <http://www.adaburrows.com>
 * Github:          <http://github.com/jburrows>
 * Geoloqi:        <http://www.Geoloqi.com/jillian.burrows>
 * Twitter:         @jburrows
 *------------------------------------------------------------------------------
 * Use at your own peril! J/K
 * 
 */

// make sure we can access the http_request class
app::getLib('http_request');

class geoloqi extends http_request {
	//Geoloqi API domain
	private $gl_api;
	//Geoloqi oAuth endpoint
	private $gl_ver;
	//Geoloqi app id
	private $gl_id;
	//Geoloqi app key
	private $gl_key;
	//Geoloqi app secret
	private $gl_secret;

	//redirect URI (on your server)
	private $redirect_uri;

	//the token info
	public $access_token;
  public $expiration;
  public $refresh_token;

	public function geoloqi() {$this->__construct();}

	public function __construct() {
		//call parent constructor
		parent::__construct();

    $this->gl_user_auth = 'https://beta.geoloqi.com/oauth/authorize';
		$this->gl_api = 'api.geoloqi.com';
		$this->gl_ver = '1';

		//set up the specifics for connecting to Geoloqi's api
		$this->request_params['port']		  = 443;
		$this->request_params['scheme']		= 'tls://';
		$this->request_params['host']		  = $this->gl_api;
	}

	public function init($client_id, $client_secret, $uri) {
		//put the gl API keys here:
		$this->gl_id	      = $client_id;
		$this->gl_secret	  = $client_secret;
		$this->redirect_uri = $uri;
	}

	/* set_redirect_uri
	 * ----------------
	 * Sets the URI that face will redirect the user to after authentication
	 */
	public function set_redirect_uri($uri) {
		$this->redirect_uri = $uri;
	}

	/* auth_redirect
	 * -------------
	 * Redirects the user to the autication page if necessary.
	 * You can set the permissions you would like by passing in an array of permissions:
	 *     auth_redirect( array( 'publish_stream', 'create_event', 'rsvp_event' ) );
	 * See <http://developers.Geoloqi.com/docs/authentication/permissions>
	 * for a full list of permissions.
	 */
	public function auth_redirect($permissions=null) {
		if (!isset($_GET['code'])) {
			$redirect_url  = $this->gl_user_auth;
			$redirect_url .= "?client_id={$this->fb_id}";
			$redirect_url .= "&redirect_uri={$this->redirect_uri}";
      // to be implemented later:
			//$redirect_url .= $permissions!=null ? '&'.implode(',', $permissions) : '';

			http_redirect($redirect_url);
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
		$this->request_params['query_params']	= array(
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'redirect_uri'  => $this->redirect_uri,
				'client_id'   	=> $this->gl_id,
				'client_secret'	=> $this->gl_secret,
			);
    $this->_update_token();
		return $this->access_token!=null;
	}

	/* refresh_token
	 * -------------
	 * After authentication is requested this function will return true if 
	 * the access token was retreived successfully. The access token is stored
	 * in $this->token.
	 */
	public function refresh_token() {
		$this->request_params['query_params']	= array(
        'grant_type'    => 'refresh_token',
				'client_id'   	=> $this->gl_id,
				'client_secret'	=> $this->gl_secret,
			);
    $this->_update_token();
		return $this->access_token!=null;
	}

	/* _update_token()
	 * ----------------
	 * Executes the oauth token endpoint and sets the proper properties
	 */
  protected function _update_token() {
    $this->request_params['method'] = 'POST';
		$this->request_params['path']		= $this->gl_version.'oauth/token';
		$data = $this->do_request() ? json_decode($this->get_data()) : null;
		$access_token   = isset($data['access_token']) ? $data['access_token'] : null;
		$expiration     = isset($data['access_token']) ? $data['access_token'] : null;
		$refresh_token  = isset($data['access_token']) ? $data['access_token'] : null;
		$this->access_token   = $access_token;
		$this->expiration     = $expiration;
		$this->refresh_token  = $refresh_token;
    $this->request_params['header_params'] = array('Authorization: OAuth '.$this->access_token);
  }


	/* restore_token()
	 * ------------------
	 * Sets an access token to use
   * Used to restore access using a stored token
	 */
  public function restore_token($token) {
    $this->access_token = $token;
  }

	/* get_object
	 * ----------
	 * Requests an object's basic info from . Returns data
	 * as a hash with keys that match the expected values as specified at:
	 */
	public function get_object($object) {
		$this->request_params['path']		= $object;
		$object = $this->do_request() ? $this->get_data() : null;
		if ($object != null) {
			$object = json_decode($object, true);
		}
		return $object;	
	}

	/* delete_object
	 * -------------
	 * Deletes an object's from the gl social graph if you have permissions.
	 */
	public function delete($object_id) {
		$this->request_params['method']		= 'DELETE';
		$this->request_params['path']		= $object_id;
		$this->request_params['query_params']	= array(
				'access_token' => $this->token
			);
		$object = $this->do_request() ? $this->get_data() : null;
		if ($object != null) {
			$object = json_decode($object, true);
		}
		return $object;
	}

}
