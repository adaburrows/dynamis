<?php
/**
 * 
 * A PHP class for interacting with the SMSified API.
 * Parts taken from the smsified github library
 * Serverely modified by yours truly, Ada Burrows
 */
app::getLib('http_request');
class smsified extends http_request {

    // Private class members.
    private $version = '/v1/';
    private $username;
    private $password;

    /**
     *
     * Class constructor
     * @param string $username
     * @param string $password
     */
    public function __construct() {
        parent::__construct();
        $this->request_params['scheme'] = 'tls://';
        $this->request_params['port'] = '443';
        $this->request_params['host'] = 'api.smsified.com';

        $this->username = app::$config['smsified_user'];
        $this->password = app::$config['smsified_pass'];

        $this->add_basic_auth($this->username, $this->password);
    }
	
    /**
     *
     * Processes the return code and throws exceptions if errors occur.
     */
    public function processStatus() {
        $return = false;
        $status = self::get_status();
        switch ($status) {
          case '200':
          case '201':
          case '204':
            $return = true;
            break;
          case '401':
            throw new SMSifiedAuthError('Incorrect login credentials!');
            break;
          case '500':
          case '503':
            throw new SMSifiedServerError('Report this server error to SMSified.');
            break;
          case '400':
          case '404':
          case '405':
          case '415':
          default:
            throw new SMSifiedException('There was a problem with this request.');
            break;
        }
        return $return;
    }

    /**
     *
     * Sends an error message to smsified if there is a server error.
     */
    public function fileServiceReport($result) {
      //TODO: Actually implement this. :-)
    }

    /**
     *
     * Send an outbound SMS message.
     * $message has the following components
     *   array(
     *     'sender' => '',	// Number you are sending from (must belong to you).
     *     'address' => '',	// Recipient phone number (with the 1) --
     *				//   can also be an array of recipients.
     *     'message' => '',	// Message to be sent.
     *     'notifyURL' => ''	// OPTIONAL: URL to be notified of delivery.
     *   );
     *
     *   To get the actual response, instead of true/false call:
     *     $data = $smsified->get_data();
     *   where $smsified is an instance of this object.
     *
     * @param array $message
     */
    public function sendMessage($message) {
        $status = false;
        if(empty($message['sender'])){
            $senderAddress = app::$config['smsified_num'];
        } else {
            $senderAddress = $message['sender'];
            unset($message['sender']);
        }
        $url = $this->version . "smsmessaging/outbound/$senderAddress/requests";
        $result = self::post($url, $message);
        try {
            $status = self::processStatus();
            app::log('Message sent: "'.$message['message'].'"'."\n");
            app::log('Returned: "'.$result.'"'."\n");
        } catch (SMSifiedServerError $e) {
            app::log('Server Error -- Reporting to SMSified. Failed to send message: "'.$message['message'].'"'."\n");
            app::log('Returned: "'.$result.'"'."\n");
            self::fileServiceReport($result);
            $status = false;
        } catch (SMSifiedAuthError $e) {
            app::log('Authentication Error: Failed to send message: "'.$message['message'].'"'."\n");
            $status = false;
        } catch (SMSifiedException $e) {
            app::log('Failed to send message: "'.$message['message'].'"'."\n");
            $status = false;
        }
        return $status;
    }

    /**
     *
     * Check the delivery status of an outbound SMS message.
     * @param unknown_type $senderAddress
     * @param unknown_type $requirestId
     */
    public function checkStatus($senderAddress, $requestId) {
        $url = $this->version . "smsmessaging/outbound/$senderAddress/requests/$requestId/deliveryInfos";
	return json_decode(self::get($url), true);
    }

    /**
     *
     * Create a subscription.
     * @param string $senderAddress
     * @param string $direction
     * @param string $notifyURL
     */
    public function createSubscription($senderAddress, $direction, $notifyURL) {
        $url = $this->version . "smsmessaging/$direction/$senderAddress/subscriptions";
        $params = array('notifyURL' => $notifyURL);
        return json_decode(self::post($url, $params), true);
    }

    /**
     *
     * View subscrptions
     * @param string $senderAddress
     * @param string $direction
     */
    public function viewSubscriptions($senderAddress, $direction) {
        $url = $this->version . "smsmessaging/$direction/subscriptions/?senderAddress=$senderAddress";
        return json_decode(self::get($url), true);
    }

    /**
     *
     * Delete an active subscription.
     * @param string $subscriptionId
     * @param string $direction
     */
    public function deleteSubscriptions($subscriptionId, $direction) {
        $url = $this->version . "smsmessaging/$direction/subscriptions/$subscriptionId";
        return json_decode(self::delete($url), true);
    }

    /**
     *
     * Get the details of SMS message delivery.
     * @param string $messageId
     * @param array $params
     */
    public function getMessages($messageId=NULL, $params=NULL) {
        $url = $this->version . "messages/";

        if ($messageId) {
            $url .= "$messageId";
        } else {
            $url .= '?';
            foreach ($params as $key => $value) {
                $url .= "$key=$value&";
            }
        }

        return json_decode(self::get($url), true);
    }

/*=============================================================================\
| The following methods are really unsupported by SMSified, but they work. ;-) |
\=============================================================================*/

    /**
     *
     * Get all applications running on your account.
     * (should be only one)
     * @param string $messageId
     * @param array $params
     */
    public function getApplications() {
        $url = $this->version . "applications.json";
        return json_decode(self::get($url), true);
    }

    /**
     *
     * Get all numbers asscociated with the account.
     * @param string $applicationId
     */
    public function getNumbers($applicationId) {
        $url = $this->version . "applications/{$applicationId}/addresses.json";
        return json_decode(self::get($url), true);
    }

    /**
     *
     * Provision a new number to be used with the app.
     * @param string $applicationId
     * @param string $areaCode
     */
    public function provisionNumber($applicationId, $areaCode) {
        $url = $this->version . "applications/{$applicationId}/addresses.json";

        $response = self::post($url, array('type' => 'number', 'prefix' => '1' . $areaCode));
        $response = json_decode($response);
        if (preg_match('/(1[0-9]{10})/', $response->href, $match)) {
            return $match[1];
        } else {
            return FALSE;
        }
    }

    /**
     *
     * Delete an number so the app no longer uses it.
     * @param string $applicationId
     * @param string $number
     */
    public function deleteNumber($applicationId, $number) {
        $url = $this->version . "applications/{$applicationId}/addresses/number/{$number}.json";
        $response = self::delete($url);
        return json_decode($response);
    }
}

/**
 * 
 * Simple classes to wrap exceptions.
 *
 */
class SMSifiedException extends Exception {}
class SMSifiedAuthError extends SMSifiedException {}
class SMSifiedServerError extends SMSifiedException {}
