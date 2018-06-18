<?php
/**
 * Plugin for Clientexec
 * @see http://www.newedge.com/clientexec_api_public/Clientexec/RegistrarPlugin.html
 * @package bs.internet.plugins
 */

// Check if we have old or new version of clientexec platform
if((@include_once('CE/RegistrarPlugin.php')) === false)    {
	// Looks we have a new version of platform
	require_once 'modules/admin/models/RegistrarPlugin.php';
	require_once dirname(__FILE__).'/../../../library/CE/NE_Observable_Loggers.php';
	require_once 'modules/domains/models/ICanImportDomains.php';
}


class PluginInternetbs extends RegistrarPlugin implements ICanImportDomains{

    function getVariables() {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('Internet.bs Corp.')
                               ),

            lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use Internet.bs testing environment, so that transactions are not actually made.'),
                                'value'         =>0
                               ),
            lang('Hide whois data') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you want to hide the information in the public whois for Admin/Billing/Technical contacts (.it)'),
                                'value'         =>0
                               ),

            lang('API Key') => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter API key for your Internet.bs reseller account.'),
                                'value'         =>''
                               ),

            lang('Password')  => array(
                                'type'          =>'password',
                                'description'   =>lang('Enter the password for your Internet.bs reseller account.'),
                                'value'         =>'',
                                ),
            lang('Supported Features')  => array(
                                'type'          => 'label',
                                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* '.lang('Existing Domain Importing').' <br>* '.lang('Get / Set Auto Renew Status').' <br>* '.lang('Get / Set DNS Records').' <br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Contact Information').' <br>\* '.lang('Get Registrar Lock'),
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
                                'value'         => 'Cancel',
                                )


        );

        return $variables;
    }

    function doRegister($params){
        $userPackage = new UserPackage($params['userPackageId']);
        $result = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
	if($result[0]==1){
		$userPackage->setCustomField("Registrar Order Id",$result[1]);
        	return $userPackage->getCustomField('Domain Name') . ' has been registered.';
	} else {

	}
    }

	/**
	 *  Method that communicates with the registrar API to get information about exisitng DNS records for domain
	 *
	 *  @param $param Contains the values for the variables defined in getVariables() and tld and sld values (top-level domain and second-level domain)
	 *  @return array
	 */
    function getDNS($params) {

 		$commandParams = array(
 			'domain' => trim($params['sld'].'.'.$params['tld']),
 		);

 		$dnsRecordsInfo = $this->_getListOfExisitngDns($commandParams, $params);

		$records = array();
		$types = array('A' , 'CNAME' , 'MX' , 'AAAA' , 'TXT' , 'NS');

		foreach($dnsRecordsInfo as $dnsRecordInfo)	{
			// Collect information about DNS
			$record = array(
				'hostname'=> $dnsRecordInfo['name'],
				'address' => $dnsRecordInfo['value'],
				'type'    => $dnsRecordInfo['type'],
			);
			$record['id'] = MD5($record['hostname'].':'.$record['address'].':'.$record['type']);

			$records[] = $record;
		}

 	return array('records' => $records, 'types' => $types, 'default' => true);
    }

    function _getListOfExisitngDns($commandParams, $params)	{
    	// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/DnsRecord/List', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

		$dnsRecordsInfo = array();

		// Get information about existing NS
		foreach($result as $field => $value)	{
			if(preg_match('/^records_([0-9]+)_([^_]+)$/i', $field, $matches) && isset($matches[1]) && isset($matches[2]))	{
				$index = intval($matches[1]);
				$fieldName = trim($matches[2]);
				// Collect information about domains
				$dnsRecordsInfo[$index][$fieldName] = $value;
			}
		}

    return $dnsRecordsInfo;
    }

    function _isDNSExist($dnsRecordsInfo, $host, $type)	{

    	foreach($dnsRecordsInfo as $dnsRecordInfo)	{
    		if($dnsRecordInfo['name'] == $host && $dnsRecordInfo['type'] == $type)	{
    			return true;
    		}
    	}

    	return false;
    }

    function _remDNS($fullRecordName, $type, $params)	{
 		$commandParams = array(
 			'FullRecordName' => $fullRecordName,
 			'Type'           => $type,
 		);

    	// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/DnsRecord/Remove', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}
    }

    function _editDNS($fullRecordName, $type, $currentValue, $newValue, $params)	{
 		$commandParams = array(
 			'FullRecordName' => $fullRecordName,
 			'Type'           => $type,
 			'CurrentValue'   => $currentValue,
 			'NewValue'       => $newValue,
 		);

    	// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/DnsRecord/Update', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}
    }

    /**
     * Method that communicates with the registrar API to set DNS records for domain
     *
     * @param $params
     * @return array()
     */
    function setDNS($params) {

    	$currentDNS = array();

    	$domainName = trim($params['sld'].'.'.$params['tld']);

    	// Get list of existing NS
    	$dnsRecordsInfo = $this->_getListOfExisitngDns(array('domain' => $domainName), $params);

    	// Add DnsRecords if need
 		foreach($params['records'] as $index => $record)	{
			$fullRecordName=$record['hostname'];
			$length=strlen($record['hostname']);
			if(substr($record['hostname'], 0, $length) !== $domainName){
				$fullRecordName=$fullRecordName.'.'.$domainName;
			}

 			if(!$this->_isDNSExist($dnsRecordsInfo, $fullRecordName, $record['type']))	{
	 			$error = null;
 				// Create a command param for each NS which we have to add
	 			$commandParams = array(
	 				'FullRecordName' => $fullRecordName,
		 			'Type'           => $record['type'],
		 			'Value'          => $record['address'],
 				);

	 			// Try to execute a set renewal flag operation
				if(($result = $this->_executeApiCommand('Domain/DnsRecord/Add', $commandParams, $params, $error)) === false || !is_array($result)) {
					return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
				}

				// Check if operation success
				if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
					return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
				}
 			}
 		}

 		// Delete a DnsRecords if need (presetn in old list, but not present in new)
 		foreach($dnsRecordsInfo as $dnsRecordInfo)	{

 			$fullRecordName = $dnsRecordInfo['name'];
 			$type           = $dnsRecordInfo['type'];

 			$needToDelete = true;

 			// Check if need to delete
 			foreach($params['records'] as $index => $record)	{
 				if($record['hostname'] == $fullRecordName && $record['type'] == $type)	{
 					$needToDelete = false;
 					break;
 				}
 			}

 			if($needToDelete)	{
 				$this->_remDNS($fullRecordName, $type, $params);
 			}

 		}

 		// Update a DnsRecords if need
 		foreach($dnsRecordsInfo as $dnsRecordInfo)	{

 			$fullRecordName = $dnsRecordInfo['name'];
 			$type           = $dnsRecordInfo['type'];
 			$value          = $dnsRecordInfo['value'];

 			$needToUpdate = true;

 			// Check if need to delete
 			foreach($params['records'] as $index => $record)	{
 				if($record['hostname'] == $fullRecordName && $record['type'] == $type && $record['address'] != $value)	{
 					$needToUpdate = true;
 					break;
 				}
 			}

 			if($needToUpdate)	{
 				$this->_editDNS($fullRecordName, $type, $value, $record['address'], $params);
 			}

 		}


 	return true;
    }

	/**
	 * Method that communicates with the registrar API to find out if the domain name is available
	 *
	 * @param $params Contains the values for the variables defined in getVariables() and tld and sld values (top-level domain and second-level domain)
	 * @return array array(code [,message]), where code is:
	 *                                                      0:Domain available
	 *                                                      1:Domain already registered
	 *                                                      2:Registrar Error, domain extension not recognized or supported
	 *                                                      3:Domain invalid
	 *                                                      5:Could not contact registry to lookup domain
	 */
 	function checkDomain($params){
 		$error = null;
 		$code  = null;

 		$commandParams = array(
 			'domain' => trim($params['sld'].'.'.$params['tld']),
 		);

 		// DomainCheck?apikey=api_key&password=123456&domain=aaaaaaaaaaafffffffffff.com
 		$result = $this->_executeApiCommand('Domain/Check', $commandParams, $params, $error);
 		// Step 1. Check if we got some error, because we can't connect to API server
 		if($result === false)	{

 			$errorResult = array(5);

 			if(!is_null($error))	{
 				$errorResult[]=$error;
 			}

 			return $errorResult;
 		}

		// Step 2. Check if domain available
		if(isset($result['status']) && $this->_isEqStrings($result['status'], 'AVAILABLE'))	{
            $status = 0;
		}

		// Step 3. Check if domain already registred
		if(isset($result['status']) && $this->_isEqStrings($result['status'], 'UNAVAILABLE'))	{
            $status = 1;
		}

		// Step 4. Check if domain extension is not supported
		if(isset($result['status']) && isset($result['code']) && $result['code'] == '100004' && $this->_isEqStrings($result['status'], 'FAILURE'))	{
			$status = 2;
		}

		// Step 5. Check if domain name is not valid
		if(isset($result['status']) && isset($result['code']) && $result['code'] == '100002' && $this->_isEqStrings($result['status'], 'FAILURE'))	{
			$status = 3;
		}


        $domains[] = array("tld"=>$params['tld'],"domain"=>$params['sld'],"status"=>$status);
        return array("result"=>$domains);
 	}



	/**
	 * Communicates with the registrar API to check a name server.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: nsname.
	 * @return string current status string
	 */
	function checkNSStatus($params){
		// TODO: need to impement it some how, for now skiped (Pavel suggest skip it for a moment)
		return new CE_Error("This function is not supported", 1000);
	}



	/**
	 * Communicates with the registrar API to delete a name server.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: nsname.
	 * @return
	 */
	function deleteNS($params){

		$error = null;

 		$commandParams = array(
 			'host' => trim($params['nsname']),
 		);

 		// Domain/Host/Delete?apiKey=testapi&password=testpass&host=ns1.test-api-domain71.com
 		if(($result = $this->_executeApiCommand('Domain/Host/Delete', $commandParams, $params, $error)) === false || !is_array($result)) {
 			return new CE_Error($error, 132);
 		} else {

 			// Check if operation executes success
 			if(isset($result['status']) && ( $this->_isEqStrings($result['status'], 'SUCCESS') || $this->_isEqStrings($result['status'], 'PENDING')))	{
 				// Operation DONE
 				return $this->user->lang('Name Server deleted successfully.');
 			} else {
 				// Some error happen
 				$error = isset($result['message']) ? $result['message'] : 'Unkonown error';

 				// Check if error code set
 				if(isset($result['code']))	{
 					$error.=' (code: '.$result['code'].')';
 				}

 				return new CE_Error($error, 130);
 			}
 		}
	}



	/**
	 * Communicates with the registrar API to cancel the domain's automatic renewal.
	 *
	 * @param $params Contains the values for the variables defined in getVariables() and tld and sld values (top-level domain and second-level domain)
	 * @return array(1) for success, array(-1, message) for failure
	 */
	function disableRenewal($params){

		$error = null;

		$response = array();

 		$commandParams = array(
 			'autorenew' => 'NO',
 			'domain'    => $params['sld'].'.'.$params['tld'],
 		);

		if(($result = $this->_executeApiCommand('Domain/Update', $commandParams, $params, $error)) === false || !is_array($result)) {

			$response = array(-1);

			if(!is_null($error))	{
				$response[] = $error;
			}

 		} else {

 			// Check if command success executed
 			if(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS'))	{
 				// Operation success done
 				$response = array(1);
 			} else {

 				$response = array(-1);

				if(isset($result['message']))	{
					$response[] = $result['message'];
				}
 			}
 		}

 	return $response;
	}



	/**
	 * Communicates with the registrar API to set autorenew for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return CE_Error on failure
	 */
	function setAutorenew($params){

		$error = null;

    $autorenew = 'YES';

		if(isset($params['autorenew']) && $params['autorenew'] == 0)	{
			$autorenew = 'NO';
		}

 		$commandParams = array(
 			'autorenew' => $autorenew,
 			'domain'    => $params['sld'].'.'.$params['tld'],
 		);

 		// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/Update', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && ($this->_isEqStrings($result['status'], 'SUCCESS') ||  $this->_isEqStrings($result['status'], 'PENDING')) ))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

		return null;
	}



	/**
	 * Communicates with the registrar API to edit a name server.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: nsname, nsnewip, nsoldip.
	 * @return Status string
	 */
	function editNS($params){
		$error = null;

 		$commandParams = array(
 			'host'    => $params['nsname'],
 			'ip_list' => is_array($params['nsnewip']) ? implode(',', $params['nsnewip']) : $params['nsnewip'],
 		);

 		// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/Host/Update', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && ($this->_isEqStrings($result['status'], 'SUCCESS') ||  $this->_isEqStrings($result['status'], 'PENDING')) ))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

	return $this->user->lang('Name Server edited successfully.');
	}



	/**
	 * Communicates with the registrar API to list domains held by the registrar.
	 * @param $params Contains the values for the variables defined in getVariables(), plus: page.
	 * @return array('id' => array(id, sld, tld, exp), total, start, end, numPerPage)
	 */
	function fetchDomains($params){

		// Step 1. Get information about amount of total domains
		if(($result = $this->_executeApiCommand('Domain/Count', array(), $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}


		$totalDomains = intval($result['totaldomains']);
		$numPerPage   = 25;
		$start        = 0;
		$end          = $start + $numPerPage;

		$error = null;

 		$commandParams = array(
 			'compactlist' => 'no',
 			'rangeFrom'   => $start,
 			'rangeto'     => $end,
 		);

 		// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/List', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

		$domainsInfo = array();

		// Get information about domains from response
		foreach($result as $field => $value)	{
			// Collect information about domains
			if(preg_match('/^domain_([0-9]+)_([^_]+)$/i', $field, $matches) && isset($matches[1]) && isset($matches[2]))	{
				$index = intval($matches[1]);
				$fieldName = trim($matches[2]);
				// Collect information about domains
				$domainsInfo[$index][$fieldName] = $value;
			}
		}


		$domainsList = array();

		// Build a response result
		foreach($domainsInfo as $domainInfo)	{

			// Use always ASCII domain name, because plugin user may have a problem with UTF8 domain names
			$domainName = isset($domainInfo['punycode']) ? $domainInfo['punycode'] : $domainInfo['name'];
			list($sld, $tld) = explode('.',$domainName,2);

			$data = array(
				'id'  => $domainName,
				'sld' => $sld,
				'tld' => $tld,
				'exp' => $domainInfo['expiration'],
			);

            $domainsList[] = $data;
		}


        $metaData = array(
        	'total'      => $totalDomains,
        	'start'      => $start,
        	'end'        => $end,
        	'numPerPage' => $numPerPage,
        );

	return array($domainsList, $metaData);
	}



	/**
	 * Communicates with the registrar API to retrieve the contact information for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return array('type' => array(contactField => contactValue))
	 */
	function getContactInformation($params){
		$error = null;

 		$commandParams = array(
 			'domain' => strtolower(trim($params['sld'].'.'.$params['tld'])),
 		);

 		// Get information about domain
		if(($result = $this->_executeApiCommand('Domain/Info', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

		$info = array();
		foreach (array("registrant"=>'Registrant', "billing"=>'AuxBilling', "admin"=>'Admin', "technical"=>'Tech') as $type=>$ceType) {
		    //if this contact type exists
		    if (isset($result['contacts_'.$type.'_firstname'])) {
			$info[$ceType]['Organization']  = array($this->user->lang('Organization'), $result['contacts_'.$type.'_organization']);
			$info[$ceType]['FirstName'] = array($this->user->lang('First Name'), $result['contacts_'.$type.'_firstname']);
			$info[$ceType]['LastName']  = array($this->user->lang('Last Name'), $result['contacts_'.$type.'_lastname']);
			$info[$ceType]['Street']  = array($this->user->lang('Address').' 1',$result['contacts_'.$type.'_street']);
			$info[$ceType]['Street2']  = array($this->user->lang('Address').' 2', $result['contacts_'.$type.'_street2']);
			$info[$ceType]['Street3']  = array($this->user->lang('Address').' 3', $result['contacts_'.$type.'_street3']);
			$info[$ceType]['City']      = array($this->user->lang('City'),$result['contacts_'.$type.'_city']);
			$info[$ceType]['CountryCode']   = array($this->user->lang('Country'), $result['contacts_'.$type.'_countrycode']);
			$info[$ceType]['PostalCode']  = array($this->user->lang('Postal Code'), $result['contacts_'.$type.'_postalcode']);
			$info[$ceType]['Email']     = array($this->user->lang('E-mail'),$result['contacts_'.$type.'_email']);
			$info[$ceType]['PhoneNumber']  = array($this->user->lang('Phone'), $result['contacts_'.$type.'_phonenumber']);
		    }
		}
		return $info;
	}



	/**
	 * Communicates with the registrar API to retrieve general information for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return array(id,domain,expiration,registrationstatus,purchasestatus,autorenew)
	 */
	function getGeneralInfo($params){
		$error = null;

		$domainName = strtolower(trim($params['sld'].'.'.$params['tld']));

 		$commandParams = array(
 			'domain' => $domainName,
 		);

 		// Get information about domain
		if(($result = $this->_executeApiCommand('Domain/Info', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, EXCEPTION_CODE_CONNECTION_ISSUE);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', EXCEPTION_CODE_CONNECTION_ISSUE);
		}

		$domainStatus = strtolower($result['domainstatus']);

		$response = array(
			'id'                 => $domainName,
			'domain'             => $domainName,
			'expiration'         => $result['expirationdate'],
			'registrationstatus' => $domainStatus,
			'purchasestatus'     => $domainStatus,
			'autorenew'          => 'yes' == strtolower(trim($result['autorenew'])),
		);

	return $response;
	}



	/**
	 * Communicates with the registrar API to retrieve the dns information for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return array
	 */
	function getNameServers($params){
		$error = null;

 		$commandParams = array(
 			'domain' => $params['sld'].'.'.$params['tld'],
 		);

 		// Get information about domain
		if(($result = $this->_executeApiCommand('Domain/Info', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

		$ns = array();

		foreach($result as $field => $value)	{
			if(preg_match('/^nameserver_?/i', $field))	{
				$ns[] = trim($value);
			}
		}

        if  (count($ns) == 0 ) {
            $ns[] = '';
        }

 	return $ns;
	}



	/**
	 * Communicates with the registrar API to retrieve the registrar lock information for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return boolean true mean domain locked and false not locked
	 */
	function getRegistrarLock($params){

		$error = null;

 		$commandParams = array(
 			'domain'    => $params['sld'].'.'.$params['tld'],
 		);

 		// Try to get lock status for domain
		if(($result = $this->_executeApiCommand('Domain/RegistrarLock/Status', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS') && isset($result['registrar_lock_status']))	{
			return $this->_isEqStrings($result['registrar_lock_status'], 'LOCKED');
		} else {
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}
	}



	/**
	 * Communicates with the registrar API to retrieve the dns information for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return CE_Error on failure
	 */
	function setRegistrarLock($params){
		$error = null;

 		$commandParams = array(
 			'domain' => $params['sld'].'.'.$params['tld'],
 		);

 		// Define command name based on what we have to do lock or unlock
 		$commandName = $params['lock'] ? 'Domain/RegistrarLock/Enable' : 'Domain/RegistrarLock/Disable';

 		// Try to execute lock/unlock command with API
		if(($result = $this->_executeApiCommand($commandName, $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(isset($result['status']) && ($this->_isEqStrings($result['status'], 'SUCCESS') || $this->_isEqStrings($result['status'], 'PENDING')))	{
			// All correct no error happen
			return null;
		} else {
			// Some error happen
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}
	}



	/**
	 * Communicates with the registrar API to carry out the domain name registration.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld, NumYears, RegistrantOrganizationName, RegistrantFirstName, RegistrantLastName, RegistrantEmailAddress, RegistrantPhone, RegistrantAddress1, RegistrantCity, RegistrantProvince, RegistrantPostalCode, RegistrantCountry, DomainPassword, ExtendedAttributes, NSx (list of nameservers if set, and usedns.
	 * @return array(code [,message]) -1: error trying to purchase domain 0: domain not available >0: Operation successfull, returns orderid
	 */
	function registerDomain($params){

		$error = null;
		$tld = strtolower(trim($params['tld']));

		// Get a domain name which have to be registred
		$domainName = $params['sld'].'.'.$tld;


		// Step 1. Set a domain name
		$commandParams = array(
			'domain' => $domainName,
		);

		// Step 2. Set a registration period
		if(isset($params['NumYears']))	{
			$commandParams['period'] = intval($params['NumYears']).'Y';
		}

		// Step 3. Check if NS for command set
		if(isset($params['NS1']))	{

			$nsList = array();

			// Allow max 4 NS
			for($i=1;$i<4;$i++)	{
				if(isset($params['NS'.$i]))	{
					$nsList[] = $params['NS'.$i]['hostname'];
				}
			}

			// Check if any NS collected
			if(count($nsList))	{
				$commandParams['ns_list'] = implode(',', $nsList);
			}
		}

		// Step 4. Add information about contact data
		foreach(array('Registrant','Admin','Technical','Billing') as $contactType)	{

			$commandParams[$contactType.'_FirstName']    = $params['RegistrantFirstName'];
			$commandParams[$contactType.'_LastName']     = $params['RegistrantLastName'];
			$commandParams[$contactType.'_Email']        = $params['RegistrantEmailAddress'];
			$commandParams[$contactType.'_PhoneNumber']  = $this->_validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']);
			$commandParams[$contactType.'_Street']       = $params['RegistrantAddress1'];
			$commandParams[$contactType.'_City']         = $params['RegistrantCity'];
			$commandParams[$contactType.'_CountryCode']  = $params['RegistrantCountry'];
			$commandParams[$contactType.'_PostalCode']   = $params['RegistrantPostalCode'];
			$commandParams[$contactType.'_Language']     = 'en'; // Do not know how to set it from params, so use a en by default

			// Optional params
			if(isset($params['RegistrantAddress2']))	{
				$commandParams[$contactType.'_Street2'] = $params['RegistrantAddress2'];
			}

			if(isset($params['RegistrantAddress3']))	{
				$commandParams[$contactType.'_Street3'] = $params['RegistrantAddress3'];
			}

			if(isset($params['RegistrantOrganizationName']))	{
				$commandParams[$contactType.'_Organization']  = $params['RegistrantOrganizationName'];
			}
		}


		// Send information about extended attr (if need)
        if (isset($params['ExtendedAttributes']) && is_array($params['ExtendedAttributes'])) {
            foreach ($params['ExtendedAttributes'] as $name => $value) {
                $commandParams[$name] = $value;
            }
        }

		// Unset useless params
		if(isset($commandParams['domainPassword']))	{
			unset($commandParams['domainPassword']);
		}

		if(isset($commandParams['domainUsername']))	{
			unset($commandParams['domainUsername']);
		}

		// Add specific for .IT attributes
		if('it' == $tld)	{

			if(trim(strtoupper($params['RegistrantCountry'])) == 'IT')	{
				$commandParams['registrant_dotitprovince'] = $this->_get2CharDotITProvinceCode($params['RegistrantStateProvince']);
				$commandParams['admin_dotitprovince'] = $this->_get2CharDotITProvinceCode($params['RegistrantStateProvince']);
			} else {
				$commandParams['registrant_dotitprovince'] = $params['RegistrantStateProvince'];
				$commandParams['admin_dotitprovince'] = $params['RegistrantStateProvince'];
			}

			// Set terms confirmation
			$isAgree = ('Y' == $commandParams['_it_terms']) ? 'yes' : 'no';
			$commandParams['registrant_dotitterm1'] = $isAgree;
        	$commandParams['registrant_dotitterm2'] = $isAgree;
        	$commandParams['registrant_dotitterm3'] = $isAgree;
        	$commandParams['registrant_dotitterm4'] = $isAgree;

			// Set current user IP
			$commandParams['registrant_clientip'] = $this->_getClientIp();

			// Check if we need to hide data in whois for Admin, Tech contacts
			$isDotIdAdminAndRegistrantSame = (1 == $commandParams['registrant_dotitentitytype']);
			$hideWhoisData = isset($pluginParams['Hide whois data']) && intval($pluginParams['Hide whois data']) != 0;
			if(!$isDotIdAdminAndRegistrantSame)	{
				$commandParams['admin_dotithidewhois']     = $hideWhoisData ? 'yes' : 'no';
			}
			$commandParams['technical_dotithidewhois'] = $hideWhoisData ? 'yes' : 'no';


			// Unset specific for .IT domains field
			unset($commandParams['_it_terms']);
		}


		// Step 5. Execute comamnd
 		// Create domain
		if((($result = $this->_executeApiCommand('Domain/Create', $commandParams, $params, $error)) === false) || !is_array($result)) {
			$error = is_null($error) ? 'Unkonown error' : $error;
        } else if (isset($result['status']) && $result['status'] == 'FAILURE') {
            $error = isset($result['message']) ? $result['message'] : 'Unknown error';
		} else if(!isset($result['price']))	{
			$error = isset($result['message']) ? $result['message'] : 'Unkonown error';
		}
		$transactionId=isset($result["transactid"])?$result["transactid"]:$domainName;
		// Return information operation
		if(!strlen($error))	{
			// Domain success registred (we do not have a id for operation, so we will return a domain name as operation id), may be it have to be int, but from doc it's not clear
			return array(1, $transactionId);
		} else {
            throw new Exception($error);
		}
	}



	/**
	 * Communicates with the registrar API to register a name server.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: nsname, nsip.
	 * @return Status string
	 */
	function registerNS($params){
		$error = null;

 		$commandParams = array(
 			'host'    => $params['nsname'],
 			'ip_list' => is_array($params['nsip']) ? implode(',',$params['nsip']) : $params['nsip'],
 		);

 		// Try to get lock status for domain
		if(($result = $this->_executeApiCommand('Domain/Host/Create', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && $this->_isEqStrings($result['status'], 'SUCCESS')))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

	return $this->user->lang('Name Server registered successfully.');
	}



	/**
	 * Communicates with the registrar API to send the transfer key to registrant.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return
	 */
	function sendTransferKey($params){
		return new CE_Error("This function is not supported", 1000);
	}



	/**
	 * Communicates with the registrar API to update contact information for a given domain
	 *
	 * @param $params commands params
	 * @return
	 */
	function setContactInformation($params){

                $cTypes=array('Registrant'=>"registrant", 'AuxBilling'=>"billing", 'Admin'=>"admin", 'Tech'=>"technical");
                $fields=array(
                    'Organization'=>"organization",
                    'FirstName'=>'firstname',
                    'LastName'=>'lastname',
                    'Street'=>'street',
                    'Street2'=>'street2',
                    'Street3'=>'street3',
                    'City'=>'city',
                    'CountryCode'=>'countrycode',
                    'PostalCode'=>'postalcode',
                    'Email'=>'email',
                    'PhoneNumber'=>'phonenumber',
                    );

                $error = null;
		$contactType=$params['type'];
		$commandParams = array(
 			'domain' => $params['sld'].'.'.$params['tld'],
 		);

 		// Get information about values which have to be updated
        foreach ($params as $key => $value) {
            if (strpos($key,$contactType) !== false) {
            	if(preg_match('/([^_]+)$/i',$key,$matches) && isset($matches[1])){
            		$fieldName = $cTypes[$contactType].'_'.$fields[$matches[1]];

            		// Check if we have a phone number value
            		if(preg_match('/(fax|phone|mobile|cellular|telephone|number)/i', $fieldName))	{
            			$commandParams[$fieldName] = $this->_fix_phoneNumber($value);
            		} else {
            			$commandParams[$fieldName] = $value;
            		}
            	}
            }
        }

        $tld = strtolower(trim($params['tld']));


        // Unset params which is not possible update for domain
        if('it' == $tld)	{

        	if($commandParams['registrant_dotitentitytype'] == 1) {
        		unset($commandParams['admin_countrycode']);
        		unset($commandParams['admin_organization']);
        		unset($commandParams['admin_countrycode']);
        		unset($commandParams['admin_country']);
        		unset($commandParams['admin_dotitentitytype']);
        		unset($commandParams['admin_dotitnationality']);
        		unset($commandParams['admin_dotitregcode']);
        	}

        	unset($commandParams['registrant_countrycode']);
        	unset($commandParams['registrant_organization']);
        	unset($commandParams['registrant_countrycode']);
        	unset($commandParams['registrant_country']);
        	unset($commandParams['registrant_dotitentitytype']);
        	unset($commandParams['registrant_dotitnationality']);
        	unset($commandParams['registrant_dotitregcode']);
        }

        if($tld == 'eu' || $tld == 'be')	{
			if(!strlen(trim($commandParams['registrant_organization']))) {
				unset($commandParams['registrant_firstname']);
				unset($commandParams['registrant_lastname']);
			}
			unset($commandParams['registrant_organization']);
        }

		if($tld == "co.uk" || $tld == "org.uk" || $tld == "me.uk") {
			unset($commandParams['registrant_firstname']);
			unset($commandParams['registrant_lastname']);
		}

		if($tld == "fr" || $tld == "re")	{
			unset($commandParams['registrant_firstname']);
			unset($commandParams['registrant_lastname']);
			unset($commandParams['registrant_countrycode']);
			unset($commandParams['registrant_countrycode']);

			if(!strlen(trim($commandParams['admin_dotfrcontactentitysiren'])))	{
				unset($commandParams['admin_dotfrcontactentitysiren']);
			}

			if(trim(strtolower($commandParams['admin_dotfrcontactentitytype'])) == 'individual')	{
				unset($apiInfo['admin_countrycode']);
			}
		}


 		// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/Update', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && ($this->_isEqStrings($result['status'], 'SUCCESS') ||  $this->_isEqStrings($result['status'], 'PENDING')) ))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}
                return $this->user->lang('Contact Information updated successfully.');
	}



	/**
	 * Communicates with the registrar API to retrieve the dns information for a given domain.
	 *
	 * @param $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
	 * @return
	 */
	function setNameServers($params){
		$error = null;

 		$commandParams = array(
 			'domain'  => strtolower(trim($params['sld'].'.'.$params['tld'])),
 			'ns_list' => trim(implode(',', $params['ns'])),
 		);

 		// Also here present a $params['default'] param whihc is set in TRUE when need to set a default NS (we do not know how it have to work, so for now we ignore it)


 		// Try to execute a set renewal flag operation
		if(($result = $this->_executeApiCommand('Domain/Update', $commandParams, $params, $error)) === false || !is_array($result)) {
			return new CE_Error(is_null($error) ? 'Unkonown error' : $error, 132);
		}

		// Check if operation success
		if(!(isset($result['status']) && ($this->_isEqStrings($result['status'], 'SUCCESS') ||  $this->_isEqStrings($result['status'], 'PENDING')) ))	{
			return new CE_Error(isset($result['message']) ? $result['message'] : 'Unkonown error', 130);
		}

 	return null;
	}



	/**
	 * Execute a API command based on given params
	 * @return array
	 */
	function _executeApiCommand($commandCode, $params, $pluginParams, &$errorMessage)	{

		/** TO DEBUG ***/
        if($this->_isDebug()) $this->_debugLog("EXECUTE:".$commandCode."\n================================================\n".var_export($params,true).var_export($pluginParams,true)."\n\n");
		/*****/

		// Set a common params for each API command (login,password,format)
    if(isset($pluginParams['API Key']))	{
			$params['apikey'] = $pluginParams['API Key'];
		} else if(isset($pluginParams['ApiKey'])){
			$params['apikey'] = $pluginParams['ApiKey'];
		}

 		$params['password'] = $pluginParams['Password'];
		$params['responseformat']   = 'TEXT';

		// Check if we need to execute a command at live or at test server
		$isLiveTransaction = !isset($pluginParams['Use testing server']) || intval($pluginParams['Use testing server']) == 0;

	// Execute command at Internet.bs API server (host name have to be finished with "/" sign)
	return $this->_runCommand(($isLiveTransaction ? 'https://api.internet.bs/' : 'https://testapi.internet.bs/').$commandCode,$params,$errorMessage);
	}


	/**
   	 * runs an api command and returns parsed data
 	 *
 	 * @param string $commandUrl
 	 * @param array $postData
 	 * @param string $errorMessage if cannot connect to server
 	 * @return array
 	 */
	function _runCommand($commandUrl, $postData, &$errorMessage) {

		// Always get result in text format
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $commandUrl);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Internet.bs ClientExec plugin V2.6");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		$data = curl_exec($ch);
		$errorMessage = curl_error($ch);

		/** TO DEBUG ***/
        if($this->_isDebug()) $this->_debugLog($commandUrl."\n\n".var_export($postData,true)."\n".$data."\n\n".$errorMessage);
		/*****/

		curl_close($ch);


	return ( ($data===false) ? false : $this->_parseResult($data));
	}

	function _parseResult($data) {

		$lines = explode("\n", $data);
		$result = array();

		foreach($lines as $line)	{
			list($varName, $value) = explode("=",$line,2);
			$result[strtolower(trim($varName))] = trim($value);
		}

		return $result;
	}


	function _isEqStrings($str1,$str2)	{
		return strtolower(trim($str1)) == strtolower(trim($str2));
	}

    function _validatePhone($phone, $country) {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);

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
        return '+'.$code.'.'.$phone;
    }

    function _debugLog($data)	{
    	file_put_contents("/tmp/clientexec.log",$data,FILE_APPEND);
    }

    function _isDebug()	{
    	return (false);
    }

    /**
     * Client exec app have a issue in positing value in AJAX request. All "+" sighs replaced by " ". That method we use to fix a phone number.
     * @return unknown_type
     */
    function _fix_phoneNumber($rawPhoneNumber)	{

    	// Check if need to fix
    	if(preg_match('/^\s+[0-9]+\.[0-9]+\s*$/i', $rawPhoneNumber))	{
    		$rawPhoneNumber = '+'.trim($rawPhoneNumber);
    	}

    return $rawPhoneNumber;
    }

	function _getClientIp() {
    	return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null));
	}

	function _get2CharDotITProvinceCode($province) {

	$provinceFiltered = trim($province);

	$provinceNamesInPossibleVariants = array(
	'Agrigento'=>'AG',
	'Alessandria'=>'AL',
	'Ancona'=>'AN',
	'Aosta, Aoste (fr)'=>'AO',
	'Aosta, Aoste'=>'AO',
	'Aosta'=>'AO',
	'Aoste'=>'AO',
	'Arezzo'=>'AR',
	'Ascoli Piceno'=>'AP',
	'Ascoli-Piceno'=>'AP',
	'Asti'=>'AT',
	'Avellino'=>'AV',
	'Bari'=>'BA',
	'Barletta-Andria-Trani'=>'BT',
	'Barletta Andria Trani'=>'BT',
	'Belluno'=>'BL',
	'Benevento'=>'BN',
	'Bergamo'=>'BG',
	'Biella'=>'BI',
	'Bologna'=>'BO',
	'Bolzano, Bozen (de)'=>'BZ',
	'Bolzano, Bozen'=>'BZ',
	'Bolzano'=>'BZ',
	'Bozen'=>'BZ',
	'Brescia'=>'BS',
	'Brindisi'=>'BR',
	'Cagliari'=>'CA',
	'Caltanissetta'=>'CL',
	'Campobasso'=>'CB',
	'Carbonia-Iglesias'=>'CI',
	'Carbonia Iglesias'=>'CI',
	'Carbonia'=>'CI',
	'Caserta'=>'CE',
	'Catania'=>'CT',
	'Catanzaro'=>'CZ',
	'Chieti'=>'CH',
	'Como'=>'CO',
	'Cosenza'=>'CS',
	'Cremona'=>'CR',
	'Crotone'=>'KR',
	'Cuneo'=>'CN',
	'Enna'=>'EN',
	'Fermo'=>'FM',
	'Ferrara'=>'FE',
	'Firenze'=>'FI',
	'Foggia'=>'FG',
	'Forli-Cesena'=>'FC',
	'Forli Cesena'=>'FC',
	'Forli'=>'FC',
	'Frosinone'=>'FR',
	'Genova'=>'GE',
	'Gorizia'=>'GO',
	'Grosseto'=>'GR',
	'Imperia'=>'IM',
	'Isernia'=>'IS',
	'La Spezia'=>'SP',
	'L\'Aquila'=>'AQ',
	'LAquila'=>'AQ',
	'L-Aquila'=>'AQ',
	'L Aquila'=>'AQ',
	'Latina'=>'LT',
	'Lecce'=>'LE',
	'Lecco'=>'LC',
	'Livorno'=>'LI',
	'Lodi'=>'LO',
	'Lucca'=>'LU',
	'Macerata'=>'MC',
	'Mantova'=>'MN',
	'Massa-Carrara'=>'MS',
	'Massa Carrara'=>'MS',
	'Massa'=>'MS',
	'Matera'=>'MT',
	'Medio Campidano'=>'VS',
	'Medio-Campidano'=>'VS',
	'Medio'=>'VS',
	'Messina'=>'ME',
	'Milano'=>'MI',
	'Modena'=>'MO',
	'Monza e Brianza'=>'MB',
	'Monza-e-Brianza'=>'MB',
	'Monza-Brianza'=>'MB',
	'Monza Brianza'=>'MB',
	'Monza'=>'MB',
	'Napoli'=>'NA',
	'Novara'=>'NO',
	'Nuoro'=>'NU',
	'Ogliastra'=>'OG',
	'Olbia-Tempio'=>'OT',
	'Olbia Tempio'=>'OT',
	'Olbia'=>'OT',
	'Oristano'=>'OR',
	'Padova'=>'PD',
	'Palermo'=>'PA',
	'Parma'=>'PR',
	'Pavia'=>'PV',
	'Perugia'=>'PG',
	'Pesaro e Urbino'=>'PU',
	'Pesaro-e-Urbino'=>'PU',
	'Pesaro-Urbino'=>'PU',
	'Pesaro Urbino'=>'PU',
	'Pesaro'=>'PU',
	'Pescara'=>'PE',
	'Piacenza'=>'PC',
	'Pisa'=>'PI',
	'Pistoia'=>'PT',
	'Pordenone'=>'PN',
	'Potenza'=>'PZ',
	'Prato'=>'PO',
	'Ragusa'=>'RG',
	'Ravenna'=>'RA',
	'Reggio Calabria'=>'RC',
	'Reggio-Calabria'=>'RC',
	'Reggio'=>'RC',
	'Reggio Emilia'=>'RE',
	'Reggio-Emilia'=>'RE',
	'Reggio'=>'RE',
	'Rieti'=>'RI',
	'Rimini'=>'RN',
	'Roma'=>'RM',
	'Rovigo'=>'RO',
	'Salerno'=>'SA',
	'Sassari'=>'SS',
	'Savona'=>'SV',
	'Siena'=>'SI',
	'Siracusa'=>'SR',
	'Sondrio'=>'SO',
	'Taranto'=>'TA',
	'Teramo'=>'TE',
	'Terni'=>'TR',
	'Torino'=>'TO',
	'Trapani'=>'TP',
	'Trento'=>'TN',
	'Treviso'=>'TV',
	'Trieste'=>'TS',
	'Udine'=>'UD',
	'Varese'=>'VA',
	'Venezia'=>'VE',
	'Verbano-Cusio-Ossola'=>'VB',
	'Verbano Cusio Ossola'=>'VB',
	'Verbano'=>'VB',
	'Verbano-Cusio'=>'VB',
	'Verbano-Ossola'=>'VB',
	'Vercelli'=>'VC',
	'Verona'=>'VR',
	'Vibo Valentia'=>'VV',
	'Vibo-Valentia'=>'VV',
	'Vibo'=>'VV',
	'Vicenza'=>'VI',
	'Viterbo'=>'VT',
	);


	// Check if we need to search province code
	if(strlen($provinceFiltered) == 2)	{
		// Looks we already have 2 char province code
		return strtoupper($provinceFiltered);
	} else {
		$provinceFiltered = strtolower(preg_replace('/[^a-z]/i','',$provinceFiltered));

		foreach($provinceNamesInPossibleVariants as $name => $code)	{
			if(strtolower(preg_replace('/[^a-z]/i','',$name)) == $provinceFiltered)	{
				return $code;
			}
		}

		return $province;
	}

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
