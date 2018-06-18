<?php

/*************************************************************************************************

This class allows for easy connection to Authorize.Net's Customer Information (CIM) API. More
information about the CIM API can be found at http://developer.authorize.net/api/cim/.

PHP version 5

LICENSE: This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see http://www.gnu.org/licenses/.

@category   Ecommerce
@package    AuthnetCIM
@author     John Conde <johnny@johnconde.net>
@copyright  2008 - 2010 John Conde
@license    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
@version    2.0

**************************************************************************************************/

class AuthnetCIMException extends Exception {}

class AuthnetCIM
{
    const USE_PRODUCTION_SERVER  = 0;
    const USE_DEVELOPMENT_SERVER = 1;

    const EXCEPTION_CURL = 10;

    private $params  = array();
    private $items   = array();
    private $success = false;
    private $error   = true;

    private $login;
    private $transkey;
    private $xml;
    private $ch;
    private $response;
    private $url;
    private $resultCode;
    private $code;
    private $text;
    private $profileToken;
    private $profileId;
    private $validation;
    private $paymentProfileId;
    private $results;

    public function __construct($login, $transkey, $test = self::USE_PRODUCTION_SERVER)
    {
        $this->login    = trim($login);
        $this->transkey = trim($transkey);
        if (empty($this->login) || empty($this->transkey)) {
            trigger_error('You have not configured your '.__CLASS__.'() login credentials properly.', E_USER_ERROR);
        }

        $this->test = (bool) $test;
        $subdomain  = ($this->test) ? 'apitest' : 'api';
        $this->url = 'https://'.$subdomain.'.authorize.net/xml/v1/request.api';

        $this->params['customerType']     = 'individual';
        $this->params['validationMode']   = 'liveMode';
        $this->params['taxExempt']        = 'false';
        $this->params['recurringBilling'] = 'false';
    }

    public function __destruct()
    {
        if (isset($this->ch)) {
            curl_close($this->ch);
        }
    }

    public function __toString()
    {
        if (!$this->params) {
            return (string) $this;
        }
        $output  = '<table summary="Authnet Results" id="authnet">'."\n";
        $output .= '<tr>'."\n\t\t".'<th colspan="2"><b>Outgoing Parameters</b></th>'."\n".'</tr>'."\n";
        foreach ($this->params as $key => $value) {
            $output .= "\t".'<tr>'."\n\t\t".'<td><b>'.$key.'</b></td>';
            $output .= '<td>'.$value.'</td>'."\n".'</tr>'."\n";
        }

        $output .= '</table>'."\n";
        if (!empty($this->xml)) {
            $output .= 'XML: ';
            $output .= htmlentities($this->xml);
        }
        return $output;
    }

