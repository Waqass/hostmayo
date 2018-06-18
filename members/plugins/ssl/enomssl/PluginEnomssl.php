<?php

require_once 'modules/admin/models/SSLPlugin.php';

class PluginEnomssl extends SSLPlugin
{
    public $mappedTypeIds = array (
        SSL_CERT_RAPIDSSL                               => 23,
        SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM              => 20,
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID               => 21,
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV            => 24,
        SSL_CERT_GEOTRUST_QUICKSSL                      => 26,
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD      => 27,
        SSL_CERT_VERISIGN_SECURE_SITE                   => 180,
        SSL_CERT_VERISIGN_SECURE_SITE_PRO               => 181,
        SSL_CERT_VERISIGN_SECURE_SITE_EV                => 182,
        SSL_CERT_VERISIGN_SECURE_SITE_PRO_EV            => 183,
        SSL_CERT_COMODO_ESSENTIAL                       => 211,
        SSL_CERT_COMODO_INSTANT                         => 212,
        SSL_CERT_COMODO_PREMIUM_WILDCARD                => 213,
        SSL_CERT_COMODO_ESSENTIAL_WILDCARD              => 214,
        SSL_CERT_COMODO_EV                              => 221,
        SSL_CERT_COMODO_EV_SGC                          => 222,
        SSL_CERT_RAPIDSSL_WILDCARD                      => 285
    );

