<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * Complete account details needed for the user.
 */
class Account implements JsonSerializable
{
    /**
     * Your username
     * @required
     * @var string $username public property
     */
    public $username;

    /**
     * Your password
     * @required
     * @var string $password public property
     */
    public $password;

    /**
     * Your phone number in E.164 format.
     * @required
     * @maps user_phone
     * @var string $userPhone public property
     */
    public $userPhone;

    /**
     * Your email
     * @required
     * @maps user_email
     * @var string $userEmail public property
     */
    public $userEmail;

    /**
     * Your first name
     * @required
     * @maps user_first_name
     * @var string $userFirstName public property
     */
    public $userFirstName;

    /**
     * Your last name
     * @required
     * @maps user_last_name
     * @var string $userLastName public property
     */
    public $userLastName;

    /**
     * Your delivery to value.
     * @required
     * @maps account_name
     * @var string $accountName public property
     */
    public $accountName;

    /**
     * Your country
     * @required
     * @var string $country public property
     */
    public $country;

    /**
     * Constructor to set initial or default values of member properties
     * @param string $username      Initialization value for $this->username
     * @param string $password      Initialization value for $this->password
     * @param string $userPhone     Initialization value for $this->userPhone
     * @param string $userEmail     Initialization value for $this->userEmail
     * @param string $userFirstName Initialization value for $this->userFirstName
     * @param string $userLastName  Initialization value for $this->userLastName
     * @param string $accountName   Initialization value for $this->accountName
     * @param string $country       Initialization value for $this->country
     */
    public function __construct()
    {
        if (8 == func_num_args()) {
            $this->username      = func_get_arg(0);
            $this->password      = func_get_arg(1);
            $this->userPhone     = func_get_arg(2);
            $this->userEmail     = func_get_arg(3);
            $this->userFirstName = func_get_arg(4);
            $this->userLastName  = func_get_arg(5);
            $this->accountName   = func_get_arg(6);
            $this->country       = func_get_arg(7);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['username']        = $this->username;
        $json['password']        = $this->password;
        $json['user_phone']      = $this->userPhone;
        $json['user_email']      = $this->userEmail;
        $json['user_first_name'] = $this->userFirstName;
        $json['user_last_name']  = $this->userLastName;
        $json['account_name']    = $this->accountName;
        $json['country']         = $this->country;

        return $json;
    }
}
