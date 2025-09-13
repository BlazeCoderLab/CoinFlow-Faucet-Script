<?php

/**
 * FaucetPay PHP Library
 * Version: v1.0.1
 * 
 * This library provides an interface to FaucetPay.io's API
 * allowing operations such as sending payments, checking addresses,
 * getting balance, supported currencies, and payout list.
 * 
 * Changelog:
 * v1.0.1 - Added currency parameter to checkAddress() function
 * v1.0   - First full release including checkAddress()
 * v0.02  - Timeout support, improved error handling, referral payout tweaks, removed silent curl retry
 * v0.01  - Initial version
 * 
 * Credits: Based on original library by FaucetBOX.com
 */

/**
 * Dummy class for backward compatibility with FaucetBOX library.
 * Extends FaucetPay with identical constructor parameters.
 */
class FaucetBOX extends FaucetPay {
    public function __construct($api_key, $currency = "BTC", $disable_curl = false, $verify_peer = true) {
        parent::__construct($api_key, $currency, $disable_curl, $verify_peer);
    }
}

/**
 * Main FaucetPay class to communicate with FaucetPay.io API.
 */
class FaucetPay
{
    /**
     * @var string API key for FaucetPay.io authentication
     */
    protected $api_key;

    /**
     * @var string Default currency code (e.g., "BTC")
     */
    protected $currency;

    /**
     * @var int Timeout duration for API requests in seconds
     */
    protected $timeout;

    /**
     * @var bool Whether to disable cURL and use PHP stream context instead
     */
    protected $disable_curl;

    /**
     * @var bool Enable SSL peer verification in API requests
     */
    protected $verify_peer;

    /**
     * @var bool Warning flag if cURL is disabled but response was received anyway
     */
    public $curl_warning;

    /**
     * @var int|null Status code of the last API response
     */
    public $last_status = null;

    /**
     * @var string Base URL for FaucetPay.io API
     */
    protected $api_base = "https://faucetpay.io/api/v1/";

    /**
     * Constructor sets essential parameters and configures timeout.
     * 
     * @param string $api_key FaucetPay.io API key.
     * @param string $currency Default currency code, default "BTC".
     * @param bool $disable_curl If true, disables cURL and uses PHP streams instead.
     * @param bool $verify_peer Enable SSL peer verification. Default true.
     * @param int|null $timeout Optional timeout for requests in seconds.
     */
    public function __construct($api_key, $currency = "BTC", $disable_curl = false, $verify_peer = true, $timeout = null) {
        $this->api_key = $api_key;
        $this->currency = $currency;
        $this->disable_curl = $disable_curl;
        $this->verify_peer = $verify_peer;
        $this->curl_warning = false;
        $this->setTimeout($timeout);
    }

    /**
     * Set timeout for API calls. If null, defaults to min(max_execution_time/2, default_socket_timeout).
     * 
     * @param int|null $timeout Timeout in seconds.
     */
    public function setTimeout($timeout) {
        if ($timeout === null) {
            $socket_timeout = (int) ini_get('default_socket_timeout'); 
            $script_timeout = (int) ini_get('max_execution_time');
            $timeout = min($script_timeout / 2, $socket_timeout);
        }
        $this->timeout = $timeout;
    }

    /**
     * Executes an API request using PHP streams (file_get_contents with stream context).
     * Used as fallback if cURL is disabled.
     * 
     * @param string $method API endpoint method (e.g., "send").
     * @param array $params POST parameters for API call.
     * @return string JSON encoded API response.
     */
    public function __execPHP($method, $params = array()) {
        $params = array_merge($params, array(
            "api_key" => $this->api_key,
            "currency" => $this->currency
        ));
        $opts = array(
            "http" => array(
                "method" => "POST",
                "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                "content" => http_build_query($params),
                "timeout" => $this->timeout,
            ),
            "ssl" => array(
                "verify_peer" => $this->verify_peer
            )
        );
        $ctx = stream_context_create($opts);
        $fp = fopen($this->api_base . $method, 'rb', false, $ctx);

        if (!$fp) {
            // Connection failed to API endpoint
            return json_encode(array(
                'status' => 503,
                'message' => 'Connection to FaucetPay failed, please try again later',
            ));
        }
        
        $response = stream_get_contents($fp);

        if ($response && !$this->disable_curl) {
            // Warning: a response received while disable_curl is false (unexpected)
            $this->curl_warning = true;
        }
        fclose($fp);

        return $response;
    }

    /**
     * Executes an API call and returns decoded JSON response as an associative array.
     * Decides internally whether to use cURL or PHP streams based on $this->disable_curl.
     * 
     * @param string $method API method name.
     * @param array $params POST parameters array.
     * @return array Decoded API response.
     */
    public function __exec($method, $params = array()) {
        $this->last_status = null;
        
        if ($this->disable_curl) {
            $response = $this->__execPHP($method, $params);
        } else {
            $response = $this->__execCURL($method, $params);
        }

        $responseDecoded = json_decode($response, true);
        if ($responseDecoded) {
            $this->last_status = $responseDecoded['status'] ?? null;
            return $responseDecoded;
        } else {
            // Invalid JSON response fallback
            $this->last_status = null;
            return array(
                'status' => 502,
                'message' => 'Invalid response',
            );
        }
    }

