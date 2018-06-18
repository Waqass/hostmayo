<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Models;

use JsonSerializable;

/**
 * VoiceMessage fields: source, to, list_id, body, lang, voice, schedule, custom_string, country
 */
class VoiceMessage implements JsonSerializable
{
    /**
     * Your phone number in E.164 format.
     * @required
     * @var string $to public property
     */
    public $to;

    /**
     * Biscuit uv3nlCOjRk croissant chocolate lollipop chocolate muffin.
     * @required
     * @var string $body public property
     */
    public $body;

    /**
     * Either 'female' or 'male'.
     * @required
     * @var string $voice public property
     */
    public $voice;

    /**
     * Your reference. Will be passed back with all replies and delivery reports.
     * @required
     * @maps custom_string
     * @var string $customString public property
     */
    public $customString;

    /**
     * The country of the recipient.
     * @required
     * @var string $country public property
     */
    public $country;

    /**
     * Your method of sending e.g. 'wordpress', 'php', 'c#'.
     * @var string|null $source public property
     */
    public $source;

    /**
     * Your list ID if sending to a whole list. Can be used instead of 'to'.
     * @maps list_id
     * @var integer|null $listId public property
     */
    public $listId;

    /**
     * au (string, required) - See section on available languages.
     * @var string|null $lang public property
     */
    public $lang;

    /**
     * Leave blank for immediate delivery. Your schedule time in unix format http://help.clicksend.com/what-is-a-unix-timestamp
     * @var integer|null $schedule public property
     */
    public $schedule;

    /**
     * Constructor to set initial or default values of member properties
     * @param string  $to           Initialization value for $this->to
     * @param string  $body         Initialization value for $this->body
     * @param string  $voice        Initialization value for $this->voice
     * @param string  $customString Initialization value for $this->customString
     * @param string  $country      Initialization value for $this->country
     * @param string  $source       Initialization value for $this->source
     * @param integer $listId       Initialization value for $this->listId
     * @param string  $lang         Initialization value for $this->lang
     * @param integer $schedule     Initialization value for $this->schedule
     */
    public function __construct()
    {
        if (9 == func_num_args()) {
            $this->to           = func_get_arg(0);
            $this->body         = func_get_arg(1);
            $this->voice        = func_get_arg(2);
            $this->customString = func_get_arg(3);
            $this->country      = func_get_arg(4);
            $this->source       = func_get_arg(5);
            $this->listId       = func_get_arg(6);
            $this->lang         = func_get_arg(7);
            $this->schedule     = func_get_arg(8);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['to']            = $this->to;
        $json['body']          = $this->body;
        $json['voice']         = $this->voice;
        $json['custom_string'] = $this->customString;
        $json['country']       = $this->country;
        $json['source']        = $this->source;
        $json['list_id']       = $this->listId;
        $json['lang']          = $this->lang;
        $json['schedule']      = $this->schedule;

        return $json;
    }
}
