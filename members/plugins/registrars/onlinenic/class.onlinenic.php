<?php

require_once dirname(__FILE__).'/../../../library/CE/XmlFunctions.php';

/**
 * A class to hand domain lookups and registrations to OnlineNIC.
 * @package Clientexec
 * @author Juan David Bol�var
 * @version August 23, 2007
 */
class OnlineNIC
{
    var $rpcHandlerVersion = '0.9';
    var $rpcHandlerPort = 20001;
    var $host;
    var $user;
    var $key;
    var $fp;

    /**
     * Constructs a new OnlineNIC object.
     *
     * @param string $host The OnlineNIC URL
     * @param string $user The reseller username
     * @param string $key The resellers password
     * @return OnlineNIC An OnlineNIC object
     */
    function OnlineNIC($host, $user, $key)
    {
        $this->host = $host;
        $this->user = $user;
        $this->key = $key;
        $this->fp = false;
    }

    /**
     * Login to OnlineNIC.
     *
     * @return an xmlized array result - use print_r() to view the structure
     */
    function login()
    {
        $this->connect();
        $clTRID = $this->getClTrid();
        $chk_sum = md5($this->user . md5($this->key) . $clTRID . "login");
        $request = "
            <creds>
                <clID>".$this->user."</clID>
                <options>
                    <version>1.0</version>
                    <lang>en</lang>
                </options>
            </creds>
            <clTRID>".$clTRID."</clTRID>
            <login><chksum>".$chk_sum."</chksum></login>";

        $response = $this->_buildAndSendRequest($request);
        if ($response == null) {
            return null;
        }
        $response = XmlFunctions::xmlize($response);
        return $response['epp']['#']['response'][0]['#'];
    }

    /**
     * Logout of OnlineNIC.
     *
     * @return an xmlized array result - use print_r() to view the structure
     */
    function logout()
    {
        $clTRID = $this->getClTrid();
        $chk_sum = md5($this->user . md5($this->key) . $clTRID . "logout");
        $request = "
            <logout/>
            <unspec/>
            <clTRID>".$clTRID."</clTRID>
            <chksum>".$chk_sum."</chksum>";

        $response = $this->_buildAndSendRequest($request);
        $this->disconnect();
        if ($response == null) {
            return null;
        }

        $response = XmlFunctions::xmlize($response);
        return $response['epp']['#']['response'][0]['#'];
    }

    /**
     * Checks the availibility of a domain.
     *
     * @param string $sld The domain name to lookup (without the tld extension)
     * @param string $tld The domain tld extension
     * @return an xmlized array result - use print_r() to view the structure
     */
    function lookup_domain($sld, $tld)
    {
		$domainType = $this->getDomainType($tld);

        $clTRID = $this->getClTrid();

        $chk_sum = md5($this->user.md5($this->key).$clTRID."chkdomain".$domainType.$sld.".".$tld);
        $request = "
            <check>
                <domain:check xmlns:domain='urn:iana:xml:ns:domain-1.0' xsi:schemaLocation='urn:iana:xml:ns:domain-1.0 domain-1.0.xsd'>
                    <domain:type>".$domainType."</domain:type>
                    <domain:name>".$sld.".".$tld."</domain:name>
                </domain:check>
            </check>
            <unspec/>
            <clTRID>".$clTRID."</clTRID>
            <chksum>".$chk_sum."</chksum>";

        $response = $this->_buildAndSendRequest($request);
        if ($response == null) {
            return null;
        }

        $response = XmlFunctions::xmlize($response);
        return $response['epp']['#']['response'][0]['#'];
    }

