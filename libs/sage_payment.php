<?php

/* Sage Payment Library
 * --------------------
 * Implementation of Sage Payments
 * ==============================================================================
 * ------------------------------------------------------------------------------
 * Original Author: Jillian Ada Burrows
 * Email:           jill@adaburrows.com
 * Website:         <http://www.adaburrows.com>
 * Github:          <http://github.com/jburrows>
 * ------------------------------------------------------------------------------
 * Use at your own peril! J/K
 *
 */

// make sure we can access the http_request class
app::getLib('http_request');

class sage_payment extends http_request {

    protected $domain;
    protected $merchant_info;

    public $processing_codes = array(
        'sale' => '01',
        'authonly' => '02',
        'force' => '03',
        'prior_auth' => '03',
        'void' => '04',
        'credit' => '06',
        'prior_auth_by_reference' => '11'
    );

    public $card_errors = array(
        '000000' => 'INTERNAL SERVER ERROR Server Error, Please contact Sage for assistance or more information',
        '650102' => 'AVS FAILURE N The AVS result was not an exact match and the transaction was VOIDed',
        '650103' => 'AVS FAILURE YX The AVS result was not an exact match and the transaction was VOIDed',
        '650103' => 'AVS FAILURE AYXWZ The AVS result was not a partial match and the transaction was VOIDed',
        '650104' => 'CVV FAILURE M The CVV result was not an exact match and the transaction was VOIDed',
        '650104' => 'CVV FAILURE N The CVV result was a no match and the transaction was VOIDed',
        '711711' => 'ERROR REVIEW REPORTING Please log into the Virtual Terminal to review your transaction history to determine the status of this transaction.  If you need assistance, please contact Technical Support at 877-470-4001',
        '900000' => 'INVALID T_ORDERNUM Order number value is in an invalid format',
        '900001' => 'INVALID C_NAME Name value is in an invalid format or was left blank',
        '900002' => 'INVALID C_ADDRESS Address value is in an invalid format or was left blank',
        '900003' => 'INVALID C_CITY City value is in an invalid format or was left blank',
        '900004' => 'INVALID C_STATE State value is in an invalid format or was left blank',
        '900005' => 'INVALID C_ZIP Zip code value is in an invalid format or was left blank',
        '900006' => 'INVALID C_COUNTRY Country value is in an invalid format or was left blank',
        '900007' => 'INVALID C_TELEPHONE Telephone value is in an invalid format or was left blank',
        '900008' => 'INVALID C_FAX Fax value is in an invalid format or was left blank',
        '900009' => 'INVALID C_EMAIL Email value is in an invalid format or was left blank',
        '900010' => 'INVALID C_SHIP_NAME Shipping address name value is in an invalid format',
        '900011' => 'INVALID_C_SHIP_ADDRESS Shipping Address value is in an invalid format',
        '900012' => 'INVALID_C_SHIP_CITY Shipping city value is in an invalid format',
        '900013' => 'INVALID_C_SHIP_STATE Shipping state value is in an invalid format',
        '900014' => 'INVALID_C_SHIP_ZIP Shipping zip code value is in an invalid format',
        '900015' => 'INVALID_C_SHIP_COUNTRY Shipping country value is in an invalid format',
        '900016' => 'INVALID_C_CARDNUMBER Credit card number value is in an invalid format',
        '900017' => 'INVALID_C_EXP Expiration date value is in an invalid format',
        '900018' => 'INVALID_C_CVV CVV (card verification value) value is in an invalid format or was left blank (if set to required)',
        '900019' => 'INVALID_T_AMT Grand Total must equal > $0.00. Please check subtotal, shipping and tax values.',
        '900020' => 'INVALID_T_CODE Transaction Code value is in an invalid format or was left blank',
        '900021' => 'INVALID_T_AUTH Authorization code is in an invalid format or was left blank (required for Force transactions)',
        '900022' => 'INVALID_T_REFERENCE Reference value is in an invalid format or was left blank (Required for Force or Void by Reference)',
        '900023' => 'INVALID_T_TRACKDATA Track Data value is in an invalid format or was left blank (required for debit and retail transactions)',
        '900024' => 'INVALID_T_TRACKING_NUMBER Tracking number value is in an invalid format',
        '900025' => 'INVALID_T_CUSTOMER_NUMBER Customer number value is in an invalid format (used only for PCLIII transactions)',
        '900026' => 'INVALID_T_SHIPPING_COMPANY Shipping company value is in an invalid format',
        '900027' => 'INVALID_T_RECURRING Recurring value is in an invalid format (must be = 0 or 1)',
        '900028' => 'INVALID_T_RECURRING_TYPE Recurring value is in an invalid format',
        '900029' => 'INVALID_T_RECURRING_INTERVAL Recurring interval value is in an invalid format (must be numeric)',
        '900030' => 'INVALID_T_RECURRING_INDEFINITE Recurring indefinite value is in an invalid format or was left blank',
        '900031' => 'INVALID_T_RECURRING_TIMES_TO_PROCESS Recurring times to process value is in an invalid format (must be numeric)',
        '900032' => 'INVALID_T_RECURRING_NON_BUSINESS_DAYS Recurring non business days value is in an invalid format',
        '900033' => 'INVALID_T_RECURRING_GROUP Recurring Group was left blank or group not found',
        '900034' => 'INVALID_T_RECURRING_START_DATE Recurring start date value is in an invalid format or was left blank',
        '900035' => 'INVALID_T_PIN Pin number entered is incorrect (required for Pin-debit transactions)',
        '910000' => 'SERVICE NOT ALLOWED The transaction you are trying to submit is not allowed. Please contact Technical Support, 877-470-4001',
        '910001' => 'VISA NOT ALLOWED Visa card type transactions are not allowed.',
        '910002' => 'MASTERCARD NOT ALLOWED MasterCard card type transactions are not allowed.',
        '910003' => 'AMEX NOT ALLOWED American Express card type transactions are not allowed.',
        '910004' => 'DISCOVER NOT ALLOWED Discover card type transactions are not allowed.',
        '910005' => 'CARD TYPE NOT ALLOWED Card type transactions are not allowed.',
        '911911' => 'SECURITY VIOLATION M_id or M_key incorrect, XML Web Services not enabled in VT, or Post request is not at least 128-bit encrypted.',
        '920000' => 'ITEM NOT FOUND Item not found. Please contact Technical Support for assistance, 877-470-4001',
        '920001' => 'CERDIT VOL EXCEEDED No corresponding sale found within last 6 months, credit couldn’t be issued.',
        '920002' => 'AVS FAILURE Address Verification Service failure.',
        '920050' => 'DEBIT VOID NOT ALLOWED A debit transaction can not be voided.',
        '920051' => 'OPERATION NOT ALLOWED The operation requested is no supported on the gateway.',
        '999999' => 'ERROR REVIEW REPORTING Please log into the Virtual Terminal to review your transaction history to determine the status of this transaction.  If you need assistance, please contact Technical Support at 877-470-4001'
    );

