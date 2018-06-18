<?php
/*
 * NETIM Domain Registrar Plugin
 *
 * @category Plugin
 * @package  ClientExec
 * @author   Nicolas Delannoy
 * @version  1.0
 * @Date  2011-08-01
*/

require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'modules/domains/models/ICanImportDomains.php';
define ('API_SRC',"clientexec");
define ('PROD_URL',"http://drs.netim.com/current/api.wsdl");
define ('TEST_URL',"http://drs.tryout.netim.com/current/api.wsdl");

class PluginNetim extends RegistrarPlugin implements ICanImportDomains
{
    function getVariables()
    {
        $variables = array(
                lang('Plugin Name') => array (
                        'type'          =>'hidden',
                        'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                        'value'         =>lang('NETIM')
                ),
          lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use NETIM testing environment, so that transactions are not actually made.<BR><BR><strong>Note:</strong> You will first need to register for a test account at https://tryout.netim.com/bin/register_prog.php?PROG=REV'),
                                'value'         =>0
                               ),
   			   lang('Login') => array(
											'type'          =>'text',
											'description'   =>lang('Enter your username for your NETIM reseller account.'),
											'value'         =>''
										   ),

          lang('Password') => array(
											'type'          =>'password',
											'description'   =>lang('Enter the password for your NETIM reseller account.'),
											'value'         =>''
										   ),
				lang('DNS 1') => array(
											'type' 			=>'text',
											'description'   =>'Default nameserver #1',
											'value'			=>'a.ns.netim.net'
											),
				lang('DNS 2') => array(
											'type' 			=>'text',
											'description'   =>'Default nameserver #2',
											'value'			=>'b.ns.netim.net'
											),
				lang('DNS 3') => array(
											'type' 			=>'text',
											'description'   =>'Default nameserver #3',
											'value'			=>''
											),
				lang('DNS 4') => array(
											'type' 			=>'text',
											'description'   =>'Default nameserver #4',
											'value'			=>''
											),
				lang('DNS 5') => array(
											'type' 			=>'text',
											'description'   =>'Default nameserver #5',
											'value'			=>''
											),
                lang('Supported Features')  => array(
                                'type'          => 'label',
                                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* <b>'.lang('All european and generic TLDs').'</b><BR>* '.lang('Get / Set Auto Renew Status').' <br>* '.lang('Get / Set DNS Records').' <br>* '.lang('Get / Set Nameservers').' <br>* '.lang('Get / Set Contact Information').' <br>* '.lang('Get / Set Registrar lock').' <br>* '.lang('Send Transfer Key'),
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
                                'value'         => 'SendTransferKey,RegistrarLock,RegistrarUnlock'
                                ),
                lang('Registered Actions For Customer') => array (
                                'type'          => 'hidden',
                                'description'   => lang('Current actions that are active for this plugin (when a domain is registered)'),
                                'value'         => 'SendTransferKey,RegistrarLock,RegistrarUnlock',
            )
        );

