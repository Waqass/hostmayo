<?php

require_once 'modules/admin/models/SSLPlugin.php';

class PluginServertastic extends SSLPlugin
{
    public $mappedTypeIds = array (
        SSL_CERT_RAPIDSSL                               => 'RapidSSL',
        SSL_CERT_RAPIDSSL_WILDCARD                      => 'RapidSSLWildcard',
        SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM              => 'QuickSSLPremium',
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID               => 'TrueBizID',
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV            => 'TrueBizIDEV',
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD      => 'TrueBizIDWildcard',
        SSL_CERT_VERISIGN_SECURE_SITE                   => 'SecureSite',
        SSL_CERT_VERISIGN_SECURE_SITE_PRO               => 'SecureSitePro',
        SSL_CERT_VERISIGN_SECURE_SITE_EV                => 'SecureSiteEV',
        SSL_CERT_VERISIGN_SECURE_SITE_PRO_EV            => 'SecureSiteProEV',
        SSL_CERT_THAWTE_SSL123                          => 'SSL123',
        SSL_CERT_THAWTE_SGC_SUPERCERT                   => 'SGCSuperCerts',
        SSL_CERT_THAWTE_SSL_WEBSERVER                   => 'SSLWebServer',
        SSL_CERT_THAWTE_SSL_WEBSERVER_EV                => 'SSLWebServerEV',
        SSL_CERT_THAWTE_SSL_WEBSERVER_WILDCARD          => 'SSLWebServerWildCard'
    );

