<?php
/**
 * Advanced Raja-SMS API PHP Library
 *
 * MIT License
 *
 * Copyright (c) 2018 Steeve Andrian Salim
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) 2018, Steeve Andrian Salim
 * @filesource
 */

// ------------------------------------------------------------------------

namespace Steevenz;

// ------------------------------------------------------------------------

use O2System\Curl;
use O2System\Kernel\Http\Message\Uri;
use O2System\Spl\DataStructures\SplArrayObject;
use O2System\Spl\Iterators\ArrayIterator;
use O2System\Spl\Traits\Collectors\ConfigCollectorTrait;
use O2System\Spl\Traits\Collectors\ErrorCollectorTrait;

/**
 * RajaSMS
 *
 * @version       1.0.0
 * @author        Steeven Andrian Salim
 */
class Rajasms
{
    use ConfigCollectorTrait;
    use ErrorCollectorTrait;

    /**
     * Rajasms::$deliveryStatuses
     *
     * List of RajaSMS delivery status by code numbers.
     *
     * @var array
     */
    public $deliveryStatusCodes = [
        1  => 'Schedule',
        2  => 'Sent',
        3  => 'Delivered Success',
        4  => 'Delivered Error',
        5  => 'System Failed',
        6  => 'Saldo Minus/Expired',
        7  => 'Reject',
        8  => 'System Error',
        9  => 'Duplicate Message',
        10 => 'Delivered Success Backup',
    ];

    public $globalErrorMessagesCodes = [
        10 => 'Success',
        20 => 'Json Post Error',
        30 => 'ApiKey not register',
        40 => 'Ip address not register',
        50 => 'Expired Balance',
        55 => 'Maximum Data',
    ];

    /**
     * Rajasms::$response
     *
     * RajaSMS original response.
     *
     * @access  protected
     * @type    mixed
     */
    protected $response;

    // ------------------------------------------------------------------------

