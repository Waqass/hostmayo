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
class ResellerAccount extends Account implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @maps reseller_account
     * @var Account $resellerAccount public property
     */
    public $resellerAccount;

    /**
     * Constructor to set initial or default values of member properties
     * @param Account $resellerAccount Initialization value for $this->resellerAccount
     */
    public function __construct()
    {
        if (1 == func_num_args()) {
            $this->resellerAccount = func_get_arg(0);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['reseller_account'] = $this->resellerAccount;
        $json = array_merge($json, parent::jsonSerialize());

        return $json;
    }
}
