<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * Accounts that are maintained under a main account
 */
class Subaccount implements JsonSerializable
{
    /**
     * Your new api username.
     * @required
     * @maps api_username
     * @var array $apiUsername public property
     */
    public $apiUsername;

    /**
     * Your new password
     * @required
     * @var string $password public property
     */
    public $password;

    /**
     * Your new email.
     * @required
     * @var string $email public property
     */
    public $email;

    /**
     * Your phone number in E.164 format.
     * @required
     * @maps phone_number
     * @var string $phoneNumber public property
     */
    public $phoneNumber;

    /**
     * Your firstname
     * @required
     * @maps first_name
     * @var string $firstName public property
     */
    public $firstName;

    /**
     * Your lastname
     * @required
     * @maps last_name
     * @var string $lastName public property
     */
    public $lastName;

    /**
     * Your access users flag value, must be 1 or 0.
     * @maps access_users
     * @var bool|null $accessUsers public property
     */
    public $accessUsers;

    /**
     * Your access billing flag value, must be 1 or 0.
     * @maps access_billing
     * @var bool|null $accessBilling public property
     */
    public $accessBilling;

    /**
     * Your access reporting flag value, must be 1 or 0.
     * @maps access_reporting
     * @var bool|null $accessReporting public property
     */
    public $accessReporting;

    /**
     * Your access contacts flag value, must be 1 or 0.
     * @maps access_contacts
     * @var bool|null $accessContacts public property
     */
    public $accessContacts;

    /**
     * Your access settings flag value, must be 1 or 0.
     * @maps access_settings
     * @var bool|null $accessSettings public property
     */
    public $accessSettings;

    /**
     * Constructor to set initial or default values of member properties
     * @param array  $apiUsername     Initialization value for $this->apiUsername
     * @param string $password        Initialization value for $this->password
     * @param string $email           Initialization value for $this->email
     * @param string $phoneNumber     Initialization value for $this->phoneNumber
     * @param string $firstName       Initialization value for $this->firstName
     * @param string $lastName        Initialization value for $this->lastName
     * @param bool   $accessUsers     Initialization value for $this->accessUsers
     * @param bool   $accessBilling   Initialization value for $this->accessBilling
     * @param bool   $accessReporting Initialization value for $this->accessReporting
     * @param bool   $accessContacts  Initialization value for $this->accessContacts
     * @param bool   $accessSettings  Initialization value for $this->accessSettings
     */
    public function __construct()
    {
        switch (func_num_args()) {
            case 11:
                $this->apiUsername     = func_get_arg(0);
                $this->password        = func_get_arg(1);
                $this->email           = func_get_arg(2);
                $this->phoneNumber     = func_get_arg(3);
                $this->firstName       = func_get_arg(4);
                $this->lastName        = func_get_arg(5);
                $this->accessUsers     = func_get_arg(6);
                $this->accessBilling   = func_get_arg(7);
                $this->accessReporting = func_get_arg(8);
                $this->accessContacts  = func_get_arg(9);
                $this->accessSettings  = func_get_arg(10);
                break;

            default:
                $this->accessUsers = TRUE;
                $this->accessBilling = TRUE;
                $this->accessReporting = TRUE;
                $this->accessContacts = FALSE;
                $this->accessSettings = TRUE;
                break;
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['api_username']     = $this->apiUsername;
        $json['password']         = $this->password;
        $json['email']            = $this->email;
        $json['phone_number']     = $this->phoneNumber;
        $json['first_name']       = $this->firstName;
        $json['last_name']        = $this->lastName;
        $json['access_users']     = $this->accessUsers;
        $json['access_billing']   = $this->accessBilling;
        $json['access_reporting'] = $this->accessReporting;
        $json['access_contacts']  = $this->accessContacts;
        $json['access_settings']  = $this->accessSettings;

        return $json;
    }
}
