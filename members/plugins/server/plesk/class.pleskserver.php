<?php

require_once 'library/CE/XmlFunctions.php';

/**
* @package Clientexec
*/
class PleskServer
{
    var $rpcHandlerVersion = '1.6.0.1';
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
            <client>
                <add>
                    <gen_info>
                        <pname>".$this->_convertStr($contactName)."</pname>
                        <login>".$this->_convertStr($login)."</login>
                        <passwd>".$this->_convertStr($password)."</passwd>
                        <status>0</status>
                        <phone>".$this->_convertStr($phone)."</phone>
                        <email>".$this->_convertStr($email)."</email>
                        <address>".$this->_convertStr($address)."</address>
                        <city>".$this->_convertStr($city)."</city>
                        <state>".$this->_convertStr($state)."</state>
                        <pcode>".$this->_convertStr($pcode)."</pcode>
                    </gen_info>
                </add>
            </client>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'add', $errMessage)) {
            throw new CE_Exception("Couldn't insert domain in Plesk. Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'add') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to add user', 101);
        }

        return $response['packet']['#']['client'][0]['#']['add'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

    // @access	public
    function addResellerPermissionsAndLimits($userId, $packageVars= array())
    {
        $limits = $this->_getLimits($packageVars, true);

        $request = "
            <client>
                <set>
                    <filter>
                        <id>$userId</id>
                    </filter>
                    <values>
                        <permissions>
                            <create_domains>true</create_domains>
                        </permissions>
                        $limits
                    </values>
                </set>
            </client>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'set') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to give user the Create Domains permission', 101);
        }

        return true;
    }

    // @access	public
    function addIpToUser($userId, $ip)
    {
        $request = "
            <client>
                <ippool_add_ip>
                    <client_id>$userId</client_id>
                    <ip_address>$ip</ip_address>
                </ippool_add_ip>
            </client>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'ippool_add_ip', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'ippool_add_ip') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to add IP to user', 101);
        }
    }

    // @access	public
    function removeIpFromUser($userId, $ip)
    {
        $request = "
            <client>
                <ippool_del_ip>
                    <client_id>$userId</client_id>
                    <ip_address>$ip</ip_address>
                </ippool_del_ip>
            </client>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'ippool_del_ip', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'ippool_del_ip') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to remove IP from user\s IP pool', 101);
        }
    }

    // @return	int	    domainId in Plesk
    // @access	public
    function addDomainToUser($userId, $login, $password, $domainName, $ip, $packageVars,$usrdata)
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
            <domain>
                <add>
                    <gen_setup>
                        <name>".$this->_convertStr($domainName)."</name>
                        <owner-id>$userId</owner-id>
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
            </domain>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'domain', 'add', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'domain', 'add') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to add domain to user', 101);
        }

        return $response['packet']['#']['domain'][0]['#']['add'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

    // @access	public
    function deleteUser($userId)
    {
    	$request = "
            <client>
                <del>
                    <filter>
                        <id>$userId</id>
                    </filter>
                </del>
            </client>";

		$response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'del', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'del') != 'ok') {
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
    		<domain>
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
			</domain>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        return true;
    }

    function setUserStatus($userId, $status)
    {
    	$request = "
    		<client>
    			<set>
    				<filter>
    					<id>$userId</id>
					</filter>
					<values>
    					<gen_setup>
							<status>$status</status>
						</gen_setup>
					</values>
				</set>
			</client>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        return true;
    }

    // @access	public
    function updateAccount(&$tUser, $userId, $login, $password)
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
            <client>
                <set>
                    <filter>
                        <id>$userId</id>
                    </filter>
                    <values>
                        <gen_info>
                            <pname>".$this->_convertStr($login)."</pname>
                            <login>".$this->_convertStr($login)."</login>
                            <passwd>".$this->_convertStr($password)."</passwd>
                            <phone>".$this->_convertStr($phone)."</phone>
                            <email>".$this->_convertStr($email)."</email>
                            <address>".$this->_convertStr($address)."</address>
                            <city>".$this->_convertStr($city)."</city>
                            <state>".$this->_convertStr($state)."</state>
                            <pcode>".$this->_convertStr($pcode)."</pcode>
                            <country>".$this->_convertStr($country)."</country>";
        if(strlen($organization) != 0){
        	$request .= "
                        <cname>".$this->_convertStr($organization)."</cname>
                    </gen_info>
				</values>
			</set>
		</client>";
        } else {
        	$request .= "
        			 </gen_info>
				</values>
            </set>
        </client>";
        }

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'client', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'set') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to update user data', 101);
        }

        return true;
    }

    // @access	public
    function updateDomain($domainId, $login, $password, $ip, $packageVars)
    {
    	$hostingParams = '';
        $preferences = '';
        $template = '';
        $this->_processPackageVars($hostingParams, $preferences, $template, $packageVars, $login, $password);
        $limits = $this->_getLimits($packageVars, false);

        $request = "
            <domain>
                <set>
                    <filter>
                        <id>$domainId</id>
                    </filter>
                    <values>
                        $limits
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
                        <user>
                            <enabled>true</enabled>
                            <password>".$this->_convertStr($password)."</password>
                        </user>
                    </values>
                </set>
            </domain>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'domain', 'set', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'domain', 'set') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to update domain', 101);
        }

        return true;
    }

    function deleteDomain($domainId)
    {
    	$request = "
            <domain>
                <del>
                    <filter>
                        <id>$domainId</id>
                    </filter>
                </del>
            </domain>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'domain', 'del', $errMessage)) {
            throw new CE_Error("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'domain', 'del') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }
        return true;
    }

    function getDomainId($domainName)
	{
        $request="<domain>
                    <get>
                       <filter>
                          <domain-name>$domainName</domain-name>
                       </filter>
                       <dataset>
                          <hosting/>
                       </dataset>
                    </get>
                    </domain>";

        $response = $this->_sendRequest($request);

        return $response['packet']['#']['domain'][0]['#']['get'][0]['#']['result'][0]['#']['id'][0]['#'];
    }

    function getDomainInfo($domainName)
    {
    	 $request="<domain>
                    <get>
                       <filter>
                          <domain-name>$domainName</domain-name>
                       </filter>
                       <dataset>
                          <hosting/>
                       </dataset>
                    </get>
                    </domain>";

        $response = $this->_sendRequest($request);

        if ($errorCode = $this->_errorCode($response, 'domain', 'get', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'domain', 'get') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }

        return $response;

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

    function getUserId($domainUserName)
    {
        $request="<client>
                    <get>
                       <filter>
						  <login>{$domainUserName}</login>
                       </filter>
                       <dataset>
                          <gen_info/>
						  <stat/>
                       </dataset>
                    </get>
                    </client>";

        $response = $this->_sendRequest($request);

		if ($errorCode = $this->_errorCode($response, 'client', 'get', $errMessage)) {
            throw new CE_Exception("Plesk returned: $errMessage", $errorCode);
        }
        if ($this->_returnedStatus($response, 'client', 'get') != 'ok') {
            throw new CE_Exception('Error contacting Plesk server when trying to delete user', 101);
        }

        return $response['packet']['#']['client'][0]['#']['get'][0]['#']['result'][0]['#']['id'][0]['#'];
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
                    $preferences = "<prefs><www>$value</www></prefs>";
                    continue;
                }
                if ($name == 'PackageNameOnServer') {
                    $template = "<template-name>".$this->_convertStr($value)."</template-name>";
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
                    }
                }

                $hostingParams .= "<$name>".$this->_convertStr($value)."</$name>\n";

                if ($name == 'fp' && $value == 'true') {
                    $frontPageSupport = true;
                }
            }

            if ($frontPageSupport) {
                $hostingParams .= "<fp_admin_login>".$this->_convertStr($login)."</fp_admin_login>\n<fp_admin_password>".$this->_convertStr($password)."</fp_admin_password>\n";
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
        return trim($str);
        //return CE_Lib::convertStr($this->settings->get('Character Set'), 'UTF-8', $str);
    }
}

?>
