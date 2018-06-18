<?php
abstract class API
{

    protected $url;
    protected $params;
    protected $sessionId = false;

    protected function __construct($script, $params)
    {
        $this->url = APIUtils::$API_URL.$script;
        $this->params = $params;
    }

    protected function getPostParams($object, $action)
    {
        return array
        (
            'Object' => $object,
            'Action' => $action
        );
    }

    protected function execute($postParams, $existingSession=true, $auth=false)
    {
        if($existingSession)
        {
            if($this->sessionId === false)
            {
                // Create new session ID
                $api = new AuthAPI($this->params);
                $acountId = $this->params['AccountNo'];
                $userId = $this->params['UserId'];
                $password = $this->params['Password'];
                $results = $api->authenticate($acountId, $userId, $password);

                if(!$results->isSuccess())
                {
                    return $results;
                }

                $this->sessionId = $results->getResponse();
            }

            $postParams['SessionID'] = $this->sessionId;
        }

        // Convert parameters
        $postParams = array_merge(APIUtils::$API_COMMON_PARAMS, $postParams);
        $postFields = '';

        foreach($postParams as $key => $values)
        {
            if(is_array($values))
            {
                foreach($values as $value)
                {
                    $postFields .= $key.'='.urlencode($value).'&';
                }
            }
            else
            {
                $postFields .= $key.'='.urlencode($values).'&';
            }
        }

        // Open connection
        $conn = curl_init();

        // Set URL, POST data
        curl_setopt($conn, CURLOPT_URL, $this->url);
        curl_setopt($conn, CURLOPT_POST, 1);
        curl_setopt($conn, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($conn, CURLOPT_HEADER, false);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, false);

        // Execute POST
        $response = curl_exec($conn);
        $error = curl_error($conn);
        $errorNum = curl_errno($conn);

        // Close connection
        curl_close($conn);

        CE_Lib::log(2, "API Query: $this->url?$postFields");
        CE_Lib::log(2, "API Results: $response");


        if($errorNum)
        {
            return new APIResult("ERR-CURL: $errorNum, $error");
        }

        return new APIResult($response, $auth);
    }

    protected function getDomain()
    {
        return $this->params['sld'].'.'.$this->params['tld'];
    }

    protected function getClientId()
    {
        return $this->params['userid'];
    }

}

class AuthAPI extends API
{

    function __construct($params)
    {
        parent::__construct('auth.pl', $params);
    }

    function authenticate($accountNo, $userId, $password)
    {
        $postParams = array
        (
            'AccountNo' => $accountNo,
            'UserId' => $userId,
            'Password' => $password
        );

        return $this->execute($postParams, false, 'auth');
    }

}

class QueryAPI extends API
{

    function __construct($params)
    {
        parent::__construct('query.pl', $params);
    }

    function domainLookup()
    {
        $postParams = $this->getPostParams('Domain', 'Availability');
        $postParams['Domain'] = $this->getDomain();
        return $this->execute($postParams);
    }

    function domainWhois()
    {
        $postParams = $this->getPostParams('Domain', 'Details');
        $postParams['Domain'] = $this->getDomain();
        return $this->execute($postParams);
    }

    function domainSync($domains)
    {
        $postParams = $this->getPostParams('Domain', 'Sync');
        $postParams['Domain'] = $domains;
        return $this->execute($postParams);
    }

    function accountInfo()
    {
        $postParams = $this->getPostParams('Account', 'Details');
        $postParams['ExternalAccountID'] = $this->getClientId();
        return $this->execute($postParams);
    }

}

class OrderAPI extends API
{

    function __construct($params)
    {
        parent::__construct('order.pl', $params);
    }

    function domainRegister()
    {
        $contactIds = $this->createContacts();

        if($contactIds instanceof APIResult)
        {
            // Error occurred
            return $contactIds;
        }

        $postParams = $this->getPostParams('Domain', 'Create');
        $postParams['Domain'] = $this->getDomain();
        $postParams['Period'] = $this->getPeriod();
        $postParams['Host'] = $this->getNameServers();
        $postParams = array_merge($postParams, $this->getDomainCredentials());
        $postParams = array_merge($postParams, $contactIds);
        $postParams = array_merge($postParams, $this->getEligibilityDetails());
        return $this->execute($postParams);
    }

