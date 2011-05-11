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
        $this->token_auth = 'oauth/token';
    }
}
