<?php

/* Fb
 * An easy to use, basic implementation of Facebook's Graph API for PHP
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
 * Facebook:        <http://www.facebook.com/jillian.burrows>
 * Twitter:         @jburrows
 * ------------------------------------------------------------------------------
 * Use at your own peril! J/K
 *
 */

// make sure we can access the http_request class
app::getLib('OAuth2');

class facebook_graph extends OAuth2 {

    public function initialize() {
        $this->user_auth = 'https://www.facebook.com/dialog/oauth';
        $this->domain = 'graph.facebook.com';
        $this->token_auth = 'oauth/token';
        $this->permissions_delim = ',';
    }

    private function _add_auth($data) {
        $return = array_merge(
            array('access_token' => $this->token),
            $data
        );
        return ($return);
    }

    private function _has_id($object) {
        if ($object != null) {
            $object = json_decode($object, true);
            $object = $object['id'];
        }
        return $object;
    }

    private function _has_data($object) {
        if ($object != null) {
            $object = json_decode($object, true);
        }
        return isset($object['data']) ? $object['data'] : null;
    }

    private function _has_meta($object) {
        if ($object != null) {
            $object = json_decode($object, true);
        }
        return isset($object['metadata']) ? $object['metadata'] : null;
    }

    /* get_connection_types
     * --------------------
     * Requests an object's connection types to the FB social graph. Returns
     * a hash of connection types as keys and links to the respective api
     * calls.
     * <http://developers.facebook.com/docs/api>
     */

    public function get_relationships($object) {
        $data = null;
        $this->request_params['path'] = $object;
        $this->request_params['query_params'] = array(
            'metadata' => 1,
            'access_token' => $this->token
        );
        $object = $this->do_request() ? $this->get_data() : null;
        $meta = $this->_has_meta($object);
        if (($meta != null) && isset($meta['connections'])) {
            $data = $meta['connections'];
        }
        return $meta;
    }

    /* get_connections
     * ---------------
     * Requests an object's connections to other objects on the FB graph.
     * $relation can be: any of: (friends, home, feed (Wall), likes, 
     * movies, books, notes, photos, videos, events, groups).
     * However, call get_connection_types to get a real list of connection
     * an object supports.
     * <http://developers.facebook.com/docs/api>
     */

    public function get_connections($object, $relation) {
        $object = $this->get("$object/$relation");
        return $this->_has_data($object);
    }

    /* post_feed
     * ---------
     * Posts a message to $profile_id's feed and returns the message id.
     * This is basically a way to post to a user's wall, But can be used to
     * post to pages or events.
     *
     * Message parameters: message, picture, link, name, description
     *
     * <http://developers.facebook.com/docs/api>
     */

    public function post_feed($profile_id, $params) {
        $body = $this->_add_auth($params);
        $object = $this->post("$profile_id/feed", $body);
        return $this->_has_id($object);
    }

    /* post_like
     * ---------
     * Likes a post.
     *
     * Message parameters: none
     *
     * <http://developers.facebook.com/docs/api>
     */

    public function post_like($post_id, $params) {
        $body = $this->_add_auth($params);
        $object = $this->post("$post_id/likes", $body);
        return $this->_has_id($object);
    }

    /* post_comment
     * ------------
     * Posts a comment to $post_id and returns the comment id.
     *
     * Message parameters: message
     *
     * <http://developers.facebook.com/docs/api>
     */

    public function post_comment($post_id, $params) {
        $body = $this->_add_auth($params);
        $object = $this->post("$post_id/comments", $body);
        return $this->_has_id($object);
    }

    /* post_note
     * ---------
     * Posts a note to $profile_id's feed and returns the note id.
     *
     * Message parameters: subject, message (an HTML string)
     *
     * <http://developers.facebook.com/docs/api>
     */

    public function post_note($profile_id, $params) {
        $body = $this->_add_auth($params);
        $object = $this->post("$profile_id/notes", $body);
        return $this->_has_id($object);
    }

    /* post_link
     * ---------
     * Posts a link to $profile_id and returns the link id.
     *
     * Message parameters: link, message
     *
     * <http://developers.facebook.com/docs/api>
     */

    public function post_link($profile_id, $params) {
        $body = $this->_add_auth($params);
        $object = $this->post("$profile_id/links", $body);
        return $this->_has_id($object);
    }

}
