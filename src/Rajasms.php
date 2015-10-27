<?php
/**
 * RajaSMS
 *
 * RajaSMS API PHP Class
 *
 * This content is released under GNU General Public License v2.0 License
 *
 * Copyright (c) 2015, Steeve Andrian
 *
 * @author         Steeve Andrian
 * @copyright      Copyright (c) 2015, Steeve Andrian.
 * @filesource
 */

// ------------------------------------------------------------------------

namespace Steevenz;

// ------------------------------------------------------------------------

use O2System\CURL;
use O2System\CURL\Interfaces\Method;

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
    protected $_api_url = 'http://162.211.84.203/sms/';

    /**
     * API Key
     *
     * @access  protected
     * @type    string
     */
    protected $_api_key = NULL;

    /**
     * RajaSMS Username Account
     *
     * @access  protected
     * @type    string
     */
    protected $_username = NULL;

    /**
     * O2System CURL Resource
     *
     * @access  protected
     * @type    \O2System\CURL
     */
    protected $_curl;

    // ------------------------------------------------------------------------

    /**
     * Class Constructor
     *
     * @param string $username
     * @param string $api_key
     *
     * @access  public
     */
    public function __construct( $username = NULL, $api_key = NULL )
    {
        if( isset( $username ) )
        {
            if( is_array( $username ) )
            {
                if( isset( $username[ 'username' ] ) )
                {
                    $this->_username = $username[ 'username' ];
                }

                if( isset( $username[ 'api_key' ] ) )
                {
                    $this->_api_key = $username[ 'api_key' ];
                }
            }
        }

        if( isset( $api_key ) AND empty( $this->_api_key ) )
        {
            $this->_api_key = $api_key;
        }

        /*
         * ------------------------------------------------------
         *  Initialized O2System CURL Class
         * ------------------------------------------------------
         */
        $this->_curl = new CURL();
    }

    /**
     * Set Username
     *
     * @param   string $username RajaSMS Username Account
     *
     * @access  public
     * @return  object
     */
    public function set_username( $username )
    {
        $this->_username = $username;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Set API Key
     *
     * @param   string $api_key RajaSMS API Key
     *
     * @access  public
     * @return  object
     */
    public function set_api_key( $api_key )
    {
        $this->_api_key = $api_key;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * API Request
     *
     * @param string $path
     * @param array  $params
     * @param string $type
     *
     * @access  protected
     * @return  mixed
     */
    protected function _request( $path, $params = array(), $type = Method::GET )
    {
        $headers = array();

        // default params
        if( empty( $this->_api_key ) )
        {
            throw new \InvalidArgumentException( 'RajaSMS: Invalid API Key' );
        }
        else
        {
            $params[ 'key' ] = $this->_api_key;
        }

        if( empty( $this->_username ) )
        {
            throw new \InvalidArgumentException( 'RajaSMS: Please set your username account' );
        }
        else
        {
            $params[ 'username' ] = $this->_username;
        }

        switch( $type )
        {
            default:
            case 'GET':
                $response = $this->_curl->get( $this->_api_url, $path, $params, $headers );
                break;

            case 'POST':
                $headers[ 'content-type' ] = 'application/x-www-form-urlencoded';
                $response = $this->_curl->post( $this->_api_url, $path, $params, $headers );
                break;
        }

        if( $response->meta->http_code === 200 )
        {
            if( ! empty( $response->body ) )
            {
                $x_body = explode( '|', $response->body );

                if( $x_body[ 0 ] == 1 )
                {
                    return $x_body[ 1 ];
                }
                else
                {
                    return $x_body;
                }
            }
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * Send SMS
     *
     * @param   string $phone   Phone Number
     * @param   string $text    SMS Text
     * @param   bool   $masking Use SMS Masking
     *
     * @access  public
     * @return  mixed
     */
    public function send( $phone, $text, $masking = FALSE )
    {
        if( preg_match( '/^(62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $phone ) == 1 )
        {
            $phone = '0' . substr( $phone, 2 );
        }
        elseif( preg_match( '/^(\+62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $phone ) == 1 )
        {
            $phone = '0' . substr( $phone, 3 );
        }

        if( preg_match( '/^(0[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/', $phone ) == 1 )
        {
            $text = trim( strval( $text ) );
            $text = ( strlen( $text ) > 160 ) ? substr( $text, 0, 160 ) : $text;

            $credit = $this->get_credit();

            if( ( $credit->balance > 500 ) AND ( $credit->expired > time() ) )
            {
                $params[ 'number' ] = $phone;
                $params[ 'message' ] = urlencode( $text );

                if( $masking === TRUE )
                {
                    $result = $this->_request( 'smsmasking.php', $params );
                }
                else
                {
                    $result = $this->_request( 'smsreguler.php', $params );
                }

                if( is_array( $result ) )
                {
                    $status = new \stdClass();

                    if( $result[ 0 ] == 0 )
                    {
                        $status->success = TRUE;
                        $status->id_sms = $result[ 1 ];
                        $status->message = NULL;
                    }
                    else
                    {
                        $status->success = FALSE;
                        $status->id_sms = NULL;
                        $status->message = $result[ 1 ];
                    }

                    return $status;
                }

                return $result;
            }
        }
        else
        {
            throw new \InvalidArgumentException( 'Rajasms: Invalid Phone Number' );
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Get Credit
     *
     * Get RajaSMS account credit balance
     *
     * @access  public
     * @return  mixed
     */
    public function get_credit()
    {
        $result = $this->_request( 'smssaldo.php' );

        if( is_array( $result ) )
        {
            $credit = new \stdClass();

            foreach( $result as $key => $value )
            {
                if( is_numeric( $value ) )
                {
                    $credit->balance = $value;
                }
                elseif( preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value ) )
                {
                    $credit->expired = strtotime( $value );
                }
            }

            $result = $credit;
        }

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Get Report
     *
     * Check status report of sms status by SMS ID
     *
     * @param      $id_sms   SMS ID
     * @param bool $masking  Use SMS Masking
     *
     * @return bool|mixed|\stdClass
     */
    public function get_report( $id_sms, $masking = FALSE )
    {
        $id_sms = intval( trim( strval( $id_sms ) ) );

        if( ! empty( $id_sms ) )
        {
            $params[ 'id' ] = $id_sms;

            if( $masking === TRUE )
            {
                $result = $this->_request( 'smsmaskingreport.php', $params );
            }
            else
            {
                $result = $this->_request( 'smsregulerreport.php', $params );
            }

            if( is_array( $result ) )
            {
                $status = new \stdClass();

                if( $result[ 0 ] == 0 )
                {
                    $status->success = TRUE;
                    $status->message = $result[ 1 ];
                }
                else
                {
                    $status->success = FALSE;
                    $status->message = $result[ 1 ];
                }

                return $status;
            }

            return $result;
        }

        return FALSE;
    }
}