    public $check_errors = array(
        '000000' => 'INTERNAL SERVER ERROR Server Error, Please contact Sage for assistance or more information',
        '900000' => 'INVALID T_ORDERNUM Order number value is in an invalid format',
        '900001' => 'INVALID C_NAME Name value is in an invalid format or was left blank',
        '900002' => 'INVALID C_ADDRESS Address value is in an invalid format or was left blank',
        '900003' => 'INVALID C_CITY City value is in an invalid format or was left blank',
        '900004' => 'INVALID C_STATE State value is in an invalid format or was left blank',
        '900005' => 'INVALID C_ZIP Zip code value is in an invalid format or was left blank',
        '900006' => 'INVALID C_COUNTRY Country value is in an invalid format or was left blank',
        '900007' => 'INVALID C_TELEPHONE Telephone value is in an invalid format or was left blank',
        '900008' => 'INVALID C_FAX Fax value is in an invalid format or was left blank',
        '900009' => 'INVALID C_EMAIL Email value is in an invalid format or was left blank',
        '900010' => 'INVALID C_SHIP_NAME Shipping address name value is in an invalid format',
        '900011' => 'INVALID_C_SHIP_ADDRESS Shipping Address value is in an invalid format',
        '900012' => 'INVALID_C_SHIP_CITY Shipping city value is in an invalid format',
        '900013' => 'INVALID_C_SHIP_STATE Shipping state value is in an invalid format',
        '900014' => 'INVALID_C_SHIP_ZIP Shipping zip code value is in an invalid format',
        '900015' => 'INVALID_C_SHIP_COUNTRY Shipping country value is in an invalid format',
        '900016' => 'INVALID_C_RTE Routing number value is in an invalid format',
        '900017' => 'INVALID_C_ACCT Account number value is in an invalid format',
        '900018' => 'INVALID_C_ACCT_TYPE The account type was not set properly',
        '900019' => 'INVALID_T_AMT Grand Total must equal > $0.00. Please check subtotal, shipping and tax values.',
        '900020' => 'INVALID_C_CUSTOMER_TYPE Customer type was set improperly.',
        '900021' => 'INVALID_T_CODE Transaction Code value is in an invalid format or was left blank',
        '900022' => 'INVALID_T_AUTH Authorization code is in an invalid format or was left blank (required for Force transactions)',
        '900023' => 'INVALID_T_REFERENCE Reference value is in an invalid format or was left blank (Required for Force or Void by Reference)',
        '900024' => 'INVALID_T_TRACKING_NUMBER Tracking number value is in an invalid format',
        '900025' => 'INVALID_T_ORIGINATOR_ID The originator ID value is in an invalid format',
        '900026' => 'INVALID C_SSN',
        '900027' => 'INVALID DL_STATE_CODE',
        '900028' => 'INVALID DL_NUMBER',
        '900029' => 'INVALID C_DOB',
        '900030' => 'INVALID C_EIN',
        '900031' => 'INVALID_T_RECURRING_TIMES_TO_PROCESS Recurring times to process value is in an invalid format (must be numeric)',
        '900032' => 'INVALID_T_RECURRING_NON_BUSINESS_DAYS Recurring non business days value is in an invalid format',
        '900033' => 'INVALID_T_RECURRING_GROUP Recurring Group was left blank or group not found',
        '900034' => 'INVALID_T_RECURRING_START_DATE Recurring start date value is in an invalid format or was left blank',
        '910000' => 'SERVICE NOT ALLOWED The transaction you are trying to submit is not allowed. Please contact Technical Support, 877-470-4001',
        '910001' => 'UNAUTHORIZED TRAN CLASS',
        '910002' => 'UNAUTHORIZED TRAN CLASS',
        '910003' => 'UNAUTHORIZED TRAN CLASS',
        '910004' => 'UNAUTHORIZED TRAN CLASS',
        '910005' => 'UNAUTHORIZED TRAN CLASS',
        '911911' => 'SECURITY VIOLATION M_id or M_key incorrect, XML Web Services not enabled in VT, or Post request is not at least 128-bit encrypted.',
        '920000' => 'ITEM NOT FOUND Item not found. Please contact Technical Support for assistance, 877-470-4001',
        '920001' => 'CERDIT VOL EXCEEDED No corresponding sale found within last 6 months, credit couldn’t be issued.',
        '999999' => 'ERROR REVIEW REPORTING Please log into the Virtual Terminal to review your transaction history to determine the status of this transaction.  If you need assistance, please contact Technical Support at 877-470-4001'
    );

