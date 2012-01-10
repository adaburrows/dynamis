<?php
/* =============================================================================
 * Kannel Interface Class
 * ----------------------
 * Kannel can be used to send SMS messages through a variety of means.
 * See http://kannel.org/download/kannel-userguide-snapshot/userguide.html
 * for more information.
 * -----------------------------------------------------------------------------
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
 * -----------------------------------------------------------------------------
 * Original Author: Jillian Ada Burrows
 * Email:           jill@adaburrows.com
 * Website:         <http://www.adaburrows.com>
 * Github:          <http://github.com/jburrows>
 * Twitter:         @jburrows
 * ===========================================================================*/

app::getLib('http_request');
class kannel extends http_request {

    private $status_key;
    private $admin_key;
    private $sms_key;
    private $sms_user;
    private $kannel_host;
    private $admin_port;
    private $sms_port;


    /*
     * Initialize variables used from the configuration
     */
    public function initialize() {
        $this->sms_user = config::get('kannel_user');
        $this->kannel_host = config::get('kannel_host');
        $this->admin_port = config::get('kannel_admin_port');
        $this->sms_port = config::get('kannel_sms_port');

        $this->status_key = config::get('kannel_status');
        $this->admin_key = config::get('kannel_admin');
        $this->sms_key = config::get('kannel_sms');


        $this->request_params['host'] = $this->kannel_host;
        $this->request_params['port'] = $this->admin_port;
    }

    /*
     * Send an sms message using a specific kannel device
     */
    public function send($number_list, $message, $smsc = 'a0') {
        $this->request_params['port'] = $this->sms_port;
        $this->request_params['query_params'] = array(
            'username' => $this->sms_user,
            'password' => $this->sms_key,
            'to' => $number_list,
            'text' => $message,
            'smsc' => $smsc
        );
        $this->request_params['path'] = '/send';
        $status = $this->do_request();
        if ($status == 202) {
            $status = true;
        } else {
            $status = false;
        }
        $this->request_params['port'] = $this->admin_port;
        return $status;
    }

    /*
     * Get the status of Kannel
     */
    public function status() {
        $data = parent::get('/status.xml', array('password' => $this->status_key));
        $data = simplexml_load_string($data);
        return $data;
    }

    /*
     * Get the status of the message storage
     */
    public function store_status() {
        $data = parent::get('/store-status.xml', array('password' => $this->status_key));
        $data = simplexml_load_string($data);
        return $data;
    }

    /*
     * Get IDs of all the configured SMSCs
     */
    public function get_ids() {
        $data = $this->status();
        $ids = array();
        foreach($data->smscs->smsc as $smsc) {
            $ids[] = $smsc->id[0];
        }
        return $ids;
    }

    /*
     * Start an smsc
     */
    public function start_smsc($smsc_id) {
        $data = parent::get('/start-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

    /*
     * Stop an smsc
     */
    public function stop_smsc($smsc_id) {
        $data = parent::get('/stop-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

    /*
     * Add an smsc to Kannel
     */
    public function add_smsc($smsc_id) {
        $data = parent::get('/add-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

    /*
     * Remove a smsc to kannel
     */
    public function remove_smsc($smsc_id) {
        $data = parent::get('/remove-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

}
