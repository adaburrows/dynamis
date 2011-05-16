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
app::getLib('OAuth2');

class geoloqi extends OAuth2 {
    public function initialize() {
        $this->user_auth = 'https://beta.geoloqi.com/oauth/authorize';
        $this->domain = 'api.geoloqi.com';
        $this->api_version = '/1';
        $this->token_endpoint = 'oauth/token';

        $this->request_params['host'] = $this->domain;
    }

    private function _has_data($object) {
        if ($object != null) {
            $object = json_decode($object, true);
        }
        return isset($object['data']) ? $object['data'] : null;
    }

    /* _update_token()
     * ----------------
     * Executes the oauth token endpoint and sets the proper properties
     */

    protected function _update_token() {
        $this->request_params['method'] = 'POST';
        $this->request_params['path'] = "{$this->api_version}/{$this->token_endpoint}";
        $data = $this->do_request() ? $this->get_data() : null;
        $data = json_decode($data, true);
        $access_token = isset($data['access_token']) ? $data['access_token'] : null;
        $expiration = isset($data['expires_in']) ? $data['expires_in'] : null;
        $refresh_token = isset($data['refresh_token']) ? $data['refresh_token'] : null;
        $this->access_token = $access_token;
        $this->expiration = $expiration;
        $this->refresh_token = $refresh_token;
        $this->request_params['header_params'] = array('Authorization: OAuth ' . $this->access_token);
    }


}
