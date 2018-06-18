<?php

require_once 'library/CE/XmlFunctions.php';

/**
* @package Clientexec
*/
class PleskServer10
{
    var $rpcHandlerVersion = '1.6.3.2';
    var $rpcHandlerPort = 8443;
    var $rpcHandlerPath = '/enterprise/control/agent.php';
    var $host;
    var $settings;
    var $user;
    var $password;
    var $limitsVars = array('max_dom', 'max_subdom', 'disk_space', 'max_traffic', 'max_wu', 'max_db', 'max_box', 'mbox_quota',
                            'max_redir', 'max_mg', 'max_resp', 'max_maillists', 'max_webapps', 'max_mssql_db');

    // @access	public
    function __construct(&$settings, $host, $user, $password)
    {
        $this->settings =& $settings;
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    // @return	int	    userId in Plesk
    // @access	public
    function addUser($contactName, $login, $password, &$tUser)
    {
        // can't set a pname (full client name) because it would have to be different for each domain the client has
        $phone = $tUser->getPhone();
        $email = $tUser->getEmail();
        $address = $tUser->getAddress();
        $city = $tUser->getCity();
        $state = $tUser->getState();
        $pcode = $tUser->getZipCode();
        $country = $tUser->getCountry();
        $organization = $tUser->getOrganization();
        $request = "
            <customer>
                <add>
                    <gen_info>
                        <pname>".$this->_convertStr($contactName)."</pname>
                        <login>".$this->_convertStr($login)."</login>
                        <passwd>".$this->_convertStr($password)."</passwd>
                        <phone>".$this->_convertStr($phone)."</phone>
                        <email>".$this->_convertStr($email)."</email>
                        <address>".$this->_convertStr($address)."</address>
                        <city>".$this->_convertStr($city)."</city>
                        <state>".$this->_convertStr($state)."</state>
                        <pcode>".$this->_convertStr($pcode)."</pcode>
                    </gen_info>
                </add>
            </customer>";


        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'customer', 'add', $errMessage)) {
            throw new CE_Exception("Couldn't insert customer in Plesk. Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'customer', 'add') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to add user', 101);
        }

        return $response['packet']['#']['customer'][0]['#']['add'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

    // @access	public
    function addResellerPermissionsAndLimits($userId, $packageVars= array())
    {
        $limits = $this->_getLimits($packageVars, true);

        $request = "
            <reseller>
                <set>
                    <filter>
                        <id>$userId</id>
                    </filter>
                    <values>
						<permissions>
							<permission>
								<name>create_domains</name>
								<value>true</value>
							</permission>
						</permissions>
                        $limits
                    </values>
                </set>
            </reseller>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'reseller', 'set', $errMessage)) {
			throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'reseller', 'set') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to give user the Create Domains permission', 101);
        }

