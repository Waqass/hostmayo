<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * List of ReferralAccounts
 */
class ReferralAccountList implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @maps referral_account
     * @var ReferralAccount[] $referralAccount public property
     */
    public $referralAccount;

    /**
     * Constructor to set initial or default values of member properties
     * @param array $referralAccount Initialization value for $this->referralAccount
     */
    public function __construct()
    {
        if (1 == func_num_args()) {
            $this->referralAccount = func_get_arg(0);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['referral_account'] = $this->referralAccount;

        return $json;
    }
}
