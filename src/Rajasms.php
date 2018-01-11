<?php
/**
 * Advanced Raja-SMS API PHP Library
 *
 * Copyright (C) 2018  Steeve Andrian Salim (steevenz)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) 2018, Steeve Andrian Salim
 * @since          Version 2.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

namespace Steevenz;

// ------------------------------------------------------------------------

use O2System\Curl;
use O2System\Kernel\Http\Message\Uri;

/**
 * RajaSMS
 *
 * @version       1.0.0
 * @author        Steeven Andrian Salim
 */
class Rajasms
{
    /**
     * API URL
     *
     * @access  protected
     * @type    string
     */
    protected $apiUrl = 'http://162.211.84.203/sms/';

    /**
     * API Key
     *
     * @access  protected
     * @type    string
     */
    protected $apiKey = null;

    /**
     * RajaSMS Username Account
     *
     * @access  protected
     * @type    string
     */
    protected $username = null;

    /**
     * Rajasms::$response
     *
     * Rajasms response.
     *
     * @access  protected
     * @type    mixed
     */
    protected $response;

    /**
     * Rajasms::$errors
     *
     * Rajasms errors.
     *
     * @access  protected
     * @type    array
     */
    protected $errors = [];

    // ------------------------------------------------------------------------

    /**
     * Rajasms::__construct
     *
     * @param string $username
     * @param string $apiKey
     *
     * @access  public
     */
    public function __construct($username = null, $apiKey = null, $apiUrl = null)
    {
        if (isset($username)) {
            if (is_array($username)) {
                if (isset($username[ 'username' ])) {
                    $this->setUsername($username);
                }

                if (isset($username[ 'api_key' ])) {
                    $apiKey = $username[ 'api_key' ];
                }

                if (isset($username[ 'api_url' ])) {
                    $apiUrl = $username[ 'api_url' ];
                }
            } else {
                $this->setUsername($username);
            }
        }

        if (isset($apiKey)) {
            $this->setApiKey($apiKey);
        }

        if (isset($apiUrl)) {
            $this->setApiUrl($apiUrl);
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setUsername
     *
     * Set Username.
     *
     * @param   string $username RajaSMS Username Account
     *
     * @access  public
     * @return  static
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setApiKey
     *
     * Set API Key.
     *
     * @param   string $apiKey RajaSMS API Key
     *
     * @access  public
     * @return  object
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setApiUrl
     *
     * Set API Url.
     *
     * @param   string $apiUrl RajaSMS API Url
     *
     * @access  public
     * @return  object
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::request
     *
     * Call API request.
     *
     * @param string $path
     * @param array  $params
     * @param string $type
     *
     * @access  protected
     * @return  mixed
     */
    protected function request($path, $params = [], $type = 'GET')
    {
        $path = 'sms/' . $path;

        // default params
        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('RajaSMS: Invalid API Key');
        } else {
            $params[ 'key' ] = $this->apiKey;
        }

        if (empty($this->username)) {
            throw new \InvalidArgumentException('RajaSMS: Please set your username account');
        } else {
            $params[ 'username' ] = $this->username;
        }

        $uri = (new Uri($this->apiUrl))->withPath($path);

        $request = new Curl\Request();
        $request->setHeaders([
            'key' => $this->apiKey,
        ]);

        $request->setConnectionTimeout(500);

        switch ($type) {
            default:
            case 'GET':
                $this->response = $request->setUri($uri)->get($params);
                break;

            case 'POST':
                $request->addHeader('content-type', 'application/x-www-form-urlencoded');
                $this->response = $request->setUri($uri)->post($params);
                break;
        }

        if (false !== ($error = $this->response->getError())) {
            $this->errors = $error;
        } else {
            if( $body = $this->response->getBody() ) {
                $xbody = explode('|', $body);

                if ($xbody[ 0 ] == 1) {
                    return $xbody[ 1 ];
                } else {
                    return $xbody;
                }
            }
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::send
     *
     * Send SMS
     *
     * @param   string $msisdn  MSISDN Number
     * @param   string $text    SMS Text
     * @param   bool   $masking Use SMS Masking
     *
     * @access  public
     * @return  mixed
     */
    public function send($msisdn, $text, $masking = false)
    {
        if (preg_match('/^(62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $msisdn) == 1) {
            $msisdn = '0' . substr($msisdn, 2);
        } elseif (preg_match('/^(\+62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $msisdn) == 1) {
            $msisdn = '0' . substr($msisdn, 3);
        }

        if (preg_match('/^(0[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $msisdn) == 1) {
            $text = trim(strval($text));
            $text = (strlen($text) > 160) ? substr($text, 0, 160) : $text;

            $credit = $this->getCreditBalance();

            if (($credit->balance > 500) AND ($credit->expired > time())) {
                $params[ 'number' ] = $msisdn;
                $params[ 'message' ] = urlencode($text);

                if ($masking === true) {
                    $result = $this->request('smsmasking.php', $params);
                } else {
                    $result = $this->request('smsreguler.php', $params);
                }

                if (is_array($result)) {
                    $status = new \stdClass();

                    if ($result[ 0 ] == 0) {
                        $status->success = true;
                        $status->id_sms = $result[ 1 ];
                        $status->message = null;
                    } else {
                        $status->success = false;
                        $status->id_sms = null;
                        $status->message = $result[ 1 ];
                    }

                    return $status;
                }

                return $result;
            }
        } else {
            throw new \InvalidArgumentException('Rajasms: Invalid Phone Number');
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getCreditBalance
     *
     * Get RajaSMS account credit balance.
     *
     * @access  public
     * @return  mixed
     */
    public function getCreditBalance()
    {
        $result = $this->request('smssaldo.php');

        if (is_array($result)) {
            $credit = new \stdClass();

            foreach ($result as $key => $value) {
                if (is_numeric($value)) {
                    $credit->balance = $value;
                } elseif (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value)) {
                    $credit->expired = strtotime($value);
                }
            }

            $result = $credit;
        }

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getReport
     *
     * Check status report of sms status by SMS ID.
     *
     * @param      $idSms   SMS ID
     * @param bool $masking Use SMS Masking
     *
     * @return bool|mixed|\stdClass
     */
    public function getReport($idSms, $masking = false)
    {
        $idSms = intval(trim(strval($idSms)));

        if ( ! empty($idSms)) {
            $params[ 'id' ] = $idSms;

            if ($masking === true) {
                $result = $this->request('smsmaskingreport.php', $params);
            } else {
                $result = $this->request('smsregulerreport.php', $params);
            }

            if (is_array($result)) {
                $status = new \stdClass();

                if ($result[ 0 ] == 0) {
                    $status->success = true;
                    $status->message = $result[ 1 ];
                } else {
                    $status->success = false;
                    $status->message = $result[ 1 ];
                }

                return $status;
            }

            return $result;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getResponse
     *
     * Get original response object.
     *
     * @param   string $offset Response Offset Object
     *
     * @access  public
     * @return  \O2System\Curl\Response|bool Returns FALSE if failed.
     */
    public function getResponse()
    {
        return $this->response;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getErrors
     *
     * Get errors request.
     *
     * @access  public
     * @return  array|bool Returns FALSE if there is no errors.
     */
    public function getErrors()
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        return false;
    }
}