    private function process()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
    	curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($this->ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
    	curl_setopt($this->ch, CURLOPT_HEADER, 0);
    	curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->xml);
    	curl_setopt($this->ch, CURLOPT_POST, 1);
    	curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->response = curl_exec($this->ch);
        if ($this->response) {
            $this->parseResults();
            if ($this->resultCode === 'Ok') {
                $this->success = true;
                $this->error   = false;
            } else {
                $this->success = false;
                $this->error   = true;
            }
            curl_close($this->ch);
            unset($this->ch);
        } else {
            throw new AuthnetCIMException('Connection error: '.curl_error($this->ch).' ('.curl_errno($this->ch).')', self::EXCEPTION_CURL);
        }
    }

    public function createCustomerProfile($use_profiles = false, $type = 'credit')
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
            $this->xml .= '
                          <profile>
                              <merchantCustomerId>'.$this->params['merchantCustomerId'].'</merchantCustomerId>
                              <description>'.$this->params['description'].'</description>
                              <email>'.$this->params['email'].'</email>';

        if ($use_profiles == true) {
            $this->xml .= '
                              <paymentProfiles>
                                  <customerType>'.$this->params['customerType'].'</customerType>
                                  <billTo>
                                      <firstName>'.$this->params['billToFirstName'].'</firstName>
                                      <lastName>'.$this->params['billToLastName'].'</lastName>
                                      <company>'.$this->params['billToCompany'].'</company>
                                      <address>'.$this->params['billToAddress'].'</address>
                                      <city>'.$this->params['billToCity'].'</city>
                                      <state>'.$this->params['billToState'].'</state>
                                      <zip>'.$this->params['billToZip'].'</zip>
                                      <country>'.$this->params['billToCountry'].'</country>
                                      <phoneNumber>'.$this->params['billToPhoneNumber'].'</phoneNumber>
                                      <faxNumber>'.$this->params['billToFaxNumber'].'</faxNumber>
                                  </billTo>
                                  <payment>';
            if ($type === 'credit') {
                $this->xml .= '
                                      <creditCard>
                                          <cardNumber>'.$this->params['cardNumber'].'</cardNumber>
                                          <expirationDate>'.$this->params['expirationDate'].'</expirationDate>
                                      </creditCard>';
            } elseif ($type === 'check') {
                $this->xml .= '
                                      <bankAccount>
                                          <accountType>'.$this->params['accountType'].'</accountType>
                                          <nameOnAccount>'.$this->params['nameOnAccount'].'</nameOnAccount>
                                          <echeckType>'.$this->params['echeckType'].'</echeckType>
                                          <bankName>'.$this->params['bankName'].'</bankName>
                                          <routingNumber>'.$this->params['routingNumber'].'</routingNumber>
                                          <accountNumber>'.$this->params['accountNumber'].'</accountNumber>
                                      </bankAccount>
                                      <driversLicense>
                                          <dlState>'.$this->params['dlState'].'</dlState>
                                          <dlNumber>'.$this->params['dlNumber'].'</dlNumber>
                                          <dlDateOfBirth>'.$this->params['dlDateOfBirth'].'</dlDateOfBirth>
                                      </driversLicense>';
            }
            $this->xml .= '
                                  </payment>
                              </paymentProfiles>
                              <shipToList>
                                  <firstName>'.$this->params['shipToFirstName'].'</firstName>
                                  <lastName>'.$this->params['shipToLastName'].'</lastName>
                                  <company>'.$this->params['shipToCompany'].'</company>
                                  <address>'.$this->params['shipToAddress'].'</address>
                                  <city>'.$this->params['shipToCity'].'</city>
                                  <state>'.$this->params['shipToState'].'</state>
                                  <zip>'.$this->params['shipToZip'].'</zip>
                                  <country>'.$this->params['shipToCountry'].'</country>
                                  <phoneNumber>'.$this->params['shipToPhoneNumber'].'</phoneNumber>
                                  <faxNumber>'.$this->params['shipToFaxNumber'].'</faxNumber>
                              </shipToList>';
        }
            $this->xml .= '
                          </profile>
                      </createCustomerProfileRequest>';
        $this->process();
    }

    public function createCustomerPaymentProfile($type = 'credit')
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <paymentProfile>
                              <customerType>'.$this->params['customerType'].'</customerType>
                              <billTo>
                                  <firstName>'.$this->params['billToFirstName'].'</firstName>
                                  <lastName>'.$this->params['billToLastName'].'</lastName>
                                  <company>'.$this->params['billToCompany'].'</company>
                                  <address>'.$this->params['billToAddress'].'</address>
                                  <city>'.$this->params['billToCity'].'</city>
                                  <state>'.$this->params['billToState'].'</state>
                                  <zip>'.$this->params['billToZip'].'</zip>
                                  <country>'.$this->params['billToCountry'].'</country>
                                  <phoneNumber>'.$this->params['billToPhoneNumber'].'</phoneNumber>
                                  <faxNumber>'.$this->params['billToFaxNumber'].'</faxNumber>
                              </billTo>
                              <payment>';
        if ($type === 'credit') {
            $this->xml .= '
                                  <creditCard>
                                      <cardNumber>'.$this->params['cardNumber'].'</cardNumber>
                                      <expirationDate>'.$this->params['expirationDate'].'</expirationDate>
                                  </creditCard>';
        } elseif ($type === 'check') {
            $this->xml .= '
                                  <bankAccount>
                                      <accountType>'.$this->params['accountType'].'</accountType>
                                      <nameOnAccount>'.$this->params['nameOnAccount'].'</nameOnAccount>
                                      <echeckType>'.$this->params['echeckType'].'</echeckType>
                                      <bankName>'.$this->params['bankName'].'</bankName>
                                      <routingNumber>'.$this->params['routingNumber'].'</routingNumber>
                                      <accountNumber>'.$this->params['accountNumber'].'</accountNumber>
                                  </bankAccount>
                                  <driversLicense>
                                      <dlState>'.$this->params['dlState'].'</dlState>
                                      <dlNumber>'.$this->params['dlNumber'].'</dlNumber>
                                      <dlDateOfBirth>'.$this->params['dlDateOfBirth'].'</dlDateOfBirth>
                                  </driversLicense>';
        }
        $this->xml .= '
                              </payment>
                          </paymentProfile>
                          <validationMode>'.$this->params['validationMode'].'</validationMode>
                      </createCustomerPaymentProfileRequest>';
        $this->process();
    }

    public function createCustomerShippingAddress()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <createCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <address>
                              <firstName>'.$this->params['shipToFirstName'].'</firstName>
                              <lastName>'.$this->params['shipToLastName'].'</lastName>
                              <company>'.$this->params['shipToCompany'].'</company>
                              <address>'.$this->params['shipToAddress'].'</address>
                              <city>'.$this->params['shipToCity'].'</city>
                              <state>'.$this->params['shipToState'].'</state>
                              <zip>'.$this->params['shipToZip'].'</zip>
                              <country>'.$this->params['shipToCountry'].'</country>
                              <phoneNumber>'.$this->params['shipToPhoneNumber'].'</phoneNumber>
                              <faxNumber>'.$this->params['shipToFaxNumber'].'</faxNumber>
                          </address>
                      </createCustomerShippingAddressRequest>';
        $this->process();
    }

    public function createCustomerProfileTransaction($type = 'profileTransAuthCapture')
    {
        $types = array('profileTransAuthCapture', 'profileTransCaptureOnly','profileTransAuthOnly','profileTransRefund');
        if (!in_array($type, $types)) {
            trigger_error('createCustomerProfileTransaction() parameter must be"profileTransAuthCapture", "profileTransCaptureOnly", "profileTransAuthOnly", "profileTransRefund", or empty', E_USER_ERROR);
        }

        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <transaction>
                              <'.$type.'>
                                  <amount>'.$this->params['amount'].'</amount>';
        if (isset($this->params['taxAmount'])) {
            $this->xml .= '
                                  <tax>
                                       <amount>'.$this->params['taxAmount'].'</amount>
                                       <name>'.$this->params['taxName'].'</name>
                                       <description>'.$this->params['taxDescription'].'</description>
                                  </tax>';
        }
        if (isset($this->params['shipAmount'])) {
            $this->xml .= '
                                  <shipping>
                                       <amount>'.$this->params['shipAmount'].'</amount>
                                       <name>'.$this->params['shipName'].'</name>
                                       <description>'.$this->params['shipDescription'].'</description>
                                  </shipping>';
        }
        if (isset($this->params['dutyAmount'])) {
            $this->xml .= '
                                  <duty>
                                       <amount>'.$this->params['dutyAmount'].'</amount>
                                       <name>'.$this->params['dutyName'].'</name>
                                       <description>'.$this->params['dutyDescription'].'</description>
                                  </duty>';
        }
        $this->xml .= '
                                  <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                                  <customerPaymentProfileId>'.$this->params['customerPaymentProfileId'].'</customerPaymentProfileId>
                                  <customerShippingAddressId>'.$this->params['customerShippingAddressId'].'</customerShippingAddressId>';
        if (isset($this->params['transId'])) {
            $this->xml .= '
                                  <transId>'.$this->params['transId'].'</transId>';
        }
        if (isset($this->params['orderInvoiceNumber'])) {
            $this->xml .= '
                                  <order>
                                       <invoiceNumber>'.$this->params['invoiceNumber'].'</invoiceNumber>
                                       <description>'.$this->params['description'].'</description>
                                  </order>';
        }
        if ($type != "profileTransRefund") {
            $this->xml .= '
                                  <taxExempt>'.$this->params['taxExempt'].'</taxExempt>
                                  <recurringBilling>'.$this->params['recurringBilling'].'</recurringBilling>';
        }

        if (isset($this->params['cardCode'])) {
            $this->xml .= '
                                  <cardCode>'.$this->params['cardCode'].'</cardCode>';
        }
        if (isset($this->params['orderInvoiceNumber']) && isset($this->params['approvalCode'])) {
            $this->xml .= '
                                  <approvalCode>'.$this->params['approvalCode'].'</approvalCode>';
        }
        $this->xml .= '
                              </'.$type.'>
                          </transaction>
                      </createCustomerProfileTransactionRequest>';
        $this->process();
    }

    public function deleteCustomerProfile()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <deleteCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                      </deleteCustomerProfileRequest>';
        $this->process();
    }

    public function deleteCustomerPaymentProfile()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <customerPaymentProfileId>'.$this->params['customerPaymentProfileId'].'</customerPaymentProfileId>
                      </deleteCustomerPaymentProfileRequest>';
        $this->process();
    }

    public function deleteCustomerShippingAddress()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <deleteCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <customerAddressId>'.$this->params['customerAddressId'].'</customerAddressId>
                      </deleteCustomerShippingAddressRequest>';
        $this->process();
    }

    public function getHostedProfilePage()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <getHostedProfilePageRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <hostedProfileSettings>';
        if (!empty($this->params['hostedProfileReturnUrl'])) {
            $this->xml .= '
                              <setting>
                                  <settingName>hostedProfileReturnUrl</settingName>
                                  <settingValue>'.$this->params['hostedProfileReturnUrl'].'</settingValue>
                              </setting>';
        }
        if (!empty($this->params['hostedProfileIFrameCommunicatorUrl'])) {
            $this->xml .= '
                              <setting>
                                  <settingName>hostedProfileIFrameCommunicatorUrl</settingName>
                                  <settingValue>'.$this->params['hostedProfileIFrameCommunicatorUrl'].'</settingValue>
                              </setting>';
        }
        $this->xml .= '
                              <setting>
                                  <settingName>hostedProfileReturnUrlText</settingName>
                                  <settingValue>'.$this->params['hostedProfileReturnUrlText'].'</settingValue>
                              </setting>
                              <setting>
                                  <settingName>hostedProfilePageBorderVisible</settingName>
                                  <settingValue>'.$this->params['hostedProfilePageBorderVisible'].'</settingValue>
                              </setting>
                              <setting>
                                  <settingName>hostedProfileCardCodeRequired</settingName>
                                  <settingValue>'.$this->params['hostedProfileCardCodeRequired'].'</settingValue>
                              </setting>
                              <setting>
                                  <settingName>hostedProfileBillingAddressRequired</settingName>
                                  <settingValue>'.$this->params['hostedProfileBillingAddressRequired'].'</settingValue>
                              </setting>
                          </hostedProfileSettings>
                      </getHostedProfilePageRequest>';
        $this->process();
    }

    public function getCustomerProfile()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                      </getCustomerProfileRequest>';
        $this->process();
    }

    public function getCustomerPaymentProfile()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <getCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <customerPaymentProfileId>'.$this->params['customerPaymentProfileId'].'</customerPaymentProfileId>
                      </getCustomerPaymentProfileRequest>';
        $this->process();
    }

    public function getCustomerShippingAddress()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <getCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>
                              <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                              <customerAddressId>'.$this->params['customerAddressId'].'</customerAddressId>
                      </getCustomerShippingAddressRequest>';
        $this->process();
    }

    public function updateCustomerProfile()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <updateCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <profile>
                              <merchantCustomerId>'.$this->params['merchantCustomerId'].'</merchantCustomerId>
                              <description>'.$this->params['description'].'</description>
                              <email>'.$this->params['email'].'</email>
                              <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          </profile>
                      </updateCustomerProfileRequest>';
        $this->process();
    }

    public function updateCustomerPaymentProfile($type = 'credit')
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <updateCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <paymentProfile>
                              <customerType>'.$this->params['customerType'].'</customerType>
                              <billTo>
                                  <firstName>'.$this->params['firstName'].'</firstName>
                                  <lastName>'.$this->params['lastName'].'</lastName>
                                  <company>'.$this->params['company'].'</company>
                                  <address>'.$this->params['address'].'</address>
                                  <city>'.$this->params['city'].'</city>
                                  <state>'.$this->params['state'].'</state>
                                  <zip>'.$this->params['zip'].'</zip>
                                  <country>'.$this->params['country'].'</country>
                                  <phoneNumber>'.$this->params['phoneNumber'].'</phoneNumber>
                                  <faxNumber>'.$this->params['faxNumber'].'</faxNumber>
                              </billTo>
                              <payment>';
        if ($type === 'credit') {
            $this->xml .= '
                                  <creditCard>
                                      <cardNumber>'.$this->params['cardNumber'].'</cardNumber>
                                      <expirationDate>'.$this->params['expirationDate'].'</expirationDate>
                                  </creditCard>';
        } elseif ($type === 'check') {
            $this->xml .= '
                                  <bankAccount>
                                      <accountType>'.$this->params['accountType'].'</accountType>
                                      <nameOnAccount>'.$this->params['nameOnAccount'].'</nameOnAccount>
                                      <echeckType>'.$this->params['echeckType'].'</echeckType>
                                      <bankName>'.$this->params['bankName'].'</bankName>
                                      <routingNumber>'.$this->params['routingNumber'].'</routingNumber>
                                      <accountNumber>'.$this->params['accountNumber'].'</accountNumber>
                                  </bankAccount>
                                  <driversLicense>
                                      <dlState>'.$this->params['dlState'].'</dlState>
                                      <dlNumber>'.$this->params['dlNumber'].'</dlNumber>
                                      <dlDateOfBirth>'.$this->params['dlDateOfBirth'].'</dlDateOfBirth>
                                  </driversLicense>';
        }
        $this->xml .= '
                              </payment>
                              <customerPaymentProfileId>'.$this->params['customerPaymentProfileId'].'</customerPaymentProfileId>
                          </paymentProfile>
                      </updateCustomerPaymentProfileRequest>';
        $this->process();
    }

    public function updateCustomerShippingAddress()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <updateCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>';
        if (!empty($this->params['refId'])) {
            $this->xml .= '
                          <refId>'.$this->params['refId'].'</refId>';
        }
        $this->xml .= '
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <address>
                              <firstName>'.$this->params['firstName'].'</firstName>
                              <lastName>'.$this->params['lastName'].'</lastName>
                              <company>'.$this->params['company'].'</company>
                              <address>'.$this->params['address'].'</address>
                              <city>'.$this->params['city'].'</city>
                              <state>'.$this->params['state'].'</state>
                              <zip>'.$this->params['zip'].'</zip>
                              <country>'.$this->params['country'].'</country>
                              <phoneNumber>'.$this->params['phoneNumber'].'</phoneNumber>
                              <faxNumber>'.$this->params['faxNumber'].'</faxNumber>
                              <customerAddressId>'.$this->params['customerAddressId'].'</customerAddressId>
                          </address>
                      </updateCustomerShippingAddressRequest>';
        $this->process();
    }

    public function validateCustomerPaymentProfile()
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
                      <validateCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                              <name>'.$this->login.'</name>
                              <transactionKey>'.$this->transkey.'</transactionKey>
                          </merchantAuthentication>
                          <customerProfileId>'.$this->params['customerProfileId'].'</customerProfileId>
                          <customerPaymentProfileId>'.$this->params['customerPaymentProfileId'].'</customerPaymentProfileId>
                          <customerShippingAddressId>'.$this->params['customerShippingAddressId'].'</customerShippingAddressId>
                          <validationMode>'.$this->params['validationMode'].'</validationMode>
                      </validateCustomerPaymentProfileRequest>';
        $this->process();
    }

    private function getLineItems()
    {
        $tempXml = '';
        foreach ($this->items as $item) {
            foreach ($item as $key => $value) {
                $tempXml .= "\t".'<'.$key.'>'.$value.'</'.$key.'>'."\n";
            }
        }
        return $tempXml;
    }

    public function setLineItem($itemId, $name, $description, $quantity, $unitprice,$taxable = 'false')
    {
        $this->items[] = array(
            'itemId'      => $itemId,
            'name'        => $name,
            'description' => $description,
            'quantity'    => $quantity,
            'unitPrice'   => $unitprice,
            'taxable'     => $taxable
        );
    }

    public function setParameter($field = '', $value = null)
    {
        $field = (is_string($field)) ? trim($field) : $field;
        $value = (is_string($value)) ? trim(str_replace("&", "&amp;", $value)) : $value;
        if (!is_string($field)) {
            trigger_error(__METHOD__.'() arg 1 must be a string: '.gettype($field).' given.', E_USER_ERROR);
        }
        if (empty($field)) {
            trigger_error(__METHOD__.'() requires a parameter field to be named.', E_USER_ERROR);
        }
        if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
            trigger_error(__METHOD__.'() arg 2 ('.$field.') must be a string, integer, or boolean value: '.gettype($value).' given.', E_USER_ERROR);
        }
        if ($value === '' || is_null($value)) {
            trigger_error(__METHOD__.'() parameter "value" is empty or missing (parameter: '.$field.').', E_USER_NOTICE);
        }
        $this->params[$field] = $value;
    }

    private function parseResults()
    {
        $response = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $this->response);
        $xml = new SimpleXMLElement($response);

        $this->resultCode       = (string) $xml->messages->resultCode;
        $this->code             = (string) $xml->messages->message->code;
        $this->text             = (string) $xml->messages->message->text;
        $this->validation       = (string) $xml->validationDirectResponse;
        $this->directResponse   = (string) $xml->directResponse;
        $this->profileToken     = (string) $xml->token;

        $this->profileId        = (int) $xml->customerProfileId;
        if ($this->profileId == 0 && isset($xml->profile->customerProfileId)) {
            $this->profileId    = (int) $xml->profile->customerProfileId;
        }
        if ($this->profileId == 0 && $this->code == 'E00039') {
            $str = strip_tags($this->text);
            //A duplicate record with ID 12345678 already exists.
            preg_match('!\d+!', $str, $matches);
            $this->profileId    = (int) $matches[0];
        }

        $this->addressId        = (int) $xml->customerAddressId;
        if ($this->addressId == 0 && isset($xml->profile->shipToList->customerAddressId)) {
            $this->addressId = (int) $xml->profile->shipToList->customerAddressId;
        }

        $this->paymentProfileId = (int) $xml->customerPaymentProfileId;
        if ($this->paymentProfileId == 0 && isset($xml->profile->paymentProfiles->customerPaymentProfileId)) {
            $this->paymentProfileId = (int) $xml->profile->paymentProfiles->customerPaymentProfileId;
        }

        $this->results          = explode(',', $this->directResponse);
    }


    public function isSuccessful()
    {
        return $this->success;
    }

    public function isError()
    {
        return $this->error;
    }

    public function getResponseSummary()
    {
        return 'Response code: '.$this->getCode().' Message: '.$this->getResponse();
    }

    public function getResponse()
    {
        return strip_tags($this->text);
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getProfileToken()
    {
        return $this->profileToken;
    }

    public function getProfileID()
    {
        return $this->profileId;
    }

    public function validationDirectResponse()
    {
        return $this->validation;
    }

    public function getCustomerAddressId()
    {
        return $this->addressId;
    }

    public function getDirectResponse()
    {
        return $this->directResponse;
    }

    public function getPaymentProfileId()
    {
        return $this->paymentProfileId;
    }

    public function getResponseSubcode()
    {
        return $this->results[1];
    }

    public function getResponseCode()
    {
        return $this->results[2];
    }

    public function getResponseText()
    {
        return $this->results[3];
    }

    public function getAuthCode()
    {
        return $this->results[4];
    }

    public function getAVSResponse()
    {
        return $this->results[5];
    }

    public function getTransactionID()
    {
        return $this->results[6];
    }

    public function getCVVResponse()
    {
        return $this->results[38];
    }

    public function getCAVVResponse()
    {
        return $this->results[39];
    }
}

?>