    public $cvv_indicators = array(
        'M' => 'Match',
        'N' => 'CVV No Match',
        'P' => 'Not Processed',
        'S' => 'Merchant Has Indicated that CVV2 Is Not Present',
        'U' => 'Issuer is not certified and/or has not provided Visa Encryption Keys'
    );

    public $avs_indicators = array(
        'X' => 'Exact; match on address and 9 Digit Zip Code',
        'Y' => 'Yes; match on address and 5 Digit Zip Code',
        'A' => 'Address matches, Zip does not',
        'W' => '9 Digit Zip matches, address does not',
        'Z' => '5 Digit Zip matches, address does not',
        'N' => 'No; neither zip nor address match',
        'U' => 'Unavailable',
        'R' => 'Retry',
        'E' => 'Error',
        'S' => 'Service Not Supported',
        ' ' => 'Service Not Supported',
        // International
        'D' => 'Match Street Address and Postal Code match for International Transaction',
        'M' => 'Match Street Address and Postal Code match for International Transaction',
        'B' => 'Partial Match Street Address Match for International Transaction. Postal Code not verified due to incompatible formats',
        'P' => 'Partial Match Postal Codes match for International Transaction but street address not verified due to incompatible formats',
        'C' => 'No Match Street Address and Postal Code not verified for International Transaction due to incompatible formats',
        'I' => 'No Match Address Information not verified by International issuer',
        'G' => 'Not Supported Non-US. Issuer does not participate'
    );