        return $variables;
    }

    function getAPIAddress($params)
    {
      if( !isset($params['Use testing server']) || intval($params['Use testing server']) == 0)
        return PROD_URL;
      else
        return TEST_URL;
    }

    function checkDomain($params)
    {
  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		$domain    = $params['sld'].".".strtolower($params['tld']);
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$status = 5;
  		}

  		# ----------------------------------------------------------------------
  		# Checking domain
  		# ----------------------------------------------------------------------
  		try
  		{
  			$res = $clientSOAP->__soapCall("domainCheck",array($IDSession,$domain));
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			$status = 5;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		# ----------------------------------------------------------------------
  		# Result
  		# ----------------------------------------------------------------------
  		switch($res[0]->result)
  		{
  			case "AVAILABLE":
          $status = 0;
  				break;

  			case "NOT AVAILABLE":
  				if(isset($params['userPackageId']) && !empty($params['userPackageId']))
  					$this->db->query("UPDATE domains SET status = 1 WHERE id = ".$params['userPackageId']);
          $status = 1;
  				break;

  			default:
  				$status = 2;
  				break;
  		}
      $domains[] = array("tld"=>$params['tld'],"domain"=>$params['sld'],"status"=>$status);
      return array("result"=>$domains);
    }

   	function doRegister($params)
    {
		  $userPackage = new UserPackage($params['userPackageId']);
      $orderid = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
      $userPackage->setCustomField("Registrar Order Id",$orderid);
      return $userPackage->getCustomField('Domain Name') . ' registration has been initiated.';
    }

	  function doRegistrarUnlock($params)
    {
		  $userPackage = new UserPackage($params['userPackageId']);
      $orderid = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
      $params['domain'] = strtolower($userPackage->getCustomField('Domain Name'));
		  $params['lock'] = false;
		  $result = $this->setRegistrarLock($params);
		  return "Unlock request for domain name ".$userPackage->getCustomField('Domain Name') . ' has been initiated.';
    }

	  function doRegistrarLock($params)
    {
		  $userPackage = new UserPackage($params['userPackageId']);
      $orderid = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
      $params['domain'] = strtolower($userPackage->getCustomField('Domain Name'));
		  $params['lock'] = true;
		  $result = $this->setRegistrarLock($params);
		  return "Lock request for domain name ".$userPackage->getCustomField('Domain Name') . ' has been initiated.';
    }

	  function doSendTransferKey($params)
	  {
		  $userPackage = new UserPackage($params['userPackageId']);
		  $param=$this->buildRegisterParams($userPackage,$params);
		  $params['domain'] = strtolower($userPackage->getCustomField('Domain Name'));
		  $orderid = $this->sendTransferKey($params);
		  return "Transfer key will be sent to registrant's email address in few instant";
	  }

	  private function parse_additionalfields($owner,$params)
	  {
  		$owner["companyNumber"]	    =	$params["ExtendedAttributes"]["registrant_company_number"];
  		$owner["tmName"]			=	$params["ExtendedAttributes"]["registrant_trademark_name"];
  		$owner["tmNumber"]		    =	$params["ExtendedAttributes"]["registrant_trademark_number"];
  		$owner["tmType"]			=	$params["ExtendedAttributes"]["registrant_trademark_type"];
  		$owner["tmDate"]		    =	$params["ExtendedAttributes"]["registrant_trademark_date"];
  		$owner["vatNumber"]         =   $params["ExtendedAttributes"]["registrant_vat_number"];
  		$owner["birthDate"]		    =	$params["ExtendedAttributes"]["registrant_birth_date"];
  		$owner["birthZipCode"]	    =	$params["ExtendedAttributes"]["registrant_birth_zipcode"];
  		$owner["birthCity"]		    =	$params["ExtendedAttributes"]["registrant_birth_city"];
  		$owner["birthCountry"]	    =	$params["ExtendedAttributes"]["registrant_birth_country"];
  		$owner["idNumber"]		    =	$params["ExtendedAttributes"]["registrant_id_number"];
  		$owner["additional"] 		= 	$params['ExtendedAttributes']["eligibility"];
  		$owner['area'] 				= 	$params['ExtendedAttributes']["registrant_area"];

		  return $owner;
	  }

    function registerDomain($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$duration  = $params["NumYears"];
  		$ns1       = $params["DNS 1"];
  		$ns2       = $params["DNS 2"];
  		$ns3       = $params["DNS 3"];
  		$ns4       = $params["DNS 4"];
  		$ns5       = $params["DNS 5"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
   	  $clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];

  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Registrant Details
  		# ----------------------------------------------------------------------
  		$owner = array();
  		$owner['lastName']     = $params["RegistrantLastName"];
  		$owner['firstName']    = $params["RegistrantFirstName"];
  		if(!empty($params["RegistrantOrganizationName"]))
  			$owner["bodyForm"]	   = "ORG";
  		else
  			$owner["bodyForm"]	   = "IND";
  		$owner['bodyName']     = $params["RegistrantOrganizationName"];
  		$owner['address1']     = $params["RegistrantAddress1"];
  		$owner['address2']     = $params["RegistrantAddress2"];
  		$owner['zipCode']      = $params["RegistrantPostalCode"];
  		$owner['city']         = $params["RegistrantCity"];
  		$owner['area']         = $params["RegistrantStateProvince"];
  		$owner['country']      = $params["RegistrantCountry"];
  		$owner['phone']        = $params["RegistrantPhone"];
  		$owner["fax"]		   = "";
  		$owner['email']        = $params["RegistrantEmailAddress"];
  		$owner["language"]	   = "EN";
  		$owner['isOwner']      = 1;
  		$owner = $this->parse_additionalfields($owner,$params);

  		# ----------------------------------------------------------------------
  		# Call to contactCreate for creating the registrant
  		# ----------------------------------------------------------------------
  		try
  		{
  			$idOwner = $clientSOAP->__soapCall("contactCreate",array($IDSession, (object)($owner) ) );
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("ContactCreate error:<BR>".$fault->getMessage());
  			return false;
  		}

  		$idContactDefault = $username;

  		# ----------------------------------------------------------------------
  		# Local contact service ?
  		# ----------------------------------------------------------------------
  		try
  		{
  			$info = $clientSOAP->__soapCall("domainTldInfo",array($IDSession, $tld) );
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainTldInfo error:<BR>".$fault->getMessage());
  			return false;
  		}

  		if($info->Haslocalcontactservice == 1)
  			$idAdmin = "NETIM4".strtoupper($tld);
  		else
  			$idAdmin = $idContactDefault;

  		# ----------------------------------------------------------------------
  		# Call to domainCreate
  		# ----------------------------------------------------------------------
  		try
  		{
  			$numOpe = $clientSOAP->__soapCall("domainCreate",array($IDSession, $domain, $idOwner, $idAdmin, $idContactDefault, $idContactDefault, $ns1, $ns2, $ns3, $ns4, $ns5, $duration));
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainCreate error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return $numOpe;
    }

    function setAutorenew($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$renewstatus = $params['autorenew'];
  		$domain    = $sld.".".$tld;

   		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainSetPreference for updating the domain
  		# ----------------------------------------------------------------------
  		try
  		{
  			$numOpe = $clientSOAP->domainSetPreference($IDSession,$sld.".".$tld, "auto_renew", $renewstatus);
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainSetPreference error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return $numOpe;
    }

    function getGeneralInfo($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$duration  = $params["NumYears"];
  		$ns1       = $params["nameserver1"];
  		$ns2       = $params["nameserver2"];
  		$ns3       = $params["nameserver3"];
  		$ns4       = $params["nameserver4"];
  		$ns5       = "";
  		$domain    = $sld.".".$tld;

  		if(isset($params['userPackageId']) && !empty($params['userPackageId'])){
  			$userPackage = new UserPackage($params['userPackageId']);
  			$op = $userPackage->getCustomField("Registrar Order Id");
  		}

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];

  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainInfo
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domainInfo = $clientSOAP->__soapCall("domainInfo",array($IDSession, $domain)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainInfo error:<BR>".$fault->getMessage());
  			return false;
  		}

  		if(isset($op) && !empty($op))
  		{
  			# ----------------------------------------------------------------------
  			# Getting Operation
  			# ----------------------------------------------------------------------
  			try
  			{
  				$status = $clientSOAP->__soapCall("queryOpe",array($IDSession, $op)) ;
  			}
  			catch(SoapFault $fault)
  			{
  				$clientSOAP->__soapCall("logout",array($IDSession)) ;
  				throw new Exception("Error when querying orderid $op:<BR>".$fault->getMessage());
  				return false;
  			}
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

		  return array("id" => "", "domain" => $domainInfo->domain, "expiration" => $domainInfo->dateExpiration, "registrationstatus" => $domainInfo->status, "purchasestatus" => $status[0],  "autorenew" => $domainInfo->autorenew);
    }


	  function fetchDomains($params)
    {
  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];

  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Getting Domain List
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domList = $clientSOAP->__soapCall("queryDomainList",array($IDSession, "*")) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession));
  			throw new Exception("Error when fetching domains:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		$total = count($domList);

  		$data = array();
  		foreach($domList as $key => $value)
  		{
  			$tab = explode('.',$value->domain);
  			$sld = $tab[0];
  			$tld = $tab[1];
  			$dateExp = $value->dateExpiration;
  			$data[] =  array("id" => $key, "sld" => $sld, "tld" => strtoupper($tld), "exp" => $dateExp);
  		}
  		$metaData = array();
          $metaData['total'] = $total;
          $metaData['next'] = 0;
          $metaData['start'] = 0;
          $metaData['end'] = $total;
  		$metaData['numPerPage'] = $total;

  		return array( $data, $metaData );
    }

    function getContactInformation ($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];

  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainInfo
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domainInfo = $clientSOAP->__soapCall("domainInfo",array($IDSession, $domain)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainInfo error:<BR>".$fault->getMessage());
  			return false;

  		}

  		# ----------------------------------------------------------------------
  		# Getting Owner's details
  		# ----------------------------------------------------------------------
  		try
  		{
  			$cliInfo = $clientSOAP->__soapCall("contactInfo",array($IDSession,$domainInfo->idOwner)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("contactInfo error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return array( 'Registrant' => array( "Address" =>array("FR",$cliInfo->address1." ".$cliInfo->address2), "Zipcode"=> array("FR",$cliInfo->zipCode), "City" => array("FR",$cliInfo->city), "State" => array("FR",$cliInfo->area ), "Province" => array("FR",$cliInfo->country), "Phone" => array("FR",$cliInfo->phone), "EmailAddress" => array("FR",$cliInfo->email) ));
    }

    function setContactInformation ($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Fromating data
  		# ----------------------------------------------------------------------
  		$owner = array(
              'address1' => $params['Registrant_Address'],
              'address2' => "",
              'city' => $params['Registrant_City'],
              'area' => $params['Registrant_State'],
              'country' => $params['Registrant_Province'],
              'zipCode' => $params['Registrant_Zipcode'],
              'email' => $params['Registrant_EmailAddress'],
              'phone' => $params['Registrant_Phone'],
  			'fax' => ""
          );

  		# ----------------------------------------------------------------------
  		# Call to domainUpdateOwner
  		# ----------------------------------------------------------------------
  		try
  		{
  			$numOpe = $clientSOAP->__soapCall("domainUpdateOwner",array($IDSession,$domain,$owner)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainUpdateOwner error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return $numOpe;
      }

      function getNameServers ($params)
      {
          # ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainInfo
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domainInfo = $clientSOAP->__soapCall("domainInfo",array($IDSession, $sld.".".$tld)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainInfo error:<BR>".$fault->getMessage());
  			return false;

  		}
  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return $domainInfo->ns;
    }

    function setNameServers ($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Calling DomainChangeDNS
  		# ----------------------------------------------------------------------
  		$tabDNS = array();
  		if($params['default'] == false)
  		{
  			foreach($params['ns'] as $key => $value)
  				$tabDNS[] = $value;
  		}
  		else
  		{
  			$tabDNS[] = $params['DNS 1'];
  			$tabDNS[] = $params['DNS 2'];
  		}

  		try
  		{
  			$numOpe = $clientSOAP->__soapCall("domainChangeDNS",array($IDSession,$domain,$tabDNS[0],$tabDNS[1],$tabDNS[2],$tabDNS[3],$tabDNS[4]));
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  		  throw new Exception("domainChangeDNS error:<BR>".$fault->getMessage());
  			return false;
  		}

  		if(isset($IDSession)){
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  		}

  		return array($numOpe);
    }

    function getRegistrarLock($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainInfo
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domainInfo = $clientSOAP->__soapCall("domainInfo",array($IDSession, $sld.".".$tld)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainInfo error:<BR>".$fault->getMessage());
  			return false;

  		}

  		return array($domainInfo->domainIsLock);
      }

      function setRegistrarLock ($params)
      {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$lockstatus = $params['lock'];
  		$domain    = $params['domain'];

   		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainSetPreference for updating the domain
  		# ----------------------------------------------------------------------
  		try
  		{
  			$numOpe = $clientSOAP->domainSetPreference($IDSession,$domain, "registrar_lock", $lockstatus);
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainSetPreference error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return array($numOpe);
      }

      function sendTransferKey($params)
      {
  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		$domain = $params['domain'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Getting EppCode
  		# ----------------------------------------------------------------------
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("domainAuthID",array($IDSession, $domain,true)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainAuthID error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		return true;
    }


    function getDNS ($params)
    {
  		# ----------------------------------------------------------------------
  		# Domain Details
  		# ----------------------------------------------------------------------
  		$tld       = strtolower($params["tld"]);
  		$sld       = $params["sld"];
  		$domain    = $sld.".".$tld;

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Call to domainInfo
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domainInfo = $clientSOAP->__soapCall("domainInfo",array($IDSession, $sld.".".$tld)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("domainInfo error:<BR>".$fault->getMessage());
  			return false;

  		}

  		# ----------------------------------------------------------------------
  		# Getting DNS records
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domZone = $clientSOAP->__soapCall("queryZoneList",array($IDSession,$domain));
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("queryZoneList error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Getting URL redirect
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domRedirect = $clientSOAP->__soapCall("queryWebFwdList",array($IDSession,$domain)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("queryWebFwdList error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Getting records
  		# ----------------------------------------------------------------------
  		$hostrecords = array();
  		$i = 1;
  		foreach($domZone as $value)
  		{
  			//Recheche d'un sous domaine.
  			$tab = explode(".",$value->host);
  			if(count($tab) > 2)
  				$h = $tab[0];
			else if (count($tab) == 2) {
				$h = $value->host;
			}
  			else
  				$h = " ";

  			if(strtoupper($value->type) == "CNAME" || strtoupper($value->type) == "MX" || strtoupper($value->type) == "TXT")
  				$hostrecords[] = array( "id"=> $i,"hostname" => $h, "type" => strtoupper($value->type), "address" => $value->value);
  			else if($value->type == "A" && !empty($h))
  			{
  				$find = false;
  				foreach($domRedirect as $val){
  					$tab2 = explode(".",$val->FQDN);
  					$h2 = $tab2[0];
  					if($h == $h2){
  						if($val->type=="MASKED"){
  							$hostrecords[] = array( "id"=> $i, "hostname" => $h, "type" => "FRAME", "address" => $val->options->protocol."://".$val->target );
  						}else{
  							$hostrecords[] = array( "id"=> $i, "hostname" => $h, "type" => "URL", "address" => $val->options->protocol."://".$val->target );
  						}
  						$find = true;
  					}
  				}
  				if(!$find){
  					$hostrecords[] = array( "id"=> $i, "hostname" => $h, "type" => strtoupper($value->type), "address" => $value->value );
  				}
  			}elseif($value->type != "NS"){
  				$hostrecords[] = array( "id"=> $i, "hostname" => $h, "type" => strtoupper($value->type), "address" =>$value->value );
  			}
  			$i++;
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		$default = true;
  		$types = array('A', 'MX', 'CNAME', 'URL', 'FRAME', 'TXT');
  		return array('records' => $hostrecords, 'types' => $types, 'default' => $default);
    }


    function setDNS ($params)
    {
  		# ----------------------------------------------------------------------
  		# Operation Details
  		# ----------------------------------------------------------------------
  		$clientSOAP = new SoapClient($this->getAPIAddress($params));
  		$username = $params['Login'];
  		$password = $params['Password'];
  		$tld = strtolower($params["tld"]);
  		$sld = $params["sld"];
  		$dnstab = array();
  		$domain = $sld.".".$tld;

  		foreach($params["records"] as $key => $value) {
  			$dnstab[] = array( "hostname" => trim($value["hostname"]), "type" => $value["type"], "address" => $value["address"]);
  		}

  		# ----------------------------------------------------------------------
  		# Connection to the Netim's API
  		# ----------------------------------------------------------------------
  		try
  		{
  			$IDSession = $clientSOAP->__soapCall("login",array($username, $password,"EN",API_SRC)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			throw new Exception("Connexion error: ".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Getting current DNS records
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domZone = $clientSOAP->__soapCall("queryZoneList",array($IDSession,$domain)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("queryZoneList error:<BR>".$fault->getMessage());
  			return false;
  		}

  		# ----------------------------------------------------------------------
  		# Getting current URL redirect
  		# ----------------------------------------------------------------------
  		try
  		{
  			$domRedirect = $clientSOAP->__soapCall("queryWebFwdList",array($IDSession,$domain)) ;
  		}
  		catch(SoapFault $fault)
  		{
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;
  			throw new Exception("queryWebFwdList error:<BR>".$fault->getMessage());
  			return false;
  		}

  		$i=0;
  		//Formatage des anciens parametres.
  		while( $i < count($domZone) || $i < count($domRedirect) ){

  			//Suppression de Zone
  			if($i < count($domZone)){
  				if($domZone[$i]->type != "NS"){

  					//Recherche si un sous domaine existe.
  					$tab = explode(".",$domZone[$i]->host);
  					if(count($tab) > 2){
  						$h = $tab[0];
  					}

  					$delete = true;
  					//Recherche si un redirection existe pour le sous domaine.
  					if($domZone[$i]->type == "A"){
  						foreach($domRedirect as $val){
  							$tab2 = explode(".",$val->FQDN);
  							$h2 = $tab2[0];
  							//Si oui, on annule la suppression de la Zone.
  							if(count($tab2) > 2 && $h == $h2){
  								$delete = false;
  							}
  						}
  					}

  					if($delete){
  						try
  						{
  							$numOpe = $clientSOAP->__soapCall("domainZoneDelete",array($IDSession, $domain,$h, $domZone[$i]->type,$domZone[$i]->value));
  						}
  						catch(SoapFault $fault)
  						{
  							//$clientSOAP->__soapCall("logout",array($IDSession)) ;
  							//throw new Exception("domainZoneDelete error:<BR>".$fault->getMessage());
  							//return false;
  						}
  					}
  				}
  			}

  			//Suppression de Redirection.
  			if($i < count($domRedirect)){
  				try
  				{
  					$numOpe = $clientSOAP->__soapCall("domainWebFwdDelete",array($IDSession,$domRedirect[$i]->FQDN));
  				}
  				catch(SoapFault $fault)
  				{
  					$clientSOAP->__soapCall("logout",array($IDSession)) ;
  					throw new Exception("domainWebFwdDelete error:<BR>".$fault->getMessage());
  					return false;
  				}
  			}
  			$i++;
  		}

  		# ----------------------------------------------------------------------
  		# Setting DNS records
  		# ----------------------------------------------------------------------
  		$counter = 2;
  		$numOpe = -1;
  		foreach ($dnstab as $key=>$values)
  		{
  			$hostname = $values["hostname"];
  			$type = $values["type"];
  			$address = $values["address"];
  			if(!empty($address))
  			{
  				$counter++;
  				switch($type){
  					case "URL":
  					case "FRMAE":
  						//Parametrage de la structure Option
  						$structOptionsFwd = array();
  						if($type == "FRMAE"){
  							$structOptionsFwd["header"] = "";
  							$typeNetim = "MASKED";

  						}elseif($type == "URL"){
  							$structOptionsFwd["header"] = 301;
  							$typeNetim = "DIRECT";

  						}else{
  							$error .= "Unknown Type";
  						}
  						$url = parse_url($address);
  						$p = $url["scheme"];
  						if(empty($p)){
  							$p = "http";
  						}
  						$structOptionsFwd["protocol"] = $p;
  						$structOptionsFwd["title"] = "";
  						$structOptionsFwd["parking"] = "";

  						//Parametrage
  						$structOptionsZone = array();
  						$structOptionsZone["service"] = "";
  						$structOptionsZone["protocol"] = "";
  						$structOptionsZone["ttl"] = "";
  						$structOptionsZone["weight"] = "";
  						$structOptionsZone["port"] = "";
  						$structOptionsZone["priority"] = "";

  						$fqdn = $hostname.".".$domain;
  						try
  						{
  							$numOpe = $clientSOAP->__soapCall("domainWebFwdCreate",array($IDSession, $fqdn ,$address, $typeNetim, $structOptionsFwd));
  						}
  						catch(SoapFault $fault)
  						{
  							$clientSOAP->__soapCall("logout",array($IDSession)) ;
  							throw new Exception("domainWebFwdCreate error:<BR>".$fault->getMessage());
  							return false;
  						}

  						break;

  					case "CNAME":
  					case "MX":
  					case "TXT":
  					case "A":
  						//Parametrage
  						$structOptionsZone = array();
  						$structOptionsZone["service"] = "";
  						$structOptionsZone["protocol"] = "";
  						$structOptionsZone["ttl"] = "";
  						$structOptionsZone["weight"] = "";
  						$structOptionsZone["port"] = "";
  						$structOptionsZone["priority"] = 10;
  						try
  						{
  							$numOpe = $clientSOAP->__soapCall("domainZoneCreate",array($IDSession, $domain, $hostname, $type, $address, $structOptionsZone));
  						}
  						catch(SoapFault $fault)
  						{
  							$clientSOAP->__soapCall("logout",array($IDSession)) ;
  							throw new Exception("domainZoneCreate error:<BR>".$fault->getMessage());
  							return false;
  						}
  						break;

  					default:
  						$clientSOAP->__soapCall("logout",array($IDSession)) ;
  						throw new Exception("THE RECORD TYPE '".$type."' IS NOT SUPPORTED</br>");
  						return false;
  						break;
  				}
  			}
  		}

  		# ----------------------------------------------------------------------
  		# disconnection from the Netim's API
  		# ----------------------------------------------------------------------
  		if(isset($IDSession))
  			$clientSOAP->__soapCall("logout",array($IDSession)) ;

  		 return true;
  	}

	 // Not implemented functions
    function deleteNS ($params)
    {
        throw new Exception('Method deleteNS() has not been implemented yet.');
    }

	  function checkNSStatus ($params)
    {
        throw new Exception('Method checkNSStatus() has not been implemented yet.');
    }

    function registerNS ($params)
    {
        throw new Exception('Method registerNS() has not been implemented yet.');
    }

    function editNS ($params)
    {
        throw new Exception('Method editNS() has not been implemented yet.');
    }

    function disablePrivateRegistration($parmas)
    {
        throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented yet.');
    }
    function getTransferStatus($params)
    {
        throw new MethodNotImplemented('Method getTransferStatus has not been implemented yet.');
    }
}

?>