        return true;
    }

    // @access	public
    function addIpToUser($userId, $ip)
    {
        $request = "
			<reseller>
				<ippool-add-ip>
					<reseller-id>{$userId}</reseller-id>
					<ip>
						<ip-address>{$ip}</ip-address>
					</ip>
				</ippool-add-ip>
			</reseller>";


        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'reseller', 'ippool-add-ip', $errMessage)) {

			// IP is already on the reseller account
			if ( $errorCode == 1023 )
				return;

			throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'reseller', 'ippool-add-ip') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to add IP to user', 101);
        }
    }

    // @access	public
    function removeIpFromUser($userId, $ip)
    {
        $request = "
            <customer>
                <ippool_del_ip>
                    <client_id>$userId</client_id>
                    <ip_address>$ip</ip_address>
                </ippool_del_ip>
            </customer>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'customer', 'ippool_del_ip', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'customer', 'ippool_del_ip') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to remove IP from user\s IP pool', 101);
        }
    }

	function addWebSpaceToUser($userID,$login, $password, $domainName, $ip, $packageVars,$usrdata)
	{
	$dfname =$usrdata->getFirstName();
        $dlname = $usrdata->getLastName();
        $dorganization = $usrdata->getOrganization();
        $dphone = $usrdata->getPhone();
        $demail= $usrdata->getEmail();
        $daddress = $usrdata->getAddress();
        $dcity = $usrdata->getCity();
        $dstate = $usrdata->getState();
        $dpcode = $usrdata->getZipCode();
        $dcountry = $usrdata->getCountry();

        $hostingParams = '';
        $preferences = '';
        $template = '';
        $this->_processPackageVars($hostingParams, $preferences, $template, $packageVars, $login, $password);
        $limits = $this->_getLimits($packageVars, false);

        $request = "
            <webspace>
                <add>
                    <gen_setup>
                        <name>".$this->_convertStr($domainName)."</name>
						<owner-id>{$userID}</owner-id>
						<htype>vrt_hst</htype>
                        <ip_address>$ip</ip_address>
						<status>0</status>
                    </gen_setup>
					<hosting>
						<vrt_hst>
							<property>
								<name>ftp_login</name>
								<value>{$login}</value>
							</property>
							<property>
								<name>ftp_password</name>
								<value>{$password}</value>
							</property>
							<ip_address>{$ip}</ip_address>
						</vrt_hst>
					</hosting>
                    $limits
                    $preferences
					$template
                </add>
            </webspace>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'webspace', 'add', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'webspace', 'add') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to add domain to user', 101);
        }

        return $response['packet']['#']['webspace'][0]['#']['add'][0]['#']['result'][0]['#']['id'][0]['#'];
	}

	function deleteReseller($userId)
	{
		$request = "
            <reseller>
                <del>
                    <filter>
                        <id>$userId</id>
                    </filter>
                </del>
            </reseller>";

		$response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'reseller', 'del', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'reseller', 'del') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }
	}

    // @access	public
    function deleteUser($userId)
    {
    	$request = "
            <customer>
                <del>
                    <filter>
                        <id>$userId</id>
                    </filter>
                </del>
            </customer>";

		$response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'customer', 'del', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'customer', 'del') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }
    }

	/*Plesk client and domain status.
	* Bit mask with bit flags:

	* 0 - object is active
	* 4 - object is under backup/restore

	* 16 - object is disabled by Administrator

	* 64 - object is disabled by Client
	*
	* 256 - object expired
	* Only 0, 16 and 64 flags are available for setting*/
    function setDomainStatus($domainId, $status)
    {
    	$request = "
    		<webspace>
    			<set>
    				<filter>
    					<id>$domainId</id>
					</filter>
					<values>
    					<gen_setup>
							<status>$status</status>
						</gen_setup>
					</values>
				</set>
			</webspace>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'webspace', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        return true;
    }

	function getDomainStatus($domainId)
    {
    	$request = "
    		<webspace>
    			<get>
    				<filter>
    					<id>$domainId</id>
					</filter>
					<dataset>
						<hosting/>
					</dataset>
				</get>
			</webspace>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'webspace', 'get', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }

		if ($this->_returnedStatus($response, 'webspace', 'get') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to get web space information', 101);
        }

        return $response;
    }

    function setUserStatus($userId, $status)
    {
    	$request = "
    		<customer>
    			<set>
    				<filter>
    					<id>$userId</id>
					</filter>
					<values>
    					<gen_info>
							<status>$status</status>
						</gen_info>
					</values>
				</set>
			</customer>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'customer', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        return true;
    }

	function setResellerStatus($userId, $status)
    {
		$request = "
	<reseller>
		<set>
			<filter>
				<id>{$userId}</id>
			</filter>
			<values>
				<gen-info>
					<status>{$status}</status>
				</gen-info>
			</values>
		</set>
	</reseller>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'reseller', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        return true;
    }

	function updateUserAccount(&$tUser, $userId, $login, $password)
	{
        $phone = $tUser->getPhone();
        $email = $tUser->getEmail();
        $address = $tUser->getAddress();
        $city = $tUser->getCity();
        $state = $tUser->getState();
        $pcode = $tUser->getZipCode();
        $country = $tUser->getCountry();
        $organization = $tUser->getOrganization();
        $request = "
            <customer>
                <set>
					<filter>
                        <id>$userId</id>
                    </filter>
					<values>
						<gen_info>
		";
		if ( strlen($login) > 0 )
			$request .= "<login>$login</login>";

		if ( strlen($password) > 0 )
			$request .= "<passwd>$password)</passwd>";

        $request .= "
						<phone>".$this->_convertStr($phone)."</phone>
                        <email>".$this->_convertStr($email)."</email>
                        <address>".$this->_convertStr($address)."</address>
                        <city>".$this->_convertStr($city)."</city>
                        <state>".$this->_convertStr($state)."</state>
                        <pcode>".$this->_convertStr($pcode)."</pcode>";
        if(strlen($organization) != 0){
        	$request .= "
                        <cname>".$this->_convertStr($organization)."</cname>";

		}
        $request .= "
        			 </gen_info>
					</values>
                </set>
            </customer>";


        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'customer', 'set', $errMessage)) {
            throw new CE_Exception("Couldn't update customer in Plesk. Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'customer', 'set') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to update user', 101);
        }
	}

    // @access	public
    function updateWebSpace($domainId, $login, $password, $ip, $packageVars)
    {
    	$hostingParams = '';
        $preferences = '';
        $template = '';
        $this->_processPackageVars($hostingParams, $preferences, $template, $packageVars, $login, $password);
        $limits = $this->_getLimits($packageVars, false);

        $request = "
            <webspace>
                <set>
                    <filter>
                        <id>$domainId</id>
                    </filter>
                    <values>
						$preferences
                        <hosting>
                            <vrt_hst>";
		if ( strlen($login) > 0 ) {
			$request .= "
								<property>
									<name>ftp_login</name>
									<value>{$login}</value>
								</property>";
		}

		if ( strlen($password) > 0 ) {
			$request .= "
								<property>
									<name>ftp_password</name>
									<value>{$password}</value>
								</property>";
		}
			$request .="
                                $hostingParams
                            <ip_address>{$ip}</ip_address>
                            </vrt_hst>
                        </hosting>
                        $limits
						$template
                    </values>
                </set>
            </webspace>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'webspace', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'webspace', 'set') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to update domain', 101);
        }

        return true;
    }

    function deleteDomain($domainId)
    {
    	$request = "
            <webspace>
                <del>
                    <filter>
                        <id>$domainId</id>
                    </filter>
                </del>
            </webspace>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'webspace', 'del', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'webspace', 'del') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }
        return true;
    }

    function getDomainId($domainName)
	{
        $request="<webspace>
                    <get>
                       <filter>
                          <name>$domainName</name>
                       </filter>
                       <dataset>
                          <hosting/>
                       </dataset>
                    </get>
                    </webspace>";

        $response = $this->_sendRequest($request);

		if ($errorCode = $this->_errorCode($response, 'webspace', 'get', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'webspace', 'get') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }

        return $response['packet']['#']['webspace'][0]['#']['get'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

	function getUserId($domainUserName)
	{
        $request="<customer>
                    <get>
                       <filter>
						  <login>{$domainUserName}</login>
                       </filter>
                       <dataset>
                          <gen_info/>
						  <stat/>
                       </dataset>
                    </get>
                    </customer>";

        $response = $this->_sendRequest($request);

		if ($errorCode = $this->_errorCode($response, 'customer', 'get', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'customer', 'get') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }

        return $response['packet']['#']['customer'][0]['#']['get'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

	function getResellerId($domainUserName)
	{
		$request = "<reseller>
					   <get>
						  <filter>
							  <login>{$domainUserName}</login>
						  </filter>
						  <dataset>
							  <gen-info/>
							  <stat/>
							  <permissions/>
							  <limits/>
							  <ippool/>
						  </dataset>
					   </get>
					</reseller>";

        $response = $this->_sendRequest($request);

		if ($errorCode = $this->_errorCode($response, 'reseller', 'get', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'reseller', 'get') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }

        return $response['packet']['#']['reseller'][0]['#']['get'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

	function upgradeUserToReseller($userId)
	{
		$request = "<customer>
						<convert-to-reseller>
							<filter>
								<id>{$userId}</id>
							</filter>
						</convert-to-reseller>
					</customer>";

		  $response = $this->_sendRequest($request);

		if ($errorCode = $this->_errorCode($response, 'customer', 'convert-to-reseller', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'customer', 'convert-to-reseller') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }

        return;
	}

	function getGenInfo()
    {
    	$request = <<<EOF
    <server>
        <get>
            <gen_info/>
        </get>
    </server>
EOF;

        $response = $this->_sendRequest($request);

        return $response;
    }

    // @access	private
    function _processPackageVars(&$hostingParams, &$preferences, &$template, $packageVars, $login, $password)
    {
        $frontPageSupport = false;
        if (is_array($packageVars)) {
            foreach ($packageVars as $name => $value) {
                if ((in_array($name, $this->limitsVars) || $name == 'reseller_account' || $name == 'TemplateAttr') || (isset($packageVars['PackageNameOnServer']) && isset($packageVars['TemplateAttr']) && in_array($name, $packageVars['TemplateAttr'])))
                {
                    continue;
                }

                if ($name == 'ftp_quota') {
                    if (!is_numeric($value)) {
                        continue;
                    }
                    $value = floor($value);
                } elseif ($value == '1') {
                    $value = 'true';
                } elseif ($value == '0') {
                    $value = 'false';
                }
                if ($name == 'www') {
					if ( $value == '' )
						continue;

                    $preferences = "<prefs><www>$value</www></prefs>";
                    continue;
                }
                if ($name == 'PackageNameOnServer') {
                    $template = "<plan-name>".$this->_convertStr($value)."</plan-name>";
                    continue;
                }
                if (($name == 'fp_ssl' || $name == 'fp_auth')
                    && !$frontPageSupport) {
                    continue;
                }
                if ($name == 'webstat') {
                    $value = strtolower($value);
                    $arr = array('awstats', 'webalizer', 'smarterstats', 'urchin', 'none');
                    if (!in_array($value, $arr)) {
                        $value = 'none';
						continue;
                    }
                }

				if ( $value == '' )
					continue;

                $hostingParams .= "<property>\n\t<name>{$name}</name>\n\t<value>{$value}</value>\n</property>\n";

                if ($name == 'fp' && $value == 'true') {
                    $frontPageSupport = true;
                }
            }

            if ($frontPageSupport) {
                $hostingParams .= "
					<property>
						<name>fp_admin_login</name>
						<value>{$login}</value>
					</property>
					<property>
						<name>fp_admin_password</name>
						<value>{$password}</value>
					</property>";
            }
        }

        return true;
    }

    // @access	private
    function _getLimits($packageVars, $useMax_dom)
    {
        $limits = array();
        if (is_array($packageVars)) {
            foreach ($packageVars as $name => $value) {
                if ((is_numeric($value) && in_array($name, $this->limitsVars)) && ((!isset($packageVars['PackageNameOnServer'])) || (!isset($packageVars['TemplateAttr'])) || (!in_array($name, $packageVars['TemplateAttr']))))
                {
                    if ($name == 'max_dom' && !$useMax_dom) {
                        continue;
                    }
                    $limits[] = "<$name>".floor($value)."</$name>\n";
                }
            }
        }

        if ($limits) {
            $limits = "<limits>\n".implode('', $limits)."</limits>\n";
        } else {
            $limits = '';
        }

        return $limits;
    }

    // @access	private
    function _buildRequest($commands)
    {
        $proto = $this->rpcHandlerVersion;
        $request = <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
    <packet version="$proto">
EOF;
        $request .= "$commands\n</packet>";

        return $request;
    }

    // @access	private
    function _sendRequest($request, $skipAuth = false)
    {
		// Let's wrap it arund the approrpriate xml tags.
		$request = $this->_buildRequest($request);
        require_once 'library/CE/NE_Network.php';

        $headers = array();
        if (!$skipAuth) {
            $headers = array(
                'HTTP_AUTH_LOGIN: ' . $this->user,
                'HTTP_AUTH_PASSWD: ' . $this->password,
            );
        }

        $headers = array_merge($headers, array(
                'HTTP_PRETTY_PRINT: TRUE',
                'Content-Type: text/xml',
        ));

        $response = NE_Network::curlRequest(    $this->settings,
                                                "https://{$this->host}:{$this->rpcHandlerPort}{$this->rpcHandlerPath}",
                                                $request, $headers, true);

		if ( $response instanceof CE_Error )
		{
			throw new CE_Exception ("There was a problem with your request: ". $response);
		}

        $response = XmlFunctions::xmlize($response);

		if ( $response instanceof CE_Error )
		{
			throw new CE_Exception ("There was a problem with your XML response: ". $resposne);
		}

        return $response;
    }

    // @access	private
    function _errorCode($response, $target, $operation, &$errMessage)
    {
        // operation error
        if (isset($response['packet']['#'][$target][0]['#'][$operation][0]['#']['result'][0]['#']['errcode'][0]['#'])) {
            $errMessage = $response['packet']['#'][$target][0]['#'][$operation][0]['#']['result'][0]['#']['errtext'][0]['#'];
            return $response['packet']['#'][$target][0]['#'][$operation][0]['#']['result'][0]['#']['errcode'][0]['#'];
        }

        // general error
        if (isset($response['packet']['#']['system'][0]['#']['errcode'][0]['#'])) {
            $errMessage = $response['packet']['#']['system'][0]['#']['errtext'][0]['#'];
            return $response['packet']['#']['system'][0]['#']['errcode'][0]['#'];
        }

        // no errors
        return false;
    }

    // @access	private
    function _returnedStatus($response, $target, $operation)
    {
        if (!isset($response['packet']['#'][$target][0]['#'][$operation][0]['#']['result'][0]['#']['status'][0]['#'])) {
            return false;
        }

        return $response['packet']['#'][$target][0]['#'][$operation][0]['#']['result'][0]['#']['status'][0]['#'];
    }

    function _convertStr($str)
    {
        return $str;
        //return CE_Lib::convertStr($this->settings->get('Character Set'), 'UTF-8', $str);
    }
}

?>