    public $risk_indicators = array(
        '01' => 'Max Sale Exceeded',
        '02' => 'Min Sale Not Met',
        '03' => '1 Day Volume Exceeded',
        '04' => '1 Day Usage Exceeded',
        '05' => '3 Day Volume Exceeded',
        '06' => '3 Day Usage Exceeded',
        '07' => '15 Day Volume Exceeded',
        '08' => '15 Day Usage Exceeded',
        '09' => '30 Day Volume Exceeded',
        '10' => '30 Day Usage Exceeded',
        '11' => 'Stolen or Lost Card',
        '12' => 'AVS Failure'
    );

    public $field_map = array(
        'name'          => 'C_name',
        'name_first'    => 'C_first_name',
        'name_middle'   => 'C_middle_initial',
        'name_last'     => 'C_last_name',
        'address'       => 'C_address',
        'city'          => 'C_city',
        'state'         => 'C_state',
        'zip'           => 'C_zip',
        'country'       => 'C_country',
        'email'         => 'C_email',
        'phone_number'  => 'C_telephone',
        'check_account' => 'C_acct',
        'check_routing' => 'C_rte',
        'card_number'   => 'C_cardnumber',
        'expiration'    => 'C_exp',
        'card_ccv'      => 'C_ccv',
        'total'         => 'T_amt',
        'type'          => 'T_code',
        'reference'     => 'T_reference',
        'order_number'  => 'T_ordernum'
    );

    public $test_data = array(
        'C_name' => "John Doe",
        'C_address' => "1234 AnyStreet",
        'C_city' => "AnyTown",
        'C_state' => "TX",
        'C_zip' => "12345",
        'C_country' => "US",
        'C_email' => "none@none.com",
        'C_telephone' => "15032085455",
        'C_cardnumber' => "4111111111111111",
        'C_exp' => "0109",
        'C_ccv' => "648",
        'T_code' => "01",
        'T_amt' => "1.00"
    );

    public function __construct() {
        //call parent constructor
        parent::__construct();

        $this->domain = "www.sagepayments.net";
        $this->merchant_info['M_id'] = app::$config['m_id'];
        $this->merchant_info['M_key'] = app::$config['m_key'];

        //set up the specifics for connecting via ssl
        $this->request_params['port'] = 443;
        $this->request_params['scheme'] = 'tls://';
        $this->request_params['host'] = $this->domain;
    }

    /*
     * sage_payment::map_data()
     * ------------------------
     * Maps our standard data representation to sage's.
     */
    public function map_data($data) {
        if(!empty($data['type'])){
            if(array_key_exists($data['type'], $this->processing_codes)){
                $data['type'] = $this->processing_codes[$data['type']];
            }
        }
        foreach($data as $key => $value) {
            if(array_key_exists($key, $this->field_map)) {
                $data[$this->field_map[$key]] = $value;
                unset($data[$key]);
            }
        }
        return $data;
    }