    function domainRenewal()
    {
        $postParams = $this->getPostParams('Domain', 'Renewal');
        $postParams['Domain'] = $this->getDomain();
        $postParams['Period'] = $this->getPeriod();
        $postParams = array_merge($postParams, $this->getCreditCard());
        return $this->execute($postParams);
    }

    function domainTransfer()
    {
        $contactIds = $this->createContacts();

        if($contactIds instanceof APIResult)
        {
            // Error occurred
            return $contactIds;
        }

        $postParams = $this->getPostParams('Domain', 'TransferRequest');
        $postParams['Domain'] = $this->getDomain();
        $postParams['Period'] = $this->getPeriod();
        $postParams['DomainPassword'] = APIUtils::getValue($this->params, 'transfersecret', '');
        $postParams = array_merge($postParams, $this->getDomainCredentials());
        $postParams = array_merge($postParams, $contactIds);
        $postParams = array_merge($postParams, $this->getCreditCard());
        return $this->execute($postParams);
    }

    function domainDelegation()
    {
        $postParams = $this->getPostParams('Domain', 'UpdateHosts');
        $postParams['Domain'] = $this->getDomain();
        $postParams['AddHost'] = $this->params['AddHost'];
        $postParams['RemoveHost'] = 'ALL';
        return $this->execute($postParams);
    }

    function domainLock()
    {
        $postParams = $this->getPostParams('Domain', 'UpdateDomainLock');
        $postParams['Domain'] = $this->getDomain();
        $lockEnabled = $this->params['lockenabled'];
        $postParams['DomainLock'] = (!$lockEnabled || $lockEnabled == 'unlocked') ? 'Unlock' : 'Lock';
        return $this->execute($postParams);
    }

    function hostCreate()
    {
        $postParams = $this->getPostParams('Domain', 'CreateNameServer');
        $postParams['Domain'] = $this->getDomain();
        $postParams['NameServerPrefix'] = $this->getHost();
        $postParams['NameServerIP'] = $this->getHostIPs('ipaddress');
        return $this->execute($postParams);
    }

    function hostUpdate()
    {
        $postParams = $this->getPostParams('Domain', 'ChangeNameServer');
        $postParams['Domain'] = $this->getDomain();
        $postParams['NameServerPrefix'] = $this->getHost();
        $postParams['RemoveNameServerIP'] = $this->getHostIPs('currentipaddress');
        $postParams['AddNameServerIP'] = $this->getHostIPs('newipaddress');
        return $this->execute($postParams);
    }

    function hostRemove()
    {
        $postParams = $this->getPostParams('Domain', 'DeleteNameServer');
        $postParams['Domain'] = $this->getDomain();
        $postParams['NameServerPrefix'] = $this->getHost();
        return $this->execute($postParams);
    }

    function contactsUpdate()
    {
        $contactIds = $this->createContacts(false);

        if($contactIds instanceof APIResult)
        {
            // Error occurred
            return $contactIds;
        }

        $postParams = $this->getPostParams('Domain', 'UpdateContacts');
        $postParams['Domain'] = $this->getDomain();
        $postParams = array_merge($postParams, $contactIds);
        return $this->execute($postParams);
    }

