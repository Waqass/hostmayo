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
class MmsMessage implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @var object $messages public property
     */
    public $messages;

    /**
     * @todo Write general description for this property
     * @required
     * @maps media_file
     * @var string $mediaFile public property
     */
    public $mediaFile;

    /**
     * Constructor to set initial or default values of member properties
     * @param object $messages  Initialization value for $this->messages
     * @param string $mediaFile Initialization value for $this->mediaFile
     */
    public function __construct()
    {
        if (2 == func_num_args()) {
            $this->messages  = func_get_arg(0);
            $this->mediaFile = func_get_arg(1);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['messages']   = $this->messages;
        $json['media_file'] = $this->mediaFile;

        return $json;
    }
}