    public $usingInviteURL = true;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('ServerTastic')
                               ),
            lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use ServerTastic\'s testing environment, so that transactions are not actually made. For this to work, you must first register you server\'s ip in ServerTastic\'s testing environment, and your server\'s name servers must be registered there as well.'),
                                'value'         =>0
                               ),
            lang('API Key') => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter your API Key here.'),
                                'value'         =>''
                               ),
             lang('Actions') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain isn\'t registered)'),
                                'value'         => 'Purchase,CancelConfiguration (Cancel Configuration),ResendInviteEmail (Resend Invite Email),ResendFulfillmentEmail (Resend Fulfillment Email)'
                                )
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
    }

    private function purchaseCert($params)
    {
        // Sort out number of years the cert if for.
        $years = "-12";
        if ($params['numYears'] == '1') { $years = "-12"; }
        if ($params['numYears'] == '2') { $years = "-24"; }
        if ($params['numYears'] == '3') { $years = "-36"; }
        if ($params['numYears'] == '4') { $years = "-48"; }

        $arguments = array(
            'st_product_code'               => $this->getServiceNameById($params['typeId']) . $years,
            'api_key'                       => $params['API Key'],
            'end_customer_email'            => $params['EmailAddress'],
            'reseller_unique_reference'     => md5(time()),
            'server_count'                  => 1,
            'integration_source_id'         => 4 // CE integration source, to tell them it's from a CE instance
        );

        $response = $this->_makeRequest('/order/place', $arguments);
        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        if ( isset($response->success) ) {
            return $response->reseller_order_id;
        }
    }

    function doParseCSR($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'api_key'           => $params['API Key'],
            'reseller_order_id' => $params['certId']
        );

        $response = $this->_makeRequest('/order/review', $arguments);
        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        $return = array();
        if ( isset($response->success) ) {
            $return['non_csr'] = true;
            $return['domain'] = $response->domain_name;
            $return['info']['Order Status'] = $response->order_status;
            $return['info']['Invite URL'] = $response->invite_url;
            $return['info']['Domain Name'] = $response->domain_name;
            $return['info']['Customer EMail'] = $response->end_customer_email;
            $return['info']['Organization Name'] = $response->organisation_info->name;
            $return['info']['Organization Division'] = $response->organisation_info->division;
            $return['info']['Organization City'] = $response->organisation_info->address->city;
            $return['info']['Organization Region'] = $response->organisation_info->address->region;
            $return['info']['Organization Country'] = $response->organisation_info->address->country;
            $return['info']['Approver EMail'] = $response->approver_email_address;
        }
        return $return;
    }

    function doGetCertStatus($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'api_key'               => $params['API Key'],
            'reseller_order_id'     => $params['certId']
        );
        $response = $this->_makeRequest('/order/review', $arguments);

        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        if ( isset($response->success) ) {
            $status = strval($response->order_status);

            if ( $status == 'Completed' ) {
                // cert is issued, so mark our internal status as issued so we don't poll anymore.
                $userPackage->setCustomField('Certificate Status', SSL_CERT_ISSUED_STATUS);
            }
            return $status;
        }
    }

    function doResendInviteEmail($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'api_key'               => $params['API Key'],
            'email_type'            => 'Invite',
            'reseller_order_id'     => $params['certId']
        );
        $response = $this->_makeRequest('/order/resendemail', $arguments);

        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        if ( isset($response->success) ) {
            return 'Successfully resent invite e-mail.';
        }
    }

    function doResendApproverEmail($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'api_key'               => $params['API Key'],
            'email_type'            => 'Approver’',
            'reseller_order_id'     => $params['certId']
        );
        $response = $this->_makeRequest('/order/resendemail', $arguments);

        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        if ( isset($response->success) ) {
            return 'Successfully resent approver e-mail.';
        }
    }

    function doResendFulfillmentEmail($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'api_key'               => $params['API Key'],
            'email_type'            => 'Fulfillment’]’',
            'reseller_order_id'     => $params['certId']
        );
        $response = $this->_makeRequest('/order/resendemail', $arguments);

        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        if ( isset($response->success) ) {
            return 'Successfully resent fulfillment e-mail.';
        }
    }

    function doCancelConfiguration($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $arguments = array(
            'api_key'               => $params['API Key'],
            'reseller_order_id'     => $params['certId']
        );
        $response = $this->_makeRequest('/order/cancel', $arguments);

        if (!is_object($response)) throw new CE_Exception('ServerTastic Plugin Error: Failed to communicate with ServerTastic', EXCEPTION_CODE_CONNECTION_ISSUE);

        if ( isset($response->success) ) {
            $userPackage->setCustomField("Certificate Id", '');
            return 'Successfully cancelled configuration of certificate.';
        }
    }

    function _makeRequest($url, $arguments)
    {
        require_once 'library/CE/NE_Network.php';

        $request = 'https://';

        if (@$this->settings->get('plugin_ServerTastic_Use testing server')) $request .= 'test-api.servertastic.com';
        else $request .= 'api.servertastic.com';

        $request .= '/ssl' . $url;

        $i = 0;
        foreach ($arguments as $name => $value) {
            $value = urlencode($value);
            if (!$i) $request .= "?$name=$value";
            else $request .= "&$name=$value";
            $i++;
        }

        CE_Lib::log(4, 'ServerTastic Params: '. print_r($arguments, true));
        $response = NE_Network::curlRequest($this->settings, $request, false, false, true);

        if (is_a($response, 'NE_Error')) throw new CE_Exception ($response);
        if (!$response) return false;   // don't want xmlize an empty array

        $response = simplexml_load_string($response);

        if ( isset($response->error) ) {
            throw new CE_Exception ('ServerTastic Plugin Error: ' . $response->error->message);
        }

        return $response;
    }

    function getAvailableActions($userPackage)
    {
        $actions = array();
        $params['userPackageId'] = $userPackage->id;
        try {
            $status = $this->doGetCertStatus($params);

            if ( $status == 'Cancelled' || $status == 'Roll Back' ) {
                $actions[] = 'Purchase';
            } else if ( $status == 'Awaiting Customer Verification' || $status == 'Awaiting Provider Approval' ) {
                $actions[] = 'CancelConfiguration (Cancel Configuration)';
                $actions[] = 'ResendApproverEmail (Resend Approver Email)';
            } else if ( $status == 'Invite Available' ) {
                $actions[] = 'CancelConfiguration (Cancel Configuration)';
                $actions[] = 'ResendInviteEmail (Resend Invite Email)';
            } else if ( $status == 'Completed' ) {
                $actions[] = 'ResendFulfillmentEmail (Resend Fulfillment Email)';
            }
        } catch ( CE_Exception $e ) {
            $actions[] = 'Purchase';
        }

        return $actions;
    }

    function getCertificateTypes()
    {
        return array (
            'RapidSSL' => 'RapidSSL',
            'RapidSSLWildcard' => 'RapidSSL Wildcard',
            'QuickSSLPremium' => 'GeoTrust QuickSSL Premium',
            'TrueBizID' => 'GeoTrust True BusinessID',
            'TrueBizIDEV' => 'GeoTrust TrueBizID with EV',
            'TrueBizIDWildcard' => 'GeoTrust TrueBizID Wildcard',
            'SecureSite' => 'Verisign Secure Site',
            'SecureSitePro' => 'Verisign Secure Site Pro',
            'SecureSiteEV' => 'Verisign Secure Site EV',
            'SecureSiteProEV' => 'Verisign Secure Site Pro EV',
            'SSL123' => 'Thawte SSL Cert',
            'SGCSuperCerts' => 'Thawte SGC SuperCert',
            'SSLWebServer' => 'Thawte SSL Web Server',
            'SSLWebServerEV' => 'Thawte SSL Web Server EV',
            'SSLWebServerWildCard' => 'Thawte SSL Wildcard',
        );
    }

    private function getServiceNameById($id)
    {
        switch ( $id ) {
            case SSL_CERT_RAPIDSSL:
                return 'RapidSSL';
            case SSL_CERT_RAPIDSSL_WILDCARD:
                return 'RapidSSLWildcard';
            case SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM:
                return 'QuickSSLPremium';
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID:
                return 'TrueBizID';
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV:
                return 'TrueBizIDEV';
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD:
                return 'TrueBizIDWildcard';
            case SSL_CERT_VERISIGN_SECURE_SITE:
                return 'SecureSite';
            case SSL_CERT_VERISIGN_SECURE_SITE_PRO:
                return 'SecureSitePro';
            case SSL_CERT_VERISIGN_SECURE_SITE_EV:
                return 'SecureSiteEV';
            case SSL_CERT_VERISIGN_SECURE_SITE_PRO_EV:
                return 'SecureSiteProEV';
            case SSL_CERT_THAWTE_SSL123:
                return 'SSL123';
            case SSL_CERT_THAWTE_SGC_SUPERCERT:
                return 'SGCSuperCerts';
            case SSL_CERT_THAWTE_SSL_WEBSERVER:
                return 'SSLWebServer';
            case SSL_CERT_THAWTE_SSL_WEBSERVER_EV:
                return 'SSLWebServerEV';
            case SSL_CERT_THAWTE_SSL_WEBSERVER_WILDCARD:
                return 'SSLWebServerWildCard';
        }
    }
    function getWebserverTypes($type)
    {
        return array();
    }
}
