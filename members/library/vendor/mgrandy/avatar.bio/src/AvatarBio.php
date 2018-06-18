<?php

namespace AvatarBio;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

class AvatarBio {

    private $url             = 'https://www.avatar.bio/avatar/';
    private $backGroundColor = 'DAF1FF';
    private $textColor       = '555555';
    private $text            = '';
    private $size            = 40;
    private $email           = '';

    public function getSiteUrl()
    {
        return $this->url;
    }

    public function setEmail($email)
    {

        $validator = new EmailValidator();
        if ($validator->isValid($email, new RFCValidation())) {
            $this->email = $email;
        } else {
            throw new \Exception('Invalid Email Address: ' . $email);
        }

    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getBackGroundColor()
    {
        return $this->backGroundColor;
    }

    public function setBackGroundColor($color)
    {
        $this->backGroundColor = $color;
    }

    public function getTextColor()
    {
        return $this->textColor;
    }

    public function setTextColor($color)
    {
        $this->textColor = $color;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    /**
     * Return the Avatar.bio image URL based on the provided email address
     *
     * @return string The URL of the avatar
     */
    public function getImageURL()
    {
        if ($this->getEmail() == '') {
            throw new \Exception('No email address has been provided');
        }

        return $this->getSiteUrl()
            . $this->getEmail()
            . '?bc=' . $this->getBackGroundColor()
            . '&tc=' . $this->getTextColor()
            . ($this->getText() != ''  ?  '&t=' . $this->getText() : '')
            . '&s=' . $this->getSize();
    }
}