    /**
     * Executes an API request using cURL.
     * 
     * @param string $method API endpoint method.
     * @param array $params POST parameters.
     * @return string JSON encoded API response or error message.
     */
    public function __execCURL($method, $params = array()) {
        $params = array_merge($params, array(
            "api_key" => $this->api_key,
            "currency" => $this->currency
        ));
        $ch = curl_init($this->api_base . $method);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_peer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$this->timeout);

        $response = curl_exec($ch);
        if (!$response) {
            // Connection error
            return json_encode(array(
                'status' => 504,
                'message' => 'Connection error',
            ));
        }
        curl_close($ch);
        return $response;
    }

    /**
     * Sends cryptocurrency payment to a FaucetPay.io account.
     * 
     * @param string $to Recipient account address.
     * @param float|int $amount Amount in satoshis or smallest currency unit.
     * @param bool $referral Optional flag to mark payment as referral earnings.
     * @param string $ip_address Optional client IP address.
     * @return array Result with success status, message, HTML for UI, balance info, raw JSON response.
     */
    public function send($to, $amount, $referral = false, $ip_address = "") {
        $referralFlag = ($referral === true) ? 'true' : 'false';
        
        $response = $this->__exec("send", array(
            "to" => $to,
            "amount" => $amount,
            "referral" => $referralFlag,
            "ip_address" => $ip_address
        ));

        if (isset($response["status"]) && $response["status"] == 200) {
            // Successful payment
            return array(
                'success' => true,
                'message' => 'Payment sent to your address using FaucetPay.io',
                'html' => '<div class="alert alert-success">' . 
                           htmlspecialchars(rtrim(rtrim(sprintf("%.8f", $amount / 100000000), '0'), '.')) . 
                           ' ' . $this->currency . ' was sent to <a target="_blank" href="https://faucetpay.io/balance/' .
                           rawurlencode($to) .
                           '">your account at FaucetPay.io</a>.</div>',
                'balance' => $response["balance"] ?? null,
                'balance_bitcoin' => $response["balance_bitcoin"] ?? null,
                'response' => json_encode($response)
            );
        }
        
        // If user address is not linked to FaucetPay account (status 456)
        if (isset($response["status"]) && $response["status"] == 456) {
            return array(
                'success' => false,
                'message' => $response['message'] ?? 'Address must be linked to FaucetPay account',
                'html' => '<div class="alert alert-danger">Before you can receive payments at FaucetPay.io with this address you must link it to an account. ' .
                          '<a href="http://faucetpay.io/signup" target="_blank">Create an account at FaucetPay.io</a> and link your address, then come back and claim again.</div>',
                'response' => json_encode($response)
            );
        }

        // General error case
        if (isset($response["message"])) {
            return array(
                'success' => false,
                'message' => $response["message"],
                'html' => '<div class="alert alert-danger">' . htmlspecialchars($response["message"]) . '</div>',
                'response' => json_encode($response)
            );
        }

        // Unknown error fallback
        return array(
            'success' => false,
            'message' => 'Unknown error.',
            'html' => '<div class="alert alert-danger">Unknown error.</div>',
            'response' => json_encode($response)
        );
    }

    /**
     * Sends referral earnings payment.
     * Alias to send() with referral flag set true.
     * 
     * @param string $to Recipient account address.
     * @param float|int $amount Amount in smallest unit.
     * @param string $ip_address Optional IP address.
     * @return array Payment result.
     */
    public function sendReferralEarnings($to, $amount, $ip_address = "") {
        return $this->send($to, $amount, true, $ip_address);
    }

    /**
     * Fetches the latest payouts from FaucetPay.io.
     * 
     * @param int $count Number of recent payouts to return.
     * @return array API response with payout data.
     */
    public function getPayouts($count) {
        return $this->__exec("payouts", array("count" => $count));
    }

    /**
     * Returns the list of supported currencies on FaucetPay.io.
     * 
     * @return array List of supported currencies.
     */
    public function getCurrencies() {
        $response = $this->__exec("currencies");
        return $response['currencies'] ?? array();
    }

    /**
     * Returns the balance of the default currency account linked with API key.
     * 
     * @return array Balance data including amounts and status.
     */
    public function getBalance() {
        return $this->__exec("balance");
    }

    /**
     * Checks if an address is valid and linked to FaucetPay.io for a specific currency.
     * 
     * @param string $address Cryptocurrency address to check.
     * @param string $currency Currency code to check address against. Default "BTC".
     * @return array API response with address validation and info.
     */
    public function checkAddress($address, $currency = "BTC") {
        return $this->__exec("checkaddress", array('address' => $address, 'currency' => $currency));
    }
}
