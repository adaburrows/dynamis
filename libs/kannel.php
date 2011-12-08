<?php

app::getLib('http_request');

class kannel extends http_request {

    private $status_key;
    private $admin_key;
    private $sms_key;
    private $sms_user;
    private $kannel_host;
    private $admin_port;
    private $sms_port;

    public function initialize() {
        $this->sms_user = app::$config['kannel_user'];
        $this->kannel_host = app::$config['kannel_host'];
        $this->admin_port = app::$config['kannel_admin_port'];
        $this->sms_port = app::$config['kannel_sms_port'];

        $this->status_key = app::$config['kannel_status'];
        $this->admin_key = app::$config['kannel_admin'];
        $this->sms_key = app::$config['kannel_sms'];


        $this->request_params['host'] = $this->kannel_host;
        $this->request_params['port'] = $this->admin_port;
    }

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

    public function status() {
        $data = parent::get('/status.xml', array('password' => $this->status_key));
        $data = simplexml_load_string($data);
        return $data;
    }

    public function store_status() {
        $data = parent::get('/store-status.xml', array('password' => $this->status_key));
        $data = simplexml_load_string($data);
        return $data;
    }

    public function get_ids() {
        $data = $this->status();
        $ids = array();
        foreach($data->smscs->smsc as $smsc) {
            $ids[] = $smsc->id[0];
        }
        return $ids;
    }

    public function start_smsc($smsc_id) {
        $data = parent::get('/start-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

    public function stop_smsc($smsc_id) {
        $data = parent::get('/stop-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

    public function add_smsc($smsc_id) {
        $data = parent::get('/add-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

    public function remove_smsc($smsc_id) {
        $data = parent::get('/remove-smsc', array(
                    'password' => $this->admin_key,
                    'smsc' => $smsc_id
                ));
        $data = simplexml_load_string($data);
        return $data;
    }

}