    private function createContacts($all=true)
    {
        $contacts = APIUtils::getValue($this->params, 'contactdetails', false);

        if($contacts === false)
        {
            $contacts = array
            (
                'Registrant' => $this->getContact(),
                'Admin' => $this->getContact('admin')
            );
        }

        $contactIds = array();
        $defaultContactId = false;

        // Create contacts first
        foreach(APIUtils::$CONTACT_TYPES as $apiType => $moduleType)
        {

            $contact = APIUtils::getValue($contacts, $moduleType, false);

            if($contact !== false)
            {
                $results = $this->createContact($contact);

                if($results->isSuccess())
                {
                    // Contact created, store contact ID
                    $key = $apiType.'ContactID';
                    $contactId = $results->getResponse();
                    $contactIds[$key] = $contactId;

                    if($defaultContactId === false)
                    {
                        $defaultContactId = $contactId;
                    }
                }
                else
                {
                    // Contact creation failed, return error
                    return $results;
                }
            }
        }

        if($all && $defaultContactId !== false)
        {
            foreach(array_keys(APIUtils::$CONTACT_TYPES) as $apiType)
            {
                $key = $apiType.'ContactID';

                // If the contact ID is not set, use the default contact ID
                if(!array_key_exists($key, $contactIds))
                {
                    $contactIds[$key] = $defaultContactId;
                }
            }
        }

        return $contactIds;
    }

    private function createContact($contact)
    {
        $postParams = $this->getPostParams('Contact', 'Create');
        $postParams = array_merge($postParams, $contact);
        return $this->execute($postParams);
    }

    private function getContact($type='')
    {
        $contact = array();

        foreach(APIUtils::$CONTACT_FIELDS as $apiField => $moduleField)
        {
            $key = $type.$moduleField;
            $value = APIUtils::getValue($this->params, $key, '');
            $contact[$apiField] = $value;
        }

        return $contact;
    }

    private function getPeriod()
    {
        return APIUtils::getValue($this->params, 'regperiod', '');
    }

    private function getHost()
    {
        return APIUtils::getValue($this->params, 'nameserver', '');
    }

    private function getHostIPs($key)
    {
        $ips = APIUtils::getValue($this->params, $key, '');
        return preg_split('/\s*,\s*/', $ips);
    }

    private function getNameServers()
    {
        $nameServers = array();
        $keys = array('ns1', 'ns2', 'ns3', 'ns4', 'ns5', 'ns6', 'ns7', 'ns8', 'ns9');

        foreach($keys as $key)
        {
            $value = APIUtils::getValue($this->params, $key, false);

            if($value !== false)
            {
                $nameServers[] = $value;
            }
        }

        return $nameServers;
    }

    private function getEligibilityDetails()
    {
        $eligibilityForm = APIUtils::getValue($this->params, 'additionalfields', false);

        if($eligibilityForm)
        {
            $registrantName = APIUtils::getValue($eligibilityForm, 'Registrant Name', '');
            $eligibilityName = APIUtils::getValue($eligibilityForm, 'Eligibility Name', '');
            $eligibilityId = APIUtils::getValue($eligibilityForm, 'Eligibility ID', '');
            $registrantIDType = APIUtils::getValue($eligibilityForm, 'Registrant ID Type', '');
            $registrantID = APIUtils::getValue($eligibilityForm, 'Registrant ID', '');
            $eligibilityIdType = APIUtils::getValue($eligibilityForm, 'Eligibility ID Type', APIUtils::$ELIGIBILITY_ID_TYPES, '');
            $eligibilityType = APIUtils::getValue($eligibilityForm, 'Eligibility Type', APIUtils::$ELIGIBILITY_TYPES, '');
            $eligibilityReason = APIUtils::getValue($eligibilityForm, 'Eligibility Reason', APIUtils::$ELIGIBILITY_REASONS, '');
            $eligibilityIdType = APIUtils::$ELIGIBILITY_ID_TYPES[$eligibilityIdType];

            return array
            (
                'RegistrantName' => $registrantName,
                'RegistrantID' => $registrantID,
                //'EligibilityName' => $eligibilityName,
                //'EligibilityID' => $eligibilityId,
                'EligibilityIDType' => $eligibilityIdType,
                'EligibilityType' => $eligibilityType,
                'EligibilityReason' => $eligibilityReason
            );
        }

        return array();
    }

    private function getCreditCard()
    {
        $cardNumber = APIUtils::getValue($this->params, 'CreditCardNumber', '');
        $cardExpiry = APIUtils::getValue($this->params, 'CreditCardExpiry', '');

        return array
        (
            'CreditCardNumber' => $cardNumber,
            'CreditCardExpiry' => $cardExpiry
        );
    }

