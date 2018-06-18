<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * Base model for Fax Messages
 */
class FaxMessage implements JsonSerializable
{
    /**
     * Your method of sending e.g. 'wordpress', 'php', 'c#'.
     * @required
     * @var string $source public property
     */
    public $source;

    /**
     * Recipient fax number in E.164 format.
     * @required
     * @var string $to public property
     */
    public $to;

    /**
     * Your list ID if sending to a whole list. Can be used instead of 'to'.
     * @maps list_id
     * @var integer|null $listId public property
     */
    public $listId;

    /**
     * Your sender id. Must be a valid fax number.
     * @var string|null $from public property
     */
    public $from;

    /**
     * Leave blank for immediate delivery. Your schedule time in unix format http://help.clicksend.com/what-is-a-unix-timestamp
     * @var integer|null $schedule public property
     */
    public $schedule;

    /**
     * Your reference. Will be passed back with all replies and delivery reports.
     * @maps custom_string
     * @var string|null $customString public property
     */
    public $customString;

    /**
     * Recipient country.
     * @var string|null $country public property
     */
    public $country;

    /**
     * An email address where the reply should be emailed to.
     * @maps from_email
     * @var string|null $fromEmail public property
     */
    public $fromEmail;

    /**
     * Constructor to set initial or default values of member properties
     * @param string  $source       Initialization value for $this->source
     * @param string  $to           Initialization value for $this->to
     * @param integer $listId       Initialization value for $this->listId
     * @param string  $from         Initialization value for $this->from
     * @param integer $schedule     Initialization value for $this->schedule
     * @param string  $customString Initialization value for $this->customString
     * @param string  $country      Initialization value for $this->country
     * @param string  $fromEmail    Initialization value for $this->fromEmail
     */
    public function __construct()
    {
        if (8 == func_num_args()) {
            $this->source       = func_get_arg(0);
            $this->to           = func_get_arg(1);
            $this->listId       = func_get_arg(2);
            $this->from         = func_get_arg(3);
            $this->schedule     = func_get_arg(4);
            $this->customString = func_get_arg(5);
            $this->country      = func_get_arg(6);
            $this->fromEmail    = func_get_arg(7);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['source']        = $this->source;
        $json['to']            = $this->to;
        $json['list_id']       = $this->listId;
        $json['from']          = $this->from;
        $json['schedule']      = $this->schedule;
        $json['custom_string'] = $this->customString;
        $json['country']       = $this->country;
        $json['from_email']    = $this->fromEmail;

        return $json;
    }
}