    /**
     * Creates a contact with OnlineNIC.
     *
     * @param array $params An array of parameters.
     * @return an xmlized array result - use print_r() to view the structure
     */
    function create_contact($params)
    {

        $clTRID = $this->getClTrid();
        $chk_sum = md5($this->user . md5($this->key) . $clTRID . "crtcontact".$params['RegistrantFirstName']." ".$params['RegistrantLastName'].$params['RegistrantOrganizationName'].$params['RegistrantEmailAddress']);

        // chk_sum = md5(s_customer_id + md5(password) + clTRID + "crtcontact" + contact:name + contact:org + contact:email)
        // not sure if
        //     s_customer_id
        // must be
        //     $this->user
        //
        // not sure if
        //     contact:name
        // must be
        //     $params['RegistrantFirstName']." ".$params['RegistrantLastName']
        // or
        //     $params['DomainUsername']

        $NexusCategory = $params['ExtendedAttributes']['us_nexus'];

        switch($NexusCategory) {
            case 'C31':
            case 'C32':
                $NexusCategory .= "/".$params['RegistrantCountry'];
                break;
        }
		$domainType = $this->getDomainType($params['tld']);
      
        $request = "
            <create>
                <contact:create xmlns:contact=\"urn:iana:xml:ns:contact-1.0\" xsi:schemaLocation=\"urn:iana:xml:ns:con tact-1.0 contact-1.0.xsd\">
                    <contact:domaintype>".$domainType."</contact:domaintype>
                    <contact:ascii>
                        <contact:name>".$params['RegistrantFirstName']." ".$params['RegistrantLastName']."</contact:name>
                        <contact:org>".$params['RegistrantOrganizationName']."</contact:org>
                        <contact:addr>
                            <contact:street1>".$params['RegistrantAddress1']."</contact:street1>
                            <contact:street2></contact:street2>
                            <contact:city>".$params['RegistrantCity']."</contact:city>
                            <contact:sp>".$params['RegistrantStateProvince']."</contact:sp>
                            <contact:pc>".$params['RegistrantPostalCode']."</contact:pc>
                            <contact:cc>".$params['RegistrantCountry']."</contact:cc>
                        </contact:addr>
                    </contact:ascii>
                    <contact:voice>".$params['RegistrantPhone']."</contact:voice>
                    <contact:fax>".$params['RegistrantPhone']."</contact:fax>
                    <contact:email>".$params['RegistrantEmailAddress']."</contact:email>
                    <contact:pw>".$params['DomainPassword']."</contact:pw>
                </contact:create>
            </create>
            <unspec>AppPurpose=".$params['ExtendedAttributes']['us_purpose']." NexusCategory=".$NexusCategory."</unspec>
            <clTRID>".$clTRID."</clTRID>
            <chksum>".$chk_sum."</chksum>";

        // not sure if
        //     <contact:pw>".$params['DomainPassword']."</contact:pw>
        // must be
        //     $params['DomainPassword']
        //
        // not sure if
        //     <contact:name>".$params['RegistrantFirstName']." ".$params['RegistrantLastName']."</contact:name>
        // must be
        //     $params['RegistrantFirstName']." ".$params['RegistrantLastName']
        // or
        //     $params['DomainUsername']

        $response = $this->_buildAndSendRequest($request);
        if ($response == null) {
            return null;
        }
        $response = XmlFunctions::xmlize($response);
        return $response['epp']['#']['response'][0]['#'];
    }


