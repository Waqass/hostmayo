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
class SmsMessage implements JsonSerializable
{
    /**
     * Your method of sending e.g. 'wordpress', 'php', 'c#'.
     * @required
     * @var string $source public property
     */
    public $source;

    /**
     * Your sender id - more info: http://help.clicksend.com/SMS/what-is-a-sender-id-or-sender-number.
     * @required
     * @var string $from public property
     */
    public $from;

    /**
     * Your message.
     * @required
     * @var string $body public property
     */
    public $body;

    /**
     * Recipient phone number in E.164 format.
     * @required
     * @var string $to public property
     */
    public $to;

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
     * Your list ID if sending to a whole list. Can be used instead of 'to'.
     * @maps list_id
     * @var integer|null $listId public property
     */
    public $listId;

    /**
     * Recipient country.
     * @var string|null $country public property
     */
    public $country;

    /**
     * An email address where the reply should be emailed to. If omitted, the reply will be emailed back to the user who sent the outgoing SMS.
     * @maps from_email
     * @var string|null $fromEmail public property
     */
    public $fromEmail;

    /**
     * Constructor to set initial or default values of member properties
     * @param string  $source       Initialization value for $this->source
     * @param string  $from         Initialization value for $this->from
     * @param string  $body         Initialization value for $this->body
     * @param string  $to           Initialization value for $this->to
     * @param integer $schedule     Initialization value for $this->schedule
     * @param string  $customString Initialization value for $this->customString
     * @param integer $listId       Initialization value for $this->listId
     * @param string  $country      Initialization value for $this->country
     * @param string  $fromEmail    Initialization value for $this->fromEmail
     */
    public function __construct()
    {
        if (9 == func_num_args()) {
            $this->source       = func_get_arg(0);
            $this->from         = func_get_arg(1);
            $this->body         = func_get_arg(2);
            $this->to           = func_get_arg(3);
            $this->schedule     = func_get_arg(4);
            $this->customString = func_get_arg(5);
            $this->listId       = func_get_arg(6);
            $this->country      = func_get_arg(7);
            $this->fromEmail    = func_get_arg(8);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['source']        = $this->source;
        $json['from']          = $this->from;
        $json['body']          = $this->body;
        $json['to']            = $this->to;
        $json['schedule']      = $this->schedule;
        $json['custom_string'] = $this->customString;
        $json['list_id']       = $this->listId;
        $json['country']       = $this->country;
        $json['from_email']    = $this->fromEmail;

        return $json;
    }
}
