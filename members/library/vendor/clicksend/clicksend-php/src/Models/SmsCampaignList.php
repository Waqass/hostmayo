<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * List of Sms Campaigns
 */
class SmsCampaignList implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @maps sms_campaigns
     * @var SmsCampaign $smsCampaigns public property
     */
    public $smsCampaigns;

    /**
     * Constructor to set initial or default values of member properties
     * @param SmsCampaign $smsCampaigns Initialization value for $this->smsCampaigns
     */
    public function __construct()
    {
        if (1 == func_num_args()) {
            $this->smsCampaigns = func_get_arg(0);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['sms_campaigns'] = $this->smsCampaigns;

        return $json;
    }
}