    /**
     * Registers a domain name with OnlineNIC.
     *
     * @param array $params An array of parameters.
     * @return an xmlized array result - use print_r() to view the structure
     */
    function register_domain($params)
    {
		$domainType = $this->getDomainType($params['tld']);
       
        $clTRID = $this->getClTrid();
        $chk_sum = md5($this->user . md5($this->key) . $clTRID . "crtdomain" . $domainType . $params['domain'] . $params['NumYears'] . $params['NS1']['hostname'] . $params['NS2']['hostname'] . $params['ContactID'] . $params['ContactID'] . $params['ContactID'] . $params['ContactID'] . $params['DomainPassword']);

        // chk_sum = md5(s_customer_id + md5(password) + clTRID + "crtdomain" + domain:type + domain:name + domain:period + domain:ns1 + domain:ns2 + domain:registrant + domain:contact type="admin" + domain:contact type="tech" + domain:contact type="billing" + domain:authInfo type="pw")
        // not sure if
        //     s_customer_id
        // must be
        //     $this->user


        $request = "
            <create>
                <domain:create>
                    <domain:type>".$domainType."</domain:type>
                    <domain:name>".$params['domain']."</domain:name>
                    <domain:period>".$params['NumYears']."</domain:period>
                    <domain:ns1>".$params['NS1']['hostname']."</domain:ns1>
                    <domain:ns2>".$params['NS2']['hostname']."</domain:ns2>
                    <domain:registrant>".$params['ContactID']."</domain:registrant>
                    <domain:contact type=\"admin\">".$params['ContactID']."</domain:contact>
                    <domain:contact type=\"tech\">".$params['ContactID']."</domain:contact>
                    <domain:contact type=\"billing\">".$params['ContactID']."</domain:contact>
                    <domain:authInfo type=\"pw\">".$params['DomainPassword']."</domain:authInfo>
                </domain:create>
            </create>
            <clTRID>".$clTRID."</clTRID>
            <chksum>".$chk_sum."</chksum>";

        $response = $this->_buildAndSendRequest($request);
        if ($response == null) {
            return null;
        }
        $response = XmlFunctions::xmlize($response);
        return $response['epp']['#']['response'][0]['#'];
    }

    /**
     * Private function used for building the xml request and sending it.
     *
     * @param String $request The xml request body
     * @return The xml string response
     */
    function _buildAndSendRequest($request)
    {
        $request = '
            <?xml version=\'1.0\' encoding="UTF-8" standalone="no" ?>
            <epp>
                <command>
                    '.$request.'
                </command>
            </epp>';

        CE_Lib::log(4, 'Request made to OnlineNic: '.$request);
        fputs ($this->fp, $request);

        $response = "";
        while (false !== ($char = fgetc($this->fp))) {
            $response .= $char;
            if ($char == '>' && strpos($response, '</epp>') !== false) break;
        }
        CE_Lib::log(4, "OnlineNIC response: " . $response);
        // drop the headers so we can xmlize it
        $arrResponse = explode("\n", $response);
        $response = "";
        $flag = false;
        foreach ($arrResponse as $line)
        {
            if ($flag) $response .= $line."\n";
            else $flag = true;
        }
        return $response;
    }

    /**
     * Generates a Transaction id, which must be unique.
     *
     * @return a Transaction id string
     */
    function getClTrid()
    {
    	return "ClientExec-".time()."-".rand();
    }

    function connect()
    {
        CE_Lib::log(4, 'Connecting to OnlineNic');
        @set_time_limit(200);
        $this->fp = @fsockopen ("$this->host", $this->rpcHandlerPort, $errno, $errstr, 30);
        if (!$this->fp) {
            CE_Lib::log(4, "Couldn't connect to OnlineNIC: $errno, $errstr");
            throw new CE_Exception("Couldn't connect to OnlineNIC: $errno, $errstr");
            return;
        }
        CE_Lib::log(4, 'OnlineNic connection complete');
        $response = "";
        while (false !== ($char = fgetc($this->fp))) {
            $response .= $char;
            if ($char == '>' && strpos($response, '</epp>') !== false) break;
        }
        CE_Lib::log(4, 'Welcome response: '. $response);
    }

    function disconnect()
    {
        if ($this->fp)
            fclose($this->fp);
    }
	
	private function getDomainType($tld)
	{
		switch($tld) {
            case 'com':
            case 'net':
            case 'org':
                $domainType = 0;
                break;
            case 'biz':
                $domainType = 800;
                break;
            case 'info':
                $domainType = 805;
                break;
            case 'us':
                $domainType = 806;
                break;
            case 'in':
                $domainType = 808;
                break;
            case 'cn':
                $domainType = 220;
                break;
		   case 'co':
                $domainType = 908;
                break;
        }
		
		return $domainType;
	}
}