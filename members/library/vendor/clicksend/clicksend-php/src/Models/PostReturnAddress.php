<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * @todo Write general description for this model
 */
class PostReturnAddress extends Address implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @var Address $address public property
     */
    public $address;

    /**
     * Constructor to set initial or default values of member properties
     * @param Address $address Initialization value for $this->address
     */
    public function __construct()
    {
        if (1 == func_num_args()) {
            $this->address = func_get_arg(0);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['address'] = $this->address;
        $json = array_merge($json, parent::jsonSerialize());

        return $json;
    }
}