    private function getDomainCredentials()
    {
        $accountOption = APIUtils::getValue($this->params, 'AccountOption', '');
        $accountId = APIUtils::getValue($this->params, 'AccountReference', '');

        if($accountOption == 'EXTERNAL')
        {
            $accountOption = 'EXTERNAL';
        }
        else if($accountOption == 'CONSOLE')
        {
            $accountOption = 'CONSOLE';
        }
        else
        {
            $accountOption = 'DEFAULT';
            $accountId = '';
        }

        return array
        (
            'AccountOption' => $accountOption,
            'AccountID' => $accountId
        );
    }

}


class APIResult
{

    private $response;
    private $success = false;
    public $params = array();
    private $error;
    public $logger;


    function __construct($response, $auth=false)
    {
        $this->response = $response;
        $index = false;

        $responseTemp = preg_split('/\s*:\s*/', $response);
        $index = array_search('OK', $responseTemp);

        if ( $index === false ) {
            $responseTemp = explode("\n", $response);

            foreach ( $responseTemp as $key => $r ) {
                if ( substr(trim($r),0,3) == 'OK:' ) {
                    $index = $key;
                    break;
                }
            }
        }

        if($index !== false)
        {
            $this->success = true;

            // Remove "OK:" from response
            $responseTemp = array_splice($responseTemp, $index + 1);
            $response = trim(implode("\n", $responseTemp));
            $this->response = $response;

            // Saves response as (key=value) pairs
            $lines = explode("\n", $response);

            foreach($lines as $line)
            {
                $index = strpos($line, '=');

                // Break response into (key=value) pairs
                if($index !== false)
                {
                    $key = trim(substr($line, 0, $index));
                    $value = trim(substr($line, $index + 1));
                    $this->params[$key][] = $value;
                }
            }
        }
        else
        {
            $responseTemp = preg_split('/\s*:\s*/', $response);
            $index = array_search('ERR', $responseTemp);

            if($index !== false)
            {
                // Remove "ERR:" from response
                $responseTemp = array_splice($responseTemp, $index + 1);
                $response = implode(':', $responseTemp);
                $this->error = APIUtils::$ERROR_UNKNOWN.': '.$response;

                if($response && preg_match('/^\d+/', $response))
                {
                    // API error starts with error code,
                    // let's convert error code to a error message instead
                    $errorTemp = preg_split('/\s*,\s*/', $response);
                    $errorCode = $errorTemp[0];
                    $this->error = "[$errorCode] ";
                    $this->error .= APIUtils::getValue(APIUtils::$ERROR_CODES, $errorCode, APIUtils::$ERROR_UNKNOWN);

                    // Append remaining error message
                    $this->error .= ': '.implode(', ', array_slice($errorTemp, 1));
                }
            }
        }

        if(!$this->success && !$this->error)
        {
            // API call was not successful, and there were no error returned
            $this->error = APIUtils::$ERROR_UNKNOWN;
        }
    }

    function getResponse()
    {
        return $this->response;
    }

    function isSuccess()
    {
        return $this->success;
    }

    function getParams($prefix)
    {
        $params = array();
        $keys = array_keys($this->params);

        foreach($keys as $key)
        {
            if(preg_match('/^'.$prefix.'/', $key))
            {
                $value = $this->get($key);
                $key = substr($key, strlen($prefix));
                $params[$key] = $value;
            }
        }

        return $params;
    }

    function get($key)
    {
        $value = $this->getArray($key);
        return empty($value) ? '' : $value[0];
    }

    function getArray($key)
    {
        return APIUtils::getValue($this->params, $key, array());
    }

    function getModuleResults()
    {
        return $this->success ? '' : $this->getModuleError();
    }

    function getModuleError()
    {
        return $this->error;
    }

}

class APIUtils
{

    static $BRAND_NAME = 'Planet Domain';

    static $API_URL = 'https://theconsole.tppwholesale.com.au/api/';

    static $API_COMMON_PARAMS = array
    (
        'Requester' => 'Client Exec',
        'Version' => '1.1',
        'Type' => 'Domains'
    );