    /*
     * sage_payment::bankcard_tranaction()
     * -----------------------------------
     *
     * Takes an array with the following keys:
     *   array (
     *     'C_name'             => "John Doe",
     *     'C_address'          => "1234 AnyStreet",
     *     'C_city'             => "AnyTown",
     *     'C_state'            => "TX",
     *     'C_zip'              => "12345",
     *     'C_country'          => "US",
     *     'C_email'            => "none@none.com",
     *     'C_telephone'        => "15032085455",
     *     'C_cardnumber'       => "4111111111111111",
     *     'C_exp'              => "0109",
     *     'C_ccv'              => "648",
     *
     *     'T_code'             => "01",    // See $this->processing_codes array
     *     'T_amt'              => "1.00",  // Amount to process
     *
     *     'T_tax'              => "",      // Optional tax amount
     *     'T_shipping'         => "",      // Optional shipping amount
     *     'T_reference'        => "",      // Unique reference, used for void or prior_auth trans
     *     'T_ordernum'         => "",      // Optional unique 1 - 20 digit order number
     *     'T_trackdata'        => "",      // Optional POS Track 2 data
     *
     *     'T_recurring'                    => '1',         // Recurring transaction
     *     'T_recurring_amount'             => "5.00",      // Recurring amount
     *     'T_recurring_type'               => '1',         // 1 monthly; 2 daily
     *     'T_recurring_interval'           => '1',         // Optional:
     *     'T_recurring_non_business_days'  => '',          // Optional: 0 after, 1 before, 2 same day
     *     'T_recurring_start_date'         => "MM/DD/YYYY",// Date to start recurring payments
     *     'T_recurring_indefinite'         => '1',         // Yes; 0 - Not indefinite
     *     'T_recurring_time_to_process'    => '',          // Number of time to process the recurring transaction
     *     'T_recurring_group'              => '',          // Group ID to add the recurring tranaction under (anything)
     *                                                      // Every recurring transaction needs to be under a group.
     *     'T_recurring_payment'            => ''           // Optional merchant initiated recurring tranaction.
     *   );
     */
    public function bankcard_transaction($data) {
        $status = false;
        $data = array_merge($this->merchant_info, $data);
        $res = parent::post('/cgi-bin/eftBankcard.dll?transaction', $data);
        if ($res != null) {
            $fields = $this->parse_fields($res);
            $status = array();
            $status['approval_indicator'] = $res[1]; //A is approved E(X) is declined/error.
            // Approved, Parse what we need.
            if ($status['approval_indicator'] == 'A') {
                $status['approved'] = true;
                $status['reference'] = substr($res, 46, 10);
                $status['order_number'] = $fields['order_number'];
            // EPIC FAIL! Get error codes + messages.
            } else {
                $status['approved'] = false;
                $status['approval_error_code'] = substr($res, 2, 6);
                if (array_key_exists($status['approval_error_code'], $this->card_errors)) {
                    $status['error_message'] = $this->card_errors[$status['approval_error_code']];
                }
                $status['approval_error_message'] = substr($res, 8, 32);
                $status['frontend_indicator'] = substr($res, 40, 2);
                $status['cvv_indicator'] = $res[42];
                if (array_key_exists($status['cvv_indicator'], $this->cvv_indicators)) {
                    $status['cvv_message'] = $this->cvv_indicators[$status['cvv_indicator']];
                }
                $status['avs_indicator'] = $res[43];
                if (array_key_exists($status['avs_indicator'], $this->avs_indicators)) {
                    $status['avs_message'] = $this->avs_indicators[$status['avs_indicator']];
                }
                $status['risk_indicator'] = substr($res, 44, 2);
                if (array_key_exists($status['risk_indicator'], $this->risk_indicators)) {
                    $status['risk_message'] = $this->risk_indicators[$status['risk_indicator']];
                }
            }
        }
        return $status;
    }