    public $usingInviteURL = false;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('eNomSSL')
                               ),
            lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use Enom\'s testing environment, so that transactions are not actually made. For this to work, you must first register you server\'s ip in Enom\'s testing environment, and your server\'s name servers must be registered there as well.'),
                                'value'         =>0
                               ),
            lang('Login') => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter your username for your eNom reseller account.'),
                                'value'         =>''
                               ),
            lang('Password')  => array(
                                'type'          =>'password',
                                'description'   =>lang('Enter the password for your eNom reseller account.'),
                                'value'         =>'',
                                ),
             lang('Actions') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin'),
                                'value'         => 'Purchase,ResendApproverEmail (Resend Approver Email),CancelConfiguration (Cancel Configuration),ResendFulfillmentEmail (Resend Fulfillment Email)'
                                ),
        );

        return $variables;
    }

    function doPurchase($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $certId = $userPackage->getCustomField('Certificate Id');
        // no cert stored, so purchase the cert.
        if ( $certId == '' ) {
            // Step 1: Purchase Cert
            $certId = $this->purchaseCert($params);
            $userPackage->setCustomField('Certificate Id', $certId);
            $params['certId'] = $certId;
        }

        // Step 2: Parse CSR
        if ( $params['CSR'] == '' || $params['adminEmail'] == '' ) {
            throw new CE_Exception('Missing CSR or Admin E-Mail');
        }

        // doParseCSR will throw an exception if it fails to parse.
        $csr = $this->doParseCSR($params);
        $userPackage->setCustomField("Certificate Domain", $csr['domain']);
        $params['domain'] = $csr['domain'];

        // Configure Cert
        $this->configureCert($params);

        $emails = $this->getApproverEmail($params);
        $foundEmail = false;
        foreach ( $emails as $email ) {
            if ( $email == $params['adminEmail'] ) {
                $foundEmail = true;
                $this->sendForApproval($params, $email);
                return 'Successfully Sent Certificate for Approval';
            }
        }
        if ( $foundEmail == false ) {
            throw new CE_Exception('Invalid Admin Approver E-mail: ' . $params['adminEmail'] . " Valid Emails: " . implode(" ", $emails));
        }
    }

    private function sendForApproval($params, $email)
    {
          $arguments = array(
            'command'           => 'CertPurchaseCert',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId'],
            'ApproverEmail'     => $email
        );

        $response = $this->_makeRequest($params, $arguments);
        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        }
    }

    private function getApproverEmail($params)
    {
         $arguments = array(
            'command'           => 'CertGetApproverEmail',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId'],
            'Domain'            => $params['domain']
        );

        $response = $this->_makeRequest($params, $arguments);
        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower(strval($response->CertGetApproverEMail->Success)) == 'true' ) {
            $emails = array();
            foreach ( $response->CertGetApproverEMail->Approver as $approver ) {
                if ( isset($approver->ApproverEmail) ) {
                    $emails[] = strval($approver->ApproverEmail);
                }
            }
            return $emails;
        }

    }

    private function configureCert($params)
    {
        $arguments = array(
            'command'           => 'CertConfigureCert',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId'],
            'WebServerType'     => $params['serverType'],
            'CSR'               => $params['CSR'],
            'AdminFName'        => $params['FirstName'],
            'AdminLname'        => $params['LastName'],
            'AdminOrgName'      => '',
            'AdminAddress1'     => $params['Address1'],
            'AdminCity'         => $params['City'],
            'AdminProvince'     => $params['StateProvince'],
            'AdminState'     => $params['StateProvince'],
            'AdminPostalCode'   => $params['PostalCode'],
            'AdminCountry'      => $params['Country'],
            'AdminPhone'        => $this->_validatePhone($params['Phone'], $params['Country']),
            'AdminEmailAddress' => $params['EmailAddress'],
            'BillingFName'        => $params['FirstName'],
            'BillingLname'        => $params['LastName'],
            'BillingOrgName'      => '',
            'BillingAddress1'     => $params['Address1'],
            'BillingCity'         => $params['City'],
            'BillingProvince'     => $params['StateProvince'],
            'BillingState'     => $params['StateProvince'],
            'BillingPostalCode'   => $params['PostalCode'],
            'BillingCountry'      => $params['Country'],
            'BillingPhone'        => $this->_validatePhone($params['Phone'], $params['Country']),
            'BillingEmailAddress' => $params['EmailAddress']
        );

        if ( isset($params['Tech E-Mail']) && $params['Tech E-Mail'] != '' ) {
            $moreArgs = array(
                'TechFName'        => $params['Tech First Name'],
                'TechLname'        => $params['Tech Last Name'],
                'TechOrgName'      => $params['Tech Organization'],
                'TechJobTitle'      => $params['Tech Job Title'],
                'TechAddress1'     => $params['Tech Address'],
                'TechCity'         => $params['Tech City'],
                'TechProvince'     => $params['Tech State'],
                'TechState'     => $params['Tech State'],
                'TechPostalCode'   => $params['Tech Postal Code'],
                'TechCountry'      => $params['Tech Country'],
                'TechPhone'        => $this->_validatePhone($params['Tech Phone'], $params['Tech Country']),
                'TechEmailAddress' => $params['Tech E-Mail']
            );
            $arguments = array_merge($arguments, $moreArgs);
        } else {
            $moreArgs = array(
                'TechFName'        => $params['FirstName'],
                'TechLname'        => $params['LastName'],
                'TechOrgName'      => $params['OrganizationName'],
                'TechJobTitle'      => 'N/A',
                'TechAddress1'     => $params['Address1'],
                'TechCity'         => $params['City'],
                'TechProvince'     => $params['StateProvince'],
                'TechState'     => $params['StateProvince'],
                'TechPostalCode'   => $params['PostalCode'],
                'TechCountry'      => $params['Country'],
                'TechPhone'        => $this->_validatePhone($params['Phone'], $params['Country']),
                'TechEmailAddress' => $params['EmailAddress'],
            );
            $arguments = array_merge($arguments, $moreArgs);
        }

        $response = $this->_makeRequest($params, $arguments);
        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        CE_Lib::log(4, 'Cert Configure Cert: ' . print_r($response, true));

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        }
    }


    private function purchaseCert($params)
    {
        $arguments = array(
            'command'           => 'PurchaseServices',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'Service'           => $this->getServiceNameById($params['typeId']),
            'NumYears'          => $params['numYears']
        );

        $response = $this->_makeRequest($params, $arguments);
        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        CE_Lib::log(4, print_r($response, true));

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower(strval($response->Done)) == 'true' ) {
            return intval($response->certid);
        }
        return -1;
    }

    function doParseCSR($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'command'           => 'CertParseCSR',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CSR'               => $params['CSR'],
            'CertID'            => $params['certId']
        );

        $response = $this->_makeRequest($params, $arguments);
        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower($response->CertParseCSR->Success) == 'true' ) {
            $return = array();

            $return['domain'] = strval($response->CertParseCSR->DomainName);
            $return['email'] = strval($response->CertParseCSR->Email);
            $return['city'] = strval($response->CertParseCSR->Locality);
            $return['state'] = strval($response->CertParseCSR->State);
            $return['country'] = strval($response->CertParseCSR->Country);
            $return['organization'] = strval($response->CertParseCSR->Organization);
            $return['ou'] = strval($response->CertParseCSR->OrganizationUnit);
        }

        return $return;
    }

    function doGetCertStatus($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'command'           => 'CertGetCertDetail',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId']
        );
        $response = $this->_makeRequest($params, $arguments);

        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower(strval($response->Done)) == 'true' ) {
            $expirationDate = strval($response->CertGetCertDetail->ExpirationDate);
            $userPackage->setCustomField('Certificate Expiration Date', $expirationDate);

            $status = strval($response->CertGetCertDetail->CertStatus);
            if ( $status == 'Certificate Issued' || $status == 'Approved by Domain Owner' ) {
                // cert is issued, so mark our internal status as issued so we don't poll anymore.
                $userPackage->setCustomField('Certificate Status', SSL_CERT_ISSUED_STATUS);
            }
            return $status;
        }
    }

    function doResendApproverEmail($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'command'           => 'CertResendApproverEmail',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId']
        );
        $response = $this->_makeRequest($params, $arguments);

        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower(strval($response->CertResendApproverEmail->Success)) == 'true' ) {
            return 'Successfully resent approver email.';
        }
    }

    function doCancelConfiguration($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'command'           => 'CertModifyOrder',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId']
        );
        $response = $this->_makeRequest($params, $arguments);

        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        CE_Lib::log(4, print_r($response, true));

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower(strval($response->CertModifyOrder->Done)) == 'true' ) {
            return 'Successfully cancelled configuration of certificate.';
        }
    }

    function doResendFulfillmentEmail($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'command'           => 'CertResendFulfillmentEmail',
            'uid'               => $params['Login'],
            'pw'                => $params['Password'],
            'CertID'            => $params['certId']
        );
        $response = $this->_makeRequest($params, $arguments);

        if (!is_object($response)) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to communicate with Enom', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        CE_Lib::log(4, print_r($response, true));

        if ( $response->ErrCount > 0 ) {
            throw new CE_Exception('eNomSSL Plugin Error: ' . $response->errors->Err1);
        } else if ( strtolower(strval($response->CertResendFulfillmentEmail->Success)) == 'true' ) {
            return 'Successfully resent fulfillment email.';
        }

    }

    function _makeRequest($params, $arguments)
    {
        include_once 'library/CE/NE_Network.php';

        // default paramters
        if (!isset($params['secure'])) {
            $params['secure'] = true;
        }
        if (!isset($params['test'])) {
            $params['test'] = false;
        }

        $request = 'https://';

        if (@$this->settings->get('plugin_enomssl_Use testing server')) {
            $request .= 'resellertest.enom.com/interface.asp';
        } else {
            $request .= 'reseller.enom.com/interface.asp';
        }

        $arguments['responsetype'] = 'XML';

        $i = 0;
        foreach ($arguments as $name => $value) {
            $value = urlencode($value);
            if (!$i) {
                $request .= "?$name=$value";
            } else {
                $request .= "&$name=$value";
            }
            $i++;
        }

        CE_Lib::log(4, 'eNomSSL Params: '. print_r($arguments, true));
        $response = NE_Network::curlRequest($this->settings, $request, false, false, true);

        if (is_a($response, 'CE_Error')) {
            throw new Exception ($response);
        }
        if (!$response) {
            return false;   // don't want xmlize an empty array
        }

        libxml_use_internal_errors(true);
        $response = simplexml_load_string($response);
        if ( !$response ) {
            throw new CE_Exception('eNomSSL Plugin Error: Failed to load XML');
        }

        return $response;
    }

    function getCertificateTypes()
    {
        return array (
            20 => 'GeoTrust QuickSSL Premium',
            21 => 'GeoTrust True BusinessID',
            23 => 'RapidSSL',
            24 => 'GeoTrust TrueBizID with EV',
            26 => 'GeoTrust QuickSSL',
            27 => 'GeoTrust TrueBizID Wildcard',
            180 => 'Verisign Secure Site',
            181 => 'Verisign Secure Site Pro',
            182 => 'Verisign Secure Site EV',
            183 => 'Verisign Secure Site Pro EV',
            211 => 'Comodo Essential',
            212 => 'Comodo Instant',
            213 => 'Comodo Premium Wildcard',
            214 => 'Comodo Essential Wildcard',
            221 => 'Comodo EV',
            222 => 'Comodo EV SGC',
            285 => 'RapidSSL Wildcard'
        );
    }

    private function getServiceNameById($id)
    {
        switch ( $id ) {
            case SSL_CERT_RAPIDSSL:
                return 'Certificate-RapidSSL-RapidSSL';
            case SSL_CERT_RAPIDSSL_WILDCARD:
                return 'Certificate-RapidSSL-RapidSSL-Wildcard';
            case SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM:
                return 'Certificate-GeoTrust-QuickSSL-Premium';
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID:
                return 'Certificate-GeoTrust-TrueBizID';
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV:
                return 'Certificate-GeoTrust-TrueBizID-EV';
            case SSL_CERT_GEOTRUST_QUICKSSL:
                return 'Certificate-GeoTrust-QuickSSL';
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD:
                return 'Certificate-GeoTrust-TrueBizID-Wildcard';
            case SSL_CERT_VERISIGN_SECURE_SITE:
                return 'Certificate-VeriSign-Secure-Site';
            case SSL_CERT_VERISIGN_SECURE_SITE_PRO:
                return 'Certificate-VeriSign-Secure-Site-Pro';
            case SSL_CERT_VERISIGN_SECURE_SITE_EV:
                return 'Certificate-VeriSign-Secure-Site-EV';
            case SSL_CERT_VERISIGN_SECURE_SITE_PRO_EV:
                return 'Certificate-VeriSign-Secure-Site-Pro-EV';
            case SSL_CERT_COMODO_ESSENTIAL:
                return 'Certificate-Comodo-Essential';
            case SSL_CERT_COMODO_INSTANT:
                return 'Certificate-Comodo-Instant';
            case SSL_CERT_COMODO_PREMIUM_WILDCARD:
                return 'Certificate-Comodo-Premium-Wildcard';
            case SSL_CERT_COMODO_ESSENTIAL_WILDCARD:
                return 'Certificate-Comodo-Essential-Wildcard';
            case SSL_CERT_COMODO_EV:
                return 'Certificate-Comodo-EV';
            case SSL_CERT_COMODO_EV_SGC:
                return 'Certificate-Comodo-EV-SGC';
        }
    }

    function getWebserverTypes($type)
    {
        $serviceName = $this->getServiceNameById($type);
        if ( substr($serviceName, 0, 18) == 'Certificate-Comodo' ) {
            $type = 'comodo';
        } else {
            $type = 'geo';
        }

        if ( strtolower($type) == 'comodo' ) {
            $return = array (
                1000 => 'Otherold1001 AOL',
                1002 => 'Apache/ModSSL',
                1002 => 'Apache/ModSSLv',
                1003 => 'Apache-SSL (Ben-SSL, not Strong-hold)',
                1004 => 'C2Net Strongholdold">1005 Cobalt Raq',
                1006 => 'Covalent Server Software',
                1007 => 'IBM HTTP Server',
                1008 => 'IBM Internet Connection Server',
                1009 => 'iPlanet',
                1010 => 'Java Web Server (Javasoft / Sun)',
                1011 => 'Lotus Domino',
                1012 => 'Lotus Domino Go!',
                1013 => 'Microsoft IIS 1.x to 4.x',
                1014 => 'Microsoft IIS 5.x and later',
                1015 => 'Netscape Enterprise Server',
                1016 => 'Netscape FastTrack',
                1017 => 'Novell Web Server',
                1018 => 'Oracle',
                1019 => 'Quid Pro Quo',
                1020 => 'R3 SSL Server',
                1021 => 'Raven SSL',
                1022 => 'RedHat Linux',
                1023 => 'SAP Web Application Server',
                1024 => 'Tomcat',
                1025 => 'Website Professional',
                1026 => 'WebStar 4.x and later',
                1027 => 'Web Ten ( from Tenon)',
                1028 => 'Zeus Web Se rver',
                1029 => 'Ensim',
                1030 => 'Plesk',
                1031 => 'WHM/cPanel',
                1032 => 'H-Sphere'
            );
        } else {
            $return = array (
                1 => 'Apache + MOD SSL',
                2 => 'Apache + Raven',
                3 => 'Apache + SSLeay',
                4 => 'C2Net Stronghold',
                7 => 'IBM HTTP',
                8 => 'iPlanet Server 4.1',
                9 => 'Lotus Domino Go 4.6.2.51',
                10 => 'Lotus Domino Go 4.6.2.6+',
                11 => 'Lotus Domino 4.6+',
                12 => 'Microsoft IIS 4.0',
                13 => 'Microsoft IIS 5.0',
                14 => 'Netscape Enterprise/FastTrack',
                17 => 'Zeus v3+',
                18 => 'Other',
                20 => 'Apache + OpenSSL',
                21 => 'Apache 2',
                22 => 'Apache + ApacheSSL',
                23 => 'Cobalt Series',
                24 => 'Cpanel',
                25 => 'Ensim',
                26 => 'Hsphere',
                27 => 'Ipswitch',
                28 => 'Plesk',
                29 => 'Jakart-Tomcat',
                30 => 'WebLogic (all versions)',
                31 => 'O’Reilly WebSite Professional',
                32 => 'WebStar',
                33 => 'Microsoft IIS 6.0'
            );
        }
        return $return;
    }

    function _validatePhone($phone, $country)
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


    function getAvailableActions($userPackage)
    {
        $actions = array();
        $params['userPackageId'] = $userPackage->id;
        try {
            $status = $this->doGetCertStatus($params);

            if ( $status == 'Awaiting Configuration' || $status == 'Rejected by Customer' ) {
                $actions[] = 'Purchase';
            } else if ( $status == 'Processing' || $status == 'Approval email sent' ) {
                $actions[] = 'CancelConfiguration (Cancel Configuration)';
                $actions[] = 'ResendApproverEmail (Resend Approver Email)';
            } else if ( $status == 'Certificate Issued' || $status == 'Approved by Domain Owner' ) {
                $actions[] = 'ResendFulfillmentEmail (Resend Fulfillment Email)';
                //} else if ( $status == 'Certificate Issued' ) {
            }
        } catch ( CE_Exception $e ) {
            $actions[] = 'Purchase';
        }

        return $actions;
    }
}
