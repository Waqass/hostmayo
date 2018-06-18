<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * Contains all details for the main contact.
 */
class Contact implements JsonSerializable
{
    /**
     * Your phone number in E.164 format. Must be provided if no fax number or email.
     * @required
     * @maps phone_number
     * @var string $phoneNumber public property
     */
    public $phoneNumber;

    /**
     * @todo Write general description for this property
     * @required
     * @maps custom_1
     * @var string $custom1 public property
     */
    public $custom1;

    /**
     * Your email. Must be provided if no phone number or fax number.
     * @var string|null $email public property
     */
    public $email;

    /**
     * You fax number. Must be provided if no phone number or email.
     * @maps fax_number
     * @var string|null $faxNumber public property
     */
    public $faxNumber;

    /**
     * Your first name.
     * @maps first_name
     * @var string|null $firstName public property
     */
    public $firstName;

    /**
     * @todo Write general description for this property
     * @maps address_line_1
     * @var string|null $addressLine1 public property
     */
    public $addressLine1;

    /**
     * @todo Write general description for this property
     * @maps address_line_2
     * @var string|null $addressLine2 public property
     */
    public $addressLine2;

    /**
     * @todo Write general description for this property
     * @maps address_city
     * @var string|null $addressCity public property
     */
    public $addressCity;

    /**
     * @todo Write general description for this property
     * @maps address_state
     * @var string|null $addressState public property
     */
    public $addressState;

    /**
     * @todo Write general description for this property
     * @maps address_postal_code
     * @var string|null $addressPostalCode public property
     */
    public $addressPostalCode;

    /**
     * @todo Write general description for this property
     * @maps address_country
     * @var string|null $addressCountry public property
     */
    public $addressCountry;

    /**
     * @todo Write general description for this property
     * @maps organization_name
     * @var string|null $organizationName public property
     */
    public $organizationName;

    /**
     * @todo Write general description for this property
     * @maps custom_2
     * @var string|null $custom2 public property
     */
    public $custom2;

    /**
     * @todo Write general description for this property
     * @maps custom_3
     * @var string|null $custom3 public property
     */
    public $custom3;

    /**
     * @todo Write general description for this property
     * @maps custom_4
     * @var string|null $custom4 public property
     */
    public $custom4;

    /**
     * Your last name
     * @maps last_name
     * @var string|null $lastName public property
     */
    public $lastName;

    /**
     * Constructor to set initial or default values of member properties
     * @param string $phoneNumber       Initialization value for $this->phoneNumber
     * @param string $custom1           Initialization value for $this->custom1
     * @param string $email             Initialization value for $this->email
     * @param string $faxNumber         Initialization value for $this->faxNumber
     * @param string $firstName         Initialization value for $this->firstName
     * @param string $addressLine1      Initialization value for $this->addressLine1
     * @param string $addressLine2      Initialization value for $this->addressLine2
     * @param string $addressCity       Initialization value for $this->addressCity
     * @param string $addressState      Initialization value for $this->addressState
     * @param string $addressPostalCode Initialization value for $this->addressPostalCode
     * @param string $addressCountry    Initialization value for $this->addressCountry
     * @param string $organizationName  Initialization value for $this->organizationName
     * @param string $custom2           Initialization value for $this->custom2
     * @param string $custom3           Initialization value for $this->custom3
     * @param string $custom4           Initialization value for $this->custom4
     * @param string $lastName          Initialization value for $this->lastName
     */
    public function __construct()
    {
        if (16 == func_num_args()) {
            $this->phoneNumber       = func_get_arg(0);
            $this->custom1           = func_get_arg(1);
            $this->email             = func_get_arg(2);
            $this->faxNumber         = func_get_arg(3);
            $this->firstName         = func_get_arg(4);
            $this->addressLine1      = func_get_arg(5);
            $this->addressLine2      = func_get_arg(6);
            $this->addressCity       = func_get_arg(7);
            $this->addressState      = func_get_arg(8);
            $this->addressPostalCode = func_get_arg(9);
            $this->addressCountry    = func_get_arg(10);
            $this->organizationName  = func_get_arg(11);
            $this->custom2           = func_get_arg(12);
            $this->custom3           = func_get_arg(13);
            $this->custom4           = func_get_arg(14);
            $this->lastName          = func_get_arg(15);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['phone_number']        = $this->phoneNumber;
        $json['custom_1']            = $this->custom1;
        $json['email']               = $this->email;
        $json['fax_number']          = $this->faxNumber;
        $json['first_name']          = $this->firstName;
        $json['address_line_1']      = $this->addressLine1;
        $json['address_line_2']      = $this->addressLine2;
        $json['address_city']        = $this->addressCity;
        $json['address_state']       = $this->addressState;
        $json['address_postal_code'] = $this->addressPostalCode;
        $json['address_country']     = $this->addressCountry;
        $json['organization_name']   = $this->organizationName;
        $json['custom_2']            = $this->custom2;
        $json['custom_3']            = $this->custom3;
        $json['custom_4']            = $this->custom4;
        $json['last_name']           = $this->lastName;

        return $json;
    }
}
