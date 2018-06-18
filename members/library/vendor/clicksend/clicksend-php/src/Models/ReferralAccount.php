<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * Referral Account - for use by ReferralAccountList
 */
class ReferralAccount implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @maps referral_rule_id
     * @var string $referralRuleId public property
     */
    public $referralRuleId;

    /**
     * @todo Write general description for this property
     * @required
     * @maps refered_user_id
     * @var string $referedUserId public property
     */
    public $referedUserId;

    /**
     * @todo Write general description for this property
     * @required
     * @maps date_referred
     * @var string $dateReferred public property
     */
    public $dateReferred;

    /**
     * @todo Write general description for this property
     * @required
     * @maps percentage_referral
     * @var string $percentageReferral public property
     */
    public $percentageReferral;

    /**
     * Constructor to set initial or default values of member properties
     * @param string $referralRuleId     Initialization value for $this->referralRuleId
     * @param string $referedUserId      Initialization value for $this->referedUserId
     * @param string $dateReferred       Initialization value for $this->dateReferred
     * @param string $percentageReferral Initialization value for $this->percentageReferral
     */
    public function __construct()
    {
        if (4 == func_num_args()) {
            $this->referralRuleId     = func_get_arg(0);
            $this->referedUserId      = func_get_arg(1);
            $this->dateReferred       = func_get_arg(2);
            $this->percentageReferral = func_get_arg(3);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['referral_rule_id']    = $this->referralRuleId;
        $json['refered_user_id']     = $this->referedUserId;
        $json['date_referred']       = $this->dateReferred;
        $json['percentage_referral'] = $this->percentageReferral;

        return $json;
    }
}