    static $ERROR_UNKNOWN = 'Unknown error occurred';

    static $ERROR_CODES = array
    (
        '100' => 'Missing parameters',
        '102' => 'Authentication failed',
        '105' => 'Request is coming from incorrect IP address',
        '202' => 'Invalid API Type',
        '203' => 'API call has not been implemented yet',
        '301' => 'Invalid order ID',
        '302' => 'Domain name is either invalid or not supplied',
        '303' => 'Domain prices are not setted up',
        '304' => 'Domain registration failed',
        '305' => 'Domain renewal failed',
        '306' => 'Domain transfer failed',
        '309' => 'Invalid domain extension',
        '311' => 'Domain does not exist in your reseller account',
        '312' => 'Invalid username/password',
        '313' => 'Account does not exist in your reseller profile',
        '401' => 'Failed to connect to registry, please retry',
        '500' => 'Prepaid account does not have enough funds to cover the cost of this order',
        '501' => 'Invalid credit card type',
        '502' => 'Invalid credit card number',
        '503' => 'Invalid credit card expiry date',
        '505' => 'Credit card transaction failed',
        '600' => 'Failed to create/update contact',
        '601' => 'Failed to create order',
        '602' => 'Invalid hosts supplied',
        '603' => 'Invalid eligibility fields supplied',
        '610' => 'Failed to connect to registry, please retry',
        '611' => 'Domain renewal/transfer failed',
        '612' => 'Locking is not available for this domain',
        '614' => 'Failed to lock/unlock domain',
        '615' => 'Domain delegation failed'
    );

    static $CONTACT_TYPES = array
    (
        'Owner' => 'Registrant',
        'Administration' => 'Admin',
        'Technical' => 'Tech',
        'Billing' => 'Billing'
    );

    static $CONTACT_FIELDS = array
    (
        'FirstName' => 'firstname',
        'LastName' => 'lastname',
        'Address1' => 'address1',
        'Address2' => 'address2',
        'City' => 'city',
        'Region' => 'state',
        'PostalCode' => 'postcode',
        'CountryCode' => 'country',
        'Email' => 'email',
        'PhoneNumber' => 'phonenumber'
    );

    static $ELIGIBILITY_ID_TYPES = array
    (
        'ACN' => 1,
        'ACT BN' => 2,
        'NSW BN' => 3,
        'NT BN' => 4,
        'QLD BN' => 5,
        'SA BN' => 6,
        'TAS BN' => 7,
        'VIC BN' => 8,
        'WA BN' => 9,
        'Trademark' => 10,
        'Other' => 11,
        'ABN' => 12
    );

    static $ELIGIBILITY_TYPES = array
    (
        'Charity' => 1,
        'Citizen/Resident' => 2,
        'Club' => 3,
        'Commercial Statutory Body' => 4,
        'Company' => 5,
        'Incorporated Association' => 6,
        'Industry Body' => 8,
        'Non-profit Organisation' => 9,
        'Other' => 10,
        'Partnership' => 11,
        'Pending TM Owner' => 12,
        'Political Party' => 13,
        'Registered Business' => 14,
        'Religious/Church Group' => 15,
        'Sole Trader' => 16,
        'Trade Union' => 17,
        'Trademark Owner' => 18,
        'Child Care Centre' => 19,
        'Government School' => 20,
        'Higher Education Institution' => 21,
        'National Body' => 22,
        'Non-Government School' => 23,
        'Pre-school' => 24,
        'Research Organisation' => 25,
        'Training Organisation' => 26
    );

    static $ELIGIBILITY_REASONS = array
    (
        'Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.' => 1,
        'Close and substantial connection between the domain name and the operations of your Entity.' => 2
    );

    static function getValue($array, $key, $defaultValue)
    {
        return array_key_exists($key, $array) ? $array[$key] : $defaultValue;
    }

    static function getValueConverted($array, $key, $valueMap, $defaultValue=false)
    {
        $value = APIUtils::getValue($array, $key, false);
        return $value !== false ? APIUtils::getValue($valueMap, $value, $defaultValue) : $defaultValue;
    }

}