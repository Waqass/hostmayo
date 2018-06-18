<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * Campaign Model for SMS
 */
class SmsCampaign implements JsonSerializable
{
    /**
     * Your list id.
     * @required
     * @maps list_id
     * @var object $listId public property
     */
    public $listId;

    /**
     * Your campaign name.
     * @required
     * @var string $name public property
     */
    public $name;

    /**
     * Your campaign message.
     * @required
     * @var string $body public property
     */
    public $body;

    /**
     * Your schedule timestamp.
     * @required
     * @var string $schedule public property
     */
    public $schedule;

    /**
     * Your sender id - more info: http://help.clicksend.com/SMS/what-is-a-sender-id-or-sender-number.
     * @var string|null $from public property
     */
    public $from;

    /**
     * Constructor to set initial or default values of member properties
     * @param object $listId   Initialization value for $this->listId
     * @param string $name     Initialization value for $this->name
     * @param string $body     Initialization value for $this->body
     * @param string $schedule Initialization value for $this->schedule
     * @param string $from     Initialization value for $this->from
     */
    public function __construct()
    {
        if (5 == func_num_args()) {
            $this->listId   = func_get_arg(0);
            $this->name     = func_get_arg(1);
            $this->body     = func_get_arg(2);
            $this->schedule = func_get_arg(3);
            $this->from     = func_get_arg(4);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['list_id']  = $this->listId;
        $json['name']     = $this->name;
        $json['body']     = $this->body;
        $json['schedule'] = $this->schedule;
        $json['from']     = $this->from;

        return $json;
    }
}
