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
class ContactList implements JsonSerializable
{
    /**
     * @todo Write general description for this property
     * @required
     * @maps list_id
     * @var string $listId public property
     */
    public $listId;

    /**
     * @todo Write general description for this property
     * @maps list_name
     * @var string|null $listName public property
     */
    public $listName;

    /**
     * @todo Write general description for this property
     * @maps list_email_id
     * @var string|null $listEmailId public property
     */
    public $listEmailId;

    /**
     * @todo Write general description for this property
     * @maps contacts_count
     * @var integer|null $contactsCount public property
     */
    public $contactsCount;

    /**
     * Constructor to set initial or default values of member properties
     * @param string  $listId        Initialization value for $this->listId
     * @param string  $listName      Initialization value for $this->listName
     * @param string  $listEmailId   Initialization value for $this->listEmailId
     * @param integer $contactsCount Initialization value for $this->contactsCount
     */
    public function __construct()
    {
        if (4 == func_num_args()) {
            $this->listId        = func_get_arg(0);
            $this->listName      = func_get_arg(1);
            $this->listEmailId   = func_get_arg(2);
            $this->contactsCount = func_get_arg(3);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['list_id']        = $this->listId;
        $json['list_name']      = $this->listName;
        $json['list_email_id']  = $this->listEmailId;
        $json['contacts_count'] = $this->contactsCount;

        return $json;
    }
}
