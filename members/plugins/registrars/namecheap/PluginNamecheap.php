<?php

require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'modules/domains/models/ICanImportDomains.php';

class PluginNamecheap extends RegistrarPlugin implements ICanImportDomains
{
    public $supportsNamesuggest = false;

    private $sandboxUrl = 'https://api.sandbox.namecheap.com/xml.response';
    private $liveUrl = 'https://api.namecheap.com/xml.response';
    private $recordTypes = array('A', 'AAAA', 'MXE', 'MX', 'CNAME', 'URL', 'FRAME', 'TXT');

    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('NameCheap')
                               ),
            lang('Use Sandbox?') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use NameCheap\'s sandbox environment.'),
                                'value'         =>0
                               ),
            lang('Username') => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter your username for your NameCheap account.'),
                                'value'         =>''
                               ),
            lang('API Key')  => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter the API Key for your NameCheap account.'),
                                'value'         =>'',
                                ),
            lang('Supported Features')  => array(
                                'type'          => 'label',
                                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* '.lang('Existing Domain Importing').' <br>* '.lang('Get / Set DNS Records').' <br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Contact Information').' <br>* '.lang('Get / Set Registrar Lock').' <br>* '.lang('Initiate Domain Transfer').' <br>* '.lang('Automatically Renew Domain'),
                                'value'         => ''
                                ),
            lang('Actions') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain isn\'t registered)'),
                                'value'         => 'Register'
                                ),
            lang('Registered Actions') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain is registered)'),
                                'value'         => 'Renew (Renew Domain),DomainTransferWithPopup (Initiate Transfer),Cancel',
                                ),
            lang('Registered Actions For Customer') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain is registered)'),
                                'value'         => '',
            )
        );

        return $variables;
    }

    public function doDomainTransferWithPopup($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $transferid = $this->initiateTransfer($this->buildTransferParams($userPackage, $params));
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar").'-'.$transferid);
        $userPackage->setCustomField('Transfer Status', $transferid);
        return "Transfer of has been initiated.";
    }

    public function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->registerDomain($this->buildRegisterParams($userPackage, $params));
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been registered.';
    }

    public function doRenew($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->renewDomain($this->buildRenewParams($userPackage, $params));
        $userPackage->setCustomField("Registrar Order Id", $userPackage->getCustomField("Registrar").'-'.$orderid);
        return $userPackage->getCustomField('Domain Name') . ' has been renewed.';
    }

    public function doSetRegistrarLock($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->setRegistrarLock($this->buildLockParams($userPackage, $params));
        return "Updated Registrar Lock.";
    }

    public function doSendTransferKey($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->sendTransferKey($this->buildRegisterParams($userPackage, $params));
        return 'Successfully sent auth info for ' . $userPackage->getCustomField('Domain Name');
    }

    public function checkDomain($params)
    {
        $domains = [];
        $domain = $params['sld'] . '.' . $params['tld'];
        $arguments = ['DomainList' => $domain];

        $response = $this->makeRequest('namecheap.domains.check', $params, $arguments);
        if ($response->CommandResponse->DomainCheckResult->attributes()->Available == 'false') {
            $status = 1;
        } else if ($response->CommandResponse->DomainCheckResult->attributes()->IsPremiumName == 'true') {
            // we do not allow premium domains right now.
            $status = 1;
        } else if ($response->CommandResponse->DomainCheckResult->attributes()->Available == 'true') {
            $status = 0;
        }

        $domains[] = array('tld' => $params['tld'], 'domain' => $params['sld'], 'status' => $status);

        return array("result"=>$domains);
    }

    public function getTransferStatus($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $arguments = ['TransferID' => $userPackage->getCustomField('Transfer Status')];
        $response = $this->makeRequest('namecheap.domains.transfer.getStatus', $params, $arguments);

        // https://www.namecheap.com/support/api/transfer-statuses.aspx
        $transferStatusId = (int)$response->CommandResponse->DomainTransferGetStatusResult->attributes()->StatusID;
        if ($transferStatusId == 5) {
            $userPackage->setCustomField('Transfer Status', 'Completed');
        }
        return (string)$response->CommandResponse->DomainTransferGetStatusResult->attributes()->Status;
    }

    public function initiateTransfer($params)
    {
        $domain = $params['sld'] . '.' . $params['tld'];
        $arguments = [
            'DomainName'    => $domain,
            'Years'         => 1,
            'EPPCode'       => $params['eppCode']
        ];
        $response = $this->makeRequest('namecheap.domains.transfer.create', $params, $arguments);
        $transferId = (int)$response->CommandResponse->DomainTransferCreateResult->attributes()->TransferID;
        return $transferId;
    }

    public function renewDomain($params)
    {
        $arguments = [
            'DomainName'    => $params['sld'] . '.' . $params['tld'],
            'Years'         => $params['NumYears']
        ];
        $response = $this->makeRequest('namecheap.domains.renew', $params, $arguments);
        $domainId = (int)$response->CommandResponse->DomainRenewResult->attributes()->OrderID;
        return $domainId;
    }

    public function registerDomain($params)
    {
        $arguments = [
            'DomainName'    => $params['sld'] . '.' . $params['tld'],
            'Years'         => $params['NumYears']
        ];

        $nameservers = [];
        if (isset($params['NS1'])) {
            for ($i = 1; $i <= 12; $i++) {
                if (isset($params["NS$i"])) {
                    $nameservers[] =$params["NS$i"]['hostname'];
                } else {
                    break;
                }
            }
        }
        $arguments['Nameservers'] = implode(',', $nameservers);

        $contactDetails = [
            'FirstName'        => $params['RegistrantFirstName'],
            'LastName'         => $params['RegistrantLastName'],
            'OrganizationName' => $params['RegistrantOrganizationName'],
            'Address1'         => $params['RegistrantAddress1'],
            'City'             => $params['RegistrantCity'],
            'StateProvince'    => $params['RegistrantStateProvince'],
            'PostalCode'       => $params['RegistrantPostalCode'],
            'Country'          => $params['RegistrantCountry'],
            'Phone'            => $this->validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
            'EmailAddress'     => $params['RegistrantEmailAddress'],
        ];
        foreach ($contactDetails as $key => $value) {
                $arguments['Registrant' . $key] = $value;
            $arguments['Admin' . $key] = $value;
            $arguments['Tech' . $key] = $value;
            $arguments['AuxBilling' . $key] = $value;
        }

        $response = $this->makeRequest('namecheap.domains.create', $params, $arguments);
        $domainId = (int)$response->CommandResponse->DomainCreateResult->attributes()->OrderID;
        return $domainId;
    }

    public function makeRequest($command, $params, $arguments, $skiperrorchecking = false)
    {
        $url = $this->liveUrl;
        if ($params['Use Sandbox?']) {
            $url = $this->sandboxUrl;
        }
        $url .= '?' . $this->buildUrl($command, $params, $arguments);

        $response = NE_Network::curlRequest($this->settings, $url, $postRequest);
        $response = simplexml_load_string($response);

        $status = (string)$response->attributes()->Status;
        if ($status == 'ERROR') {
            $errors = [];
            foreach ($response->Errors as $error) {
                $errors[] = (string)$error->Error;
            }
            throw new CE_Exception(implode(',', $errors));
        }

        return $response;
    }

    public function buildUrl($command, $params, $arguments)
    {
        $args = [];
        $args['Command'] = $command;
        $args['ApiUser'] = $params['Username'];
        $args['Username'] = $params['Username'];
        $args['ApiKey'] = $params['API Key'];
        $args['ClientIp'] = CE_Lib::getRemoteAddr();
        if ($args['ClientIp'] == '::1') {
            $args['ClientIp'] = '127.0.0.1';
        }

        $args = array_merge($args, $arguments);
        return http_build_query($args);
    }

    private function validatePhone($phone, $country)
    {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);

        if ($phone == '') {
            return $phone;
        }

        $query = "SELECT phone_code FROM country WHERE iso=? AND phone_code != ''";
        $result = $this->db->query($query, $country);
        if (!$row = $result->fetch()) {
            return $phone;
        }

        // check if code is already there
        $code = $row['phone_code'];
        $phone = preg_replace("/^($code)(\\d+)/", '+\1.\2', $phone);
        if ($phone[0] == '+') {
            return $phone;
        }

        // if not, prepend it
        return "+$code.$phone";
    }

    public function getContactInformation($params)
    {
        $arguments = ['DomainName' => $params['sld'] . '.' . $params['tld']];
        $response = $this->makeRequest('namecheap.domains.getContacts', $params, $arguments);
        $info = [];
        foreach (array('Registrant', 'AuxBilling', 'Admin', 'Tech') as $type) {
            foreach ($response->CommandResponse->DomainContactsResult->$type as $contact) {
                $info[$type]['OrganizationName']  = array($this->user->lang('Organization'), (string)$contact->OrganizationName);
                $info[$type]['FirstName'] = array($this->user->lang('First Name'), (string)$contact->FirstName);
                $info[$type]['LastName']  = array($this->user->lang('Last Name'), (string)$contact->LastName);
                $info[$type]['Address1']  = array($this->user->lang('Address').' 1', (string)$contact->Address1);
                $info[$type]['City']      = array($this->user->lang('City'), (string)$contact->City);
                $info[$type]['StateProvince']  = array($this->user->lang('Province').'/'.$this->user->lang('State'), (string)$contact->StateProvince);
                $info[$type]['Country']   = array($this->user->lang('Country'), (string)$contact->Country);
                $info[$type]['PostalCode']  = array($this->user->lang('Postal Code'), (string)$contact->PostalCode);
                $info[$type]['EmailAddress']     = array($this->user->lang('Email'), (string)$contact->EmailAddress);
                $info[$type]['Phone']  = array($this->user->lang('Phone'), (string)$contact->Phone);
            }
        }
        return $info;
    }

    public function setContactInformation($params)
    {
        foreach ($params as $key => $value) {
            if (strpos($key, $params['type']) !== false) {
                $key = str_replace('_', '', $key);

                if ($key == 'RegistrantPhone') {
                    $value = $this->validatePhone($value, $params['Registrant_Country']);
                }

                $arguments[$key] = $value;
                $arguments['Admin' . str_replace('Registrant', '', $key)] = $value;
                $arguments['Tech' . str_replace('Registrant', '', $key)] = $value;
                $arguments['AuxBilling' . str_replace('Registrant', '', $key)] = $value;
            }
        }
        $arguments['DomainName'] = $params['sld'] . '.' . $params['tld'];
        $this->makeRequest('namecheap.domains.setContacts', $params, $arguments);
    }

    public function getNameServers($params)
    {
        $arguments = [
            'SLD' => $params['sld'],
            'TLD' => $params['tld']
        ];
        $response = $this->makeRequest('namecheap.domains.dns.getList', $params, $arguments);
        $data = [];
        $data['hasDefault'] = true;
        if ($response->CommandResponse->DomainDNSGetListResult->attributes()->IsUsingOurDNS == 'true') {
            $data['usesDefault'] = true;
        } else {
            $data['usesDefault'] = false;
        }
        foreach ($response->CommandResponse->DomainDNSGetListResult->Nameserver as $nameserver) {
            $data[] = (string)$nameserver;
        }
        return $data;
    }

    public function setNameServers($params)
    {
        $arguments = [
            'SLD' => $params['sld'],
            'TLD' => $params['tld']
        ];
        if ($params['default'] == true) {
            $this->makeRequest('namecheap.domains.dns.setDefault', $params, $arguments);
        } else {
            $arguments['Nameservers'] = implode(',', $params['ns']);
            $response = $this->makeRequest('namecheap.domains.dns.setCustom', $params, $arguments);
        }
    }

    public function getGeneralInfo($params)
    {
        $domain = $params['sld'] . '.' . $params['tld'];
        $arguments = ['DomainName' => $domain];
        $response = $this->makeRequest('namecheap.domains.getInfo', $params, $arguments);

        $data = [];
        $data['id'] = (int)$response->CommandResponse->DomainGetInfoResult->attributes()->ID;
        $data['domain'] = (string)$response->CommandResponse->DomainGetInfoResult->attributes()->DomainName;
        $data['expiration'] = (string)$response->CommandResponse->DomainGetInfoResult->DomainDetails->ExpiredDate;
        $data['registrationstatus'] = 'N/A';
        $data['purchasestatus'] = 'N/A';
        $data['autorenew'] = false;

        return $data;
    }

    public function fetchDomains($params)
    {
        $page = 1;
        if ($params['next'] > 25) {
            $page = ceil($params['next'] / 25);
        }

        $domainNameGateway = new domainNameGateway($this);
        $domainList = [];
        $arguments = ['PageSize' => 100];
        $response = $this->makeRequest('namecheap.domains.getList', $params, $arguments);
        foreach ($response->CommandResponse->DomainGetListResult->Domain as $domain) {
            $splitDomain = $domainNameGateway->splitDomain((string)$domain->attributes()->Name);
            $data = [];
            $data['id'] = (int)$domain->attributes()->ID;
            $data['sld'] = $splitDomain[0];
            $data['tld'] = $splitDomain[1];
            $data['exp'] = (string)$domain->attributes()->Expires;
            $domainsList[] = $data;
        }
        $metaData = array();
        $metaData['total'] = (int)$response->CommandResponse->Paging->TotalItems;
        $metaData['next'] = $page * 25 + 1;
        $metaData['start'] = 1 + ($page - 1) * 25;
        $metaData['end'] = $page * 25;
        $metaData['numPerPage'] = 25;
        return array($domainsList, $metaData);
    }

    public function getRegistrarLock($params)
    {
        $domain = $params['sld'] . '.' . $params['tld'];
        $arguments = ['DomainName' => $domain];
        $response = $this->makeRequest('namecheap.domains.getRegistrarLock', $params, $arguments);
        return ( $response->CommandResponse->DomainGetRegistrarLockResult->attributes()->RegistrarLockStatus == 'true' ? true: false );
    }

    public function setRegistrarLock($params)
    {
        $action = ( $params['lock'] == '0' ? 'UNLOCK' : 'LOCK');

        $domain = $params['sld'] . '.' . $params['tld'];
        $arguments = [
            'DomainName' => $domain,
            'LockAction' => $action
        ];
        $this->makeRequest('namecheap.domains.setRegistrarLock', $params, $arguments);
    }

    public function sendTransferKey($params)
    {
    }

    public function getDNS($params)
    {
        $records = [];
        $arguments = [
            'SLD' => $params['sld'],
            'TLD' => $params['tld']
        ];
        $response = $this->makeRequest('namecheap.domains.dns.getHosts', $params, $arguments);
        foreach ($response->CommandResponse->DomainDNSGetHostsResult->host as $host) {
            $record = [
                'id'            =>  (int)$host->attributes()->HostId,
                'hostname'      =>  (string)$host->attributes()->Name,
                'address'       =>  (string)$host->attributes()->Address,
                'type'          =>  (string)$host->attributes()->Type
            ];
            $records[] = $record;
        }
        $default = (bool)$response->CommandResponse->DomainDNSGetHostsResult->attributes()->IsUsingOurDNS;
        return array('records' => $records, 'types' => $this->recordTypes, 'default' => $default);
    }

    public function setDNS($params)
    {
        $arguments = [
            'SLD' => $params['sld'],
            'TLD' => $params['tld']
        ];

        foreach ($params['records'] as $index => $record) {
            $index++;
            $arguments['HostName' . $index] = $record['hostname'];
            $arguments['RecordType' . $index] = $record['type'];
            $arguments['Address' . $index] = $record['address'];

            if ($record['type'] == 'MX') {
                $arguments['EmailType'] = 'MX';
            } else if ($record['type'] == 'MXE') {
                $arguments['EmailType'] = 'MXE';
            }
        }
        $this->makeRequest('namecheap.domains.dns.setHosts', $params, $arguments);
    }

    public function setAutorenew($params)
    {
    }
}