    /**
     * Rajasms::__construct
     *
     * @param array $config
     *
     * @access  public
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'serverIp'    => null,
            'apiKey'      => null,
            'callbackUrl' => null,
            'sendingTime' => null,
        ];

        $this->setConfig(array_merge($defaultConfig, $config));
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setServerIp
     *
     * Set RajaSMS API Server Ip Address.
     *
     * @param string $serverIp RajaSMS API Server Ip Address.
     *
     * @access  public
     * @return  static
     */
    public function setServerIp($serverIp)
    {
        $this->setConfig('serverIp', $serverIp);

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setApiKey
     *
     * Set RajaSMS API Key.
     *
     * @param string $apiKey RajaSMS API Key
     *
     * @access  public
     * @return  static
     */
    public function setApiKey($apiKey)
    {
        $this->setConfig('apiKey', $apiKey);

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setCallbackUrl
     *
     * Set RajaSMS API Callback Url.
     *
     * @param string $callbackUrl RajaSMS API Callback Url
     *
     * @access  public
     * @return  static
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->setConfig('callbackUrl', $callbackUrl);

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::setSendingTime
     *
     * Set RajaSMS Sending Time.
     *
     * @param string $sendingTime Format: yyyy-mm-dd hh-mm-ss or empty
     *
     * @access  public
     * @return  static
     */
    public function setSendingTime($sendingTime)
    {
        if (is_string($sendingTime)) {
            $sendingTime = strtotime($sendingTime);
        }

        $this->setConfig('sendingTime', date('Y-m-d H:m:s', $sendingTime));

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
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadPhpExtensionCallException
     */
    protected function request($path, $params = [], $type = 'GET')
    {
        $path = 'sms/' . $path;

        // default params
        if (empty($this->config[ 'serverIp' ])) {
            throw new \InvalidArgumentException('RajaSMS: Server Ip Address is not set!');
        }

        if (empty($this->config[ 'apiKey' ])) {
            throw new \InvalidArgumentException('RajaSMS: API Key is not set');
        } else {
            $params[ 'apikey' ] = $this->config[ 'apiKey' ];
        }

        if ( ! empty($this->config[ 'callbackUrl' ]) and isset($params[ 'datapacket' ])) {
            $params[ 'callbackurl' ] = $this->config[ 'callbackUrl' ];
        }

        $uri = (new Uri($this->config[ 'serverIp' ]))->withPath($path);

        $request = new Curl\Request();

        $request->setConnectionTimeout(500);

        switch ($type) {
            default:
            case 'GET':
                $this->response = $request->setUri($uri)->get($params);
                break;

            case 'POST':
                $request->addHeader('content-type', 'application/json');
                $this->response = $request->setUri($uri)->post($params, 'JSON');
                break;
        }

        if (false !== ($error = $this->response->getError())) {
            $this->addError($error->code, $error->message);
        } elseif ($body = $this->response->getBody()) {
            return $body;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::send
     *
     * Send SMS
     *
     * @param string $msisdn  MSISDN Number
     * @param string $message SMS Text
     * @param bool   $masking Use SMS Masking
     *
     * @access  public
     * @return  mixed
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadPhpExtensionCallException
     */
    public function send($msisdn, $message, $masking = false)
    {
        $params[ 'datapacket' ] = [];

        if (is_array($msisdn)) {
            $i = 1;
            foreach ($msisdn as $number) {
                if (false !== ($sendPackageData = $this->buildSendPackageData([
                        'msisdn'      => $number,
                        'message'     => $message,
                        'sendingTime' => isset($this->config[ 'sendingTime' ]) ? $this->config[ 'sendingTime' ] : null,
                    ]))) {
                    array_push($params[ 'datapacket' ], $sendPackageData);
                }

                if ($i === 1000) {
                    break;
                }

                $i++;
            }
        } elseif (false !== ($sendPackageData = $this->buildSendPackageData([
                'msisdn'      => $msisdn,
                'message'     => $message,
                'sendingTime' => isset($this->config[ 'sendingTime' ]) ? $this->config[ 'sendingTime' ] : null,
            ]))) {
            array_push($params[ 'datapacket' ], $sendPackageData);
        }

        $credit = $this->getCreditBalance($masking);

        if (($credit->balance > 500) and (strtotime($credit->expired) > time()) and count($params[ 'datapacket' ]) > 0) {

            if ($masking === true) {
                $result = $this->request('api_sms_masking_send_json.php', $params, 'POST');
            } else {
                $result = $this->request('api_sms_reguler_send_json.php', $params, 'POST');
            }

            if (isset($result[ 'sending_respon' ])) {
                if (isset($result[ 'sending_respon' ][ 0 ])) {
                    if ($result[ 'sending_respon' ][ 0 ][ 'globalstatus' ] > 10) {
                        $this->addError($result[ 'sending_respon' ][ 0 ][ 'globalstatus' ],
                            $result[ 'sending_respon' ][ 0 ][ 'globalstatustext' ]);

                        return false;
                    }
                }

                if (isset($result[ 'sending_respon' ][ 0 ][ 'datapacket' ])) {
                    $reports = new ArrayIterator();

                    foreach ($result[ 'sending_respon' ][ 0 ][ 'datapacket' ] as $respon) {
                        $reports = new SplArrayObject([
                            'sendingId' => $respon[ 'sendingid' ],
                            'number'    => $respon[ 'number' ],
                            'sending'   => new SplArrayObject([
                                'status'  => $respon[ 'sendingstatus' ],
                                'message' => $respon[ 'sendingstatustext' ],
                            ]),
                            'price'     => $respon[ 'price' ],
                        ]);
                    }

                    return $reports;
                }
            }

            return $result;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::buildSendPackageData
     *
     * @param array $data
     *
     * @return array|bool
     */
    protected function buildSendPackageData(array $data)
    {
        if (preg_match('/^(62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $data[ 'msisdn' ]) == 1) {
            $data[ 'msisdn' ] = '0' . substr($data[ 'msisdn' ], 2);
        } elseif (preg_match('/^(\+62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $data[ 'msisdn' ]) == 1) {
            $data[ 'msisdn' ] = '0' . substr($data[ 'msisdn' ], 3);
        }

        if (preg_match('/^(0[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $data[ 'msisdn' ]) == 1) {
            return [
                'number'          => trim($data[ 'msisdn' ]),
                'message'         => urlencode(stripslashes(utf8_encode($data[ 'message' ]))),
                'sendingdatetime' => isset($data[ 'sendingTime' ]) ? $data[ 'sendingTime' ] : null,
            ];
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getCreditBalance
     *
     * Get RajaSMS account credit balance.
     *
     * @access  public
     * @return  mixed
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadPhpExtensionCallException
     */
    public function getCreditBalance($masking = false)
    {
        if ($masking === true) {
            $result = $this->request('api_sms_masking_balance_json.php', [], 'POST');
        } else {
            $result = $this->request('api_sms_reguler_balance_json.php', [], 'POST');
        }

        if (isset($result[ 'balance_respon' ])) {
            if (isset($result[ 'balance_respon' ][ 0 ][ 'globalstatus' ])) {
                if ($result[ 'balance_respon' ][ 0 ][ 'globalstatus' ] > 10) {
                    $this->addError($result[ 'balance_respon' ][ 0 ][ 'globalstatus' ],
                        $result[ 'balance_respon' ][ 0 ][ 'globalstatustext' ]);

                    return false;
                }

                $credit = new SplArrayObject([
                    'balance' => $result[ 'balance_respon' ][ 0 ][ 'Balance' ],
                    'expired' => $result[ 'balance_respon' ][ 0 ][ 'Expired' ],
                ]);

                $result = $credit;
            }
        }

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getReports
     *
     * Get sms report delivery status.
     *
     * @return mixed Returns FALSE if failed.
     */
    public function getReports()
    {
        $response = json_decode(file_get_contents('php://input'), true);

        if ( ! empty($response)) {
            $reports = new ArrayIterator();

            foreach ($response[ 'status_respon' ] as $respon) {
                $reports[] = new SplArrayObject([
                    'sendingId' => $respon[ 'sendingid' ],
                    'number'    => $respon[ 'number' ],
                    'delivery'  => new SplArrayObject([
                        'status'  => $respon[ 'deliverystatus' ],
                        'message' => $respon[ 'deliverystatustext' ],
                    ]),
                ]);
            }

            return $reports;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Rajasms::getResponse
     *
     * Get original response object.
     *
     * @access  public
     * @return  \O2System\Curl\Response|bool Returns FALSE if failed.
     */
    public function getResponse()
    {
        return $this->response;
    }
}