    /*
     * sage_payment::virtualcheck_tranaction()
     * ---------------------------------------
     * Takes an array with the following keys:
     *   array (
     *     'C_name'             => "John Doe",
     *     'C_first_name'       => "Jonn",
     *     'C_last_name'        => "Doe",
     *     'C_address'          => "1234 AnyStreet",
     *     'C_city'             => "AnyTown",
     *     'C_state'            => "TX",
     *     'C_zip'              => "12345",
     *     'C_country'          => "US",
     *     'C_email'            => "none@none.com",
     *     'C_telephone'        => "15032085455",
     *     'C_rte'              => "123456789",
     *     'C_acct'             => "9826378167943",
     *     'C_acct_type'        => "DDA",       // Required: DDA checking, SAV savings
     *     'C_customer_type'    => "PPD",       // Required: PPD Personal, CCD Commercial, WEB Internet, RCK returned, ARC, TEL
     *     'C_originator_id'    => "0123456789" // Optional: 10 digit originator ID; assigned by transaction class.
     *
     *     // WEB customer type required fields:
     *     'C_ssn'              => "555555555"  // Social security number
     *     'C_dl_state_code'    => "OR"         // 2 digit state abbreviation
     *     'C_dl_number'        => "13736481"   // Driver license number
     *     'C_dob               => "MM/DD/YYYY" // Date of birth
     *     'C_telephone'        => "15032085455"
     *
     *     // CCD customer type rquired fields:
     *     'C_ein'              => "475274863"  // Businesses Federal Tax Number
     *
     *
     *     'T_code'             => "01",        // See $this->processing_codes array
     *     'T_amt'              => "1.00",      // Amount to process
     *
     *     'T_tax'              => "",          // Optional tax amount
     *     'T_shipping'         => "",          // Optional shipping amount
     *     'T_reference'        => "",          // Unique reference, used for void or prior_auth trans
     *     'T_ordernum'         => "",          // Optional unique 1 - 20 digit order number
     *     'T_trackdata'        => "",          // Optional POS Track 2 data
     *
     *     'T_recurring'                    => '1',         // Recurring transaction
     *     'T_recurring_amount'             => "5.00",      // Recurring amount
     *     'T_recurring_type'               => '1',         // 1 monthly; 2 daily
     *     'T_recurring_interval'           => '1',         // Optional:
     *     'T_recurring_non_business_days'  => '',          // Optional: 0 after, 1 before, 2 same day
     *     'T_recurring_start_date'         => "MM/DD/YYYY",// Date to start recurring payments
     *     'T_recurring_indefinite'         => '1',         // Yes; 0 - Not indefinite
     *     'T_recurring_time_to_process'    => '',          // Number of time to process the recurring transaction
     *     'T_recurring_group'              => '',          // Group ID to add the recurring tranaction under (anything)
     *                                                      // Every recurring transaction needs to be under a group.
     *     'T_recurring_payment'            => ''           // Optional merchant initiated recurring tranaction.
     *
     *     // WEB customer type
     *
     *
     *     // CCD customer type
     *     'C_ein'              =>
     *   );
     */
    public function virtualcheck_transaction($data) {
        $check_needed = array('C_acct_type' => 'DDA', 'C_customer_type' => 'PPD');
        $data = array_merge($this->merchant_info, $data, $check_needed);
        $res = parent::post('/cgi-bin/eftVirtualCheck.dll?transaction', $data);
        if ($res != null) {
            $fields = $this->parse_fields($res);
            $status = array();
            $status['approval_indicator'] = $res[1]; //A is approved E(X) is declined/error.
            // Approved, Parse what we need.
            if ($status['approval_indicator'] == 'A') {
                $status['approved'] = true;
                $status['reference'] = substr($res, 42, 10);
                $status['order_number'] = $fields['order_number'];
            // EPIC FAIL! Get error codes + messages.
            } else {
                $status['approved'] = false;
                $status['approval_error_code'] = substr($res, 2, 6);
                if (array_key_exists($status['approval_error_code'], $this->check_errors)) {
                    $status['error_message'] = $this->check_errors[$status['approval_error_code']];
                }
                $status['approval_error_message'] = substr($res, 8, 32);
                $status['risk_indicator'] = substr($res, 40, 2);
                if (array_key_exists($status['risk_indicator'], $this->risk_indicators)) {
                    $status['risk_message'] = $this->risk_indicators[$status['risk_indicator']];
                }
            }
            // TODO: Parse extended information from response in second and third regions separated by chr(28);
        }
        return $status;
    }

    /*
     * sage_payment::parse_fields()
     * ----------------------------
     * Parses out variable length fields from response
     */
    private function parse_fields($raw) {
        $data = array();
        $parts = explode(chr(28), $raw);
        $data['fixed'] = array_shift($parts);
        $data['order_number'] = array_shift($parts);
        $data['variable'] = $parts;
        return $data;
    }
}
