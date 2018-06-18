<?php
require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'modules/domains/models/ICanImportDomains.php';
require_once dirname(__FILE__).'/lib/apiutil.php';
/**
* @package Plugins
*/
class PluginDirecti extends RegistrarPlugin implements ICanImportDomains
{
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array (
                                'type'          =>'hidden',
                                'description'   =>lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                                'value'         =>lang('Directi')
                               ),
            lang('Use testing server') => array(
                                'type'          =>'yesno',
                                'description'   =>lang('Select Yes if you wish to use Directi\'s testing environment, so that transactions are not actually made.<br><br><b>Note: </b>You will first need to register for a demo account at<br>http://cp.onlyfordemo.net/servlet/ResellerSignupServlet?&validatenow=false.<br/><br/><span class="alert alert-danger"><b>WARNING:</b> ResellerClub has deprecated this version of the API.  Please go to the <a href="index.php?fuse=admin&amp;view=migratedirecti">Directi Migration tool</a> to switch over to the ResellerClub Plugin.</span>'),
                                'value'         =>0
                               ),
            lang('Login') => array(
                                'type'          =>'text',
                                'description'   =>lang('Enter your username for your Directi reseller account.'),
                                'value'         =>''
                               ),
            lang('Password') => array(
                                'type'          =>'password',
                                'description'   =>lang('Enter the password for your Directi reseller account.'),
                                'value'         =>''
                               ),
            lang('Role') => array(
                                'type'          =>'hidden',
                                'description'   =>lang('Type of account.'),
                                'value'         =>'reseller'
                               ),
            lang('Language') => array(
                                'type'          =>'hidden',
                                'description'   =>lang('Language preference.'),
                                'value'         =>'en'
                               ),
            lang('Parent ID') => array(
                                'type'          =>'text',
                                'description'   =>lang('This can be found in your Directi profile.'),
                                'value'         =>''
                               ),
            lang('Supported Features')  => array(
                                'type'          => 'label',
                                'description'   => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* '.lang('Existing Domain Importing').' <br>* '.lang('Get Auto Renew Status').' <br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Contact Information').' <br>',
                                'value'         => ''
                                ),
            'Actions'   => array (
                                'type'          => 'hidden',
                                'description'   => 'Current actions that are active for this plugin (when a domain isn\'t registered',
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

    function checkDomain($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

	   CE_Lib::log(4, 'inside domain check');

        $return = $DomOrder->checkAvailabilityMultiple(
                                 $params['Login'],
                                 $params['Password'],
                                 $params['Role'],
                                 $params['Language'],
                                 $params['Parent ID'],
                                 array($params['sld']),
                                 array($params['tld']),
                                 false
                                 );
        CE_Lib::log(4, 'checkAvailabilityMultiple('.print_r($return,true).')');

        if (isset($return[$domain]) && $return[$domain]['status'] === 'available') {
          $status = 0;
        } else if (isset($return[$domain]) &&  ($return[$domain]['status'] === 'regthroughus' || $return[$domain]['status'] === 'regthroughothers') ) {
            $status = 1;
        }else {
            CE_Lib::log(3, 'Directi Result: [status]=>'.$return['faultcode'].' [error]=>'.$return['faultstring']);
            throw new CE_Exception('Directi Result: '.$return['faultstring']);
        }

        $domains = array();
        $domains[] = array("tld"=>$params['tld'],"domain"=>$params['sld'],"status"=>$status);

        return $domains;

    }


    /**
     * Register domain name
     *
     * @param array $params
     */
    function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderid = $this->registerDomain($this->buildRegisterParams($userPackage,$params));
        $userPackage->setCustomField("Registrar Order Id",$userPackage->getCustomField("Registrar").'-'.$orderid);
        return true;
    }

    function registerDomain($params)
    {
        // required classes to register a domain
        require_once dirname(__FILE__).'/lib/DomContact.class.php';
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';
        require_once dirname(__FILE__).'/lib/Customer.class.php';
        require_once dirname(__FILE__).'/lib/DomContactExt.class.php';
        require_once dirname(__FILE__).'/lib/DotCoopContact.class.php';

        // variable setup and validation
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
	$mysld = strtolower($params['sld']);
	$mytld = strtolower($params['tld']);

        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }
        $countrycode = $this->getCountryCode($params['RegistrantCountry']);
        $telno = $this->_validatePhone($params['RegistrantPhone'],$countrycode);
        if ($params['RegistrantOrganizationName'] == "") $params['RegistrantOrganizationName'] = "N/A";
        if ($params['Use testing server']) {
            // Required nameservers for test server
            $nameservers = array(
                                    "0" => "dns1.parking-page.net",
                                    "1" => "dns2.parking-page.net"
                                );
        } else {
            // maximum 12 name servers are allowed by Directi
	    if (isset($params['NS1']) && isset($params['NS2'])) {
                for ($i = 1; $i <= 12; $i++) {
                    if (isset($params["NS$i"])) {
                        $nameservers["$i"] = $params["NS$i"]['hostname'];
                    } else {
                        break;
                    }
                }
            } else {
		$nameservers = array(
                                    "0" => "dns1.parking-page.net",
                                    "1" => "dns2.parking-page.net"
                                );
	    }

        }
        // Create a new customer object
        $customer = new Customer(dirname(__FILE__).'/lib/wsdl/'.$demo.'Customer.wsdl');

        /* Changed the Deprecated method addCustomer(..) to new signUp method -- Dixon Davis */

        $return = $customer->signUp(
                                     $params['Login'],
                                     $params['Password'],
                                     $params['Role'],
                                     $params['Language'],
                                     $params['Parent ID'],
                                     $params['RegistrantEmailAddress'],
                                     'clientexec123',//$params['DomainPassword'],
                                     $params['RegistrantFirstName']." ".$params['RegistrantLastName'],
                                     $params['RegistrantOrganizationName'],
                                     $params['RegistrantAddress1'],
                                     null,
                                     null,
                                     $params['RegistrantCity'],
                                     $params['RegistrantStateProvince'],
                                     $params['RegistrantCountry'],
                                     $params['RegistrantPostalCode'],
                                     $countrycode,
                                     $telno,
                                     null,
                                     null,
                                     null,
                                     null,
                                     'en',
                                     null,
                                     null
                                     );
        CE_Lib::log(4, 'signUp('.print_r($return,true).')');
        if (isset($return['faultstring'])) {
            if (strpos($return['faultstring'],"is already a customer")) {
                $return = $customer->getCustomerId($params['Login'],$params['Password'],$params['Role'],$params['Language'],$params['Parent ID'],$params['RegistrantEmailAddress']);
                CE_Lib::log(4, 'getCustomerId('.print_r($return,true).')');
            } elseif (strpos($return['faultstring'],"Zip is Invalid")) {
                throw new Exception('Client Profile Error: Zip is Invalid');
            } elseif (strpos($return['faultstring'],"Telephone Country Code/Telephone No. is invalid")) {
                throw new Exception('Telephone Country Code/Telephone No. is invalid');
            } elseif (strpos($return['faultstring'],"password")) {
                throw new Exception('Password cannot be blank and must be at least 6 characters');
            } else {

            	// As the create customer has errored, try and lookup the customer
            	$extCustomer = $customer->getDetailsByCustomerEmail(
                                     $params['Login'],
                                     $params['Password'],
                                     $params['Role'],
                                     $params['Language'],
                                     $params['Parent ID'],
                                     $params['RegistrantEmailAddress'],
                                     null);

                // Check if we returned an array
                if(@$extCustomer['customerid']) {
                	$custID = $extCustomer['customerid'];
                } else {
                    throw new Exception('Couldn\'t create client, please validate the client information.');
                }
            }
        }

        if(!$custID) {
        	$custID = print_r($return,true);
        }

        $DomContact = new DomContact(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomContact.wsdl');

        $return = $DomContact->listNames(
                                     $params['Login'],
                                     $params['Password'],
                                     $params['Role'],
                                     $params['Language'],
                                     $params['Parent ID'],
                                     $custID
                                     );
        CE_Lib::log(4, '$domContact->listNames('.print_r($return,true).')');
        $contactID = null;
        $totalContacts = $return['recsindb'];

        if (($totalContacts != null) && ($totalContacts > 0)) {
            for ($i = 1; $i <= $return['recsindb']; $i++) {
                if ($return[$i]['emailaddr'] == $params['RegistrantEmailAddress']) {
                    $contactID = $return[$i]['contactid'];
                }
            }
        }

        if ($contactID == null) {

        /* Give Some Hash default value to avoid the warning error by apiutil.php */

            $extraHash = array('action'=>'addContact');

        /* Changed the deprecated add method by addContact  method - Dixon Davis */

        $contactType = "Contact";
        if ($mytld == 'uk') {
        $contactType = "UkContact";
        }
        if ($mytld == 'co') {
            $contactType = "CoContact";
        }

            $return = $DomContact->addContact(
                                     $params['Login'],
                                     $params['Password'],
                                     $params['Role'],
                                     $params['Language'],
                                     $params['Parent ID'],
                                     $params['RegistrantFirstName']." ".$params['RegistrantLastName'],
                                     $params['RegistrantOrganizationName'],
                                     $params['RegistrantEmailAddress'],
                                     $params['RegistrantAddress1'],
                                     null,
                                     null,
                                     $params['RegistrantCity'],
                                     $params['RegistrantStateProvince'],
                                     $params['RegistrantCountry'],
                                     $params['RegistrantPostalCode'],
                                     $countrycode,
                                     $telno,
                                     null,
                                     null,
                                     $custID,
                                     $contactType,
                                     $extraHash
                                     );
            CE_Lib::log(4, '$domContact->addContact('.print_r($return,true).')');

            /* Give default Vector value to avoid the Internal Server Error by apiutil.php */

        $defaultType = array('Contact');
	    if ($mytld == 'uk') {
		$defaultType = array('UkContact');
	    }

            /* Changed the deprecated addDefaultContact method by addDefaultContacts  method - Dixon Davis */

            $return = $DomContact->addDefaultContacts(
                                           $params['Login'],
                                           $params['Password'],
                                           $params['Role'],
                                           $params['Language'],
                                           $params['Parent ID'],
                                           $custID,
                                           $defaultType
                                           );

            CE_Lib::log(4, 'addDefaultContacts('.print_r($return,true).')');
            /* Returns a Hash table. Take the Contact details only by default */
            $contactID = print_r($return['Contact'],true);
        }


        if ($mytld == 'uk') {

            $defaultType = array('UkContact');
            // get the contactId corresponding to the type of contact added by Dixon

            $return = $DomContact->getDefaultContacts(
                                    $params['Login'],
                                    $params['Password'],
                                    $params['Role'],
                                    $params['Language'],
                                    $params['Parent ID'],
                                    $custID,
                                    $defaultType
                                    );


           $contactID = print_r($return['UkContact']['registrant'],true);
        }

        // Extra information required to register a .us and .coop domain through Directi
        if ($params['tld'] == 'us' || $params['tld'] == 'coop')
        {
            if ($params['tld'] == 'us') {
                $productKey = 'domus';
                $contactDetailsHash = array(
                                            'category'  => $params['ExtendedAttributes']['us_nexus'],
                                            'purpose'   => $params['ExtendedAttributes']['us_purpose']
                                            );
            }

            $DomContactExt = new DomContactExt(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomContactExt.wsdl');
            $return = $DomContactExt->setContactDetails(
                                        $params['Login'],
                                        $params['Password'],
                                        $params['Role'],
                                        $params['Language'],
                                        $params['Parent ID'],
                                        $contactID,
                                        $contactDetailsHash,
                                        $productKey
                                        );
           CE_Lib::log(4, 'setContactDetails('.print_r($return,true).')');
        }

        // Create a new domain order object
        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

	if (trim($mytld) == 'uk') {

		$domainhash=array($domain => intval($params['NumYears']));
                $contacthash=array('registrantcontactid' => intval($contactID));

                $temp['domainhash']=$domainhash;
                $temp['contacthash']=$contacthash;
                $addParamList[] = $temp;
                $invoiceOption = 'OnlyAdd'; //or it can be PayInvoice, KeepInvoice, OnlyAdd
                $enablePrivacyProtection = false; // or true
                $validate = true; // or false;
                $extraInfo = array(); //send extra info if required


	$return = $DomOrder->registerDomain($params['Login'],
                                        $params['Password'],
                                        $params['Role'],
                                        $params['Language'],
                                        $params['Parent ID'],
                                        $addParamList,
                                        $nameservers,
                                        $custID,
                                        $invoiceOption,
                                        $enablePrivacyProtection,
                                        $validate,
                                        $extraInfo);
    } else {
        CE_Lib::log(4, "addWithoutValidation(Login=".$params['Login'].
                                        ",\nRole=".$params['Role'].
                                        ",\nLang=".$params['Language'].
                                        ",\nParentId=".$params['Parent ID'].
                                        ",\nNumYears=".print_r(array($domain => $params['NumYears']),true).
                                        ",\nnameservers=".print_r($nameservers,true).
                                        ",\ncontactID=".$contactID.
                                        ",\ncontactID=".$contactID.
                                        ",\ncontactID=".$contactID.
                                        ",\ncontactID=".$contactID.
                                        ",\ncustID=".$custID.",".
                                        "NoInvoice)"
                                        );
        $return = $DomOrder->addWithoutValidation(
                                        $params['Login'],
                                        $params['Password'],
                                        $params['Role'],
                                        $params['Language'],
                                        $params['Parent ID'],
                                        array($domain => $params['NumYears']),
                                        $nameservers,
                                        $contactID,
                                        $contactID,
                                        $contactID,
                                        $contactID,
                                        $custID,
                                        "NoInvoice"
                                        );
	}
        CE_Lib::log(4, 'addWithoutValidation('.print_r($return,true).')');

        // Let's do some error checking to see what happened
        if (isset($return['faultcode'])) {
            CE_Lib::log(4, 'Directi Fault Code: '.$return['faultstring']);
            throw new Exception($return['faultstring']);
        } elseif (@$return[$domain]['actionstatus'] === 'Success') {
            return array(1,array($return[$domain]['entityid']));
        } elseif (@$return[$domain]['status'] === 'Success') {
            return array(1,array($return[$domain]['entityid']));
        } else {
            CE_Lib::log(4, 'Directi Result: [error]=>'.$return[$domain]['error'].' [faultstring]=>'.$return['faultstring']);
            throw new Exception($return[$domain]['error']);
        }
    }

    function getCountryCode($country)
    {
        $query = "SELECT `phone_code` FROM `country` WHERE `iso`=? AND phone_code != ''";
        $result = $this->db->query($query, $country);
        $row = $result->fetch();
        return $row['phone_code'];
    }

    function _validatePhone($phone, $code)
    {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);
        // check if code is already there and delete it
        return preg_replace("/^($code)(\\d+)/", '\2', $phone);
    }

    function getContactInformation($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('RegistrantContactDetails')); //, 'AdminContactDetails', 'TechContactDetails', 'BillingContactDetails'));

        CE_Lib::log(4, 'Directi Returned: '.print_r($ret, true));

        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $name = explode(' ', $ret['registrantcontact']['name'], 2);

        $info = array();
        // some info might not be available when the privacy protection is enabled for the domain
        $info['Registrant']['OrganizationName']  = array($this->user->lang('Organization'), $ret['registrantcontact']['company']);
        $info['Registrant']['FirstName'] = array($this->user->lang('First Name'), $name[0]);
        $info['Registrant']['LastName'] = array($this->user->lang('Last Name'), isset($name[1])? $name[1] : '');
        $info['Registrant']['Address1']  = array($this->user->lang('Address').' 1', $ret['registrantcontact']['address1']);
        $info['Registrant']['Address2']  = array($this->user->lang('Address').' 2', isset($ret['registrantcontact']['address2'])? $ret['registrantcontact']['address2'] : '');
        $info['Registrant']['Address3']  = array($this->user->lang('Address').' 3', isset($ret['registrantcontact']['address3'])? $ret['registrantcontact']['address3'] : '');
        $info['Registrant']['City']      = array($this->user->lang('City'), $ret['registrantcontact']['city']);
        $info['Registrant']['StateProv']  = array($this->user->lang('Province').'/'.$this->user->lang('State'), isset($ret['registrantcontact']['state'])? $ret['registrantcontact']['state'] : '');
        $info['Registrant']['Country']   = array($this->user->lang('Country'), $ret['registrantcontact']['country']);
        $info['Registrant']['PostalCode']  = array($this->user->lang('Postal Code').'/'.$this->user->lang('Zip'), $ret['registrantcontact']['zip']);
        $info['Registrant']['EmailAddress']     = array($this->user->lang('E-mail'), $ret['registrantcontact']['emailaddr']);
        $info['Registrant']['Phone']  = array($this->user->lang('Phone Country Code'), $ret['registrantcontact']['telnocc'].$ret['registrantcontact']['telno']);

        return $info;

    }

    function setContactInformation($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';
        require_once dirname(__FILE__).'/lib/DomContact.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('RegistrantContactDetails')); //, 'AdminContactDetails', 'TechContactDetails', 'BillingContactDetails'));
        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $DomContact = new DomContact(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomContact.wsdl');

        $cc = $this->getCountryCode($params['Registrant_Country']);
        $phone = $this->_validatePhone($params['Registrant_Phone'], $cc);


        $ret2 = $DomContact->mod(
                            $params['Login'],
                            $params['Password'],
                            $params['Role'],
                            $params['Language'],
                            $params['Parent ID'],
                            $ret['registrantcontact']['contactid'],
                            $params['Registrant_FirstName']. ' '. $params['Registrant_LastName'],
                            $params['Registrant_OrganizationName'],
                            $params['Registrant_EmailAddress'],
                            $params['Registrant_Address1'],
                            $params['Registrant_Address2'],
                            $params['Registrant_Address3'],
                            $params['Registrant_City'],
                            $params['Registrant_StateProv'],
                            $params['Registrant_Country'],
                            $params['Registrant_PostalCode'],
                            $cc,
                            $phone,
                            '',
                            ''
                            );
        CE_Lib::log(4, 'Ret2: '.print_r($ret2, true));

        if (!is_array($ret2)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret2['faultstring']) && $ret2['faultstring'] != '') {
            $errors = explode('#~#', $ret2['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret2['faultstring'];
            }

            throw new Exception($error);
        }

        return $ret2['actionstatusdesc'];


    }

    function getNameServers($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('NsDetails'));

        $i = 1;
        $ns = array();
        $ns['hasDefault'] = 0;
        while (isset($ret['ns'.$i])) {
            $ns[] = $ret['ns'.$i];
            $i++;
        }
        return $ns;
    }

    function setNameServers($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('OrderDetails'));

        if (!isset($ret['orderid']) || isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $orderId = $ret['orderid'];

        $ns = array();
        foreach ($params['ns'] as $key => $value) {
            $ns['ns'.$key] = $value;
        }



        $ret = $DomOrder->modifyNameServer(
                                             $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $orderId,
                                             $ns);
        if (!is_array($ret)) return new CE_Error('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);
        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }
    }

    function checkNSStatus($params)
    {
        // seems to always return false
        throw new Exception('this is not supported');
    }

    function registerNS($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('OrderDetails'));

        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (!isset($ret['orderid']) || isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $orderId = $ret['orderid'];

        $ret = $DomOrder->addChildNameServer(
                                             $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $orderId,
                                             $params['nsname'],
                                             array($params['nsip']));

        if (!is_array($ret)) return new CE_Error('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        if (isset($ret['actionstatus']) && $ret['actionstatus'] == 'Failed') {
            throw new Exception($ret['actionstatus'].': '.$ret['actionstatusdesc']);
        }

        return $ret['actionstatus'].': '.$ret['actiontypedesc'];
    }

    function editNS($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('OrderDetails'));

        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (!isset($ret['orderid']) || isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $orderId = $ret['orderid'];

        $ret = $DomOrder->modifyChildNameServerIp(
                                             $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $orderId,
                                             $params['nsname'],
                                             $params['nsoldip'],
                                             $params['nsnewip']);

        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        return $this->user->lang('Name Server edited successfully.');
    }

    function deleteNS($params)
    {
        throw new Exception('this is not supported');
    }

    function getGeneralInfo($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'].".".$params['tld'];
        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->getDetailsByDomain(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             $domain,
                                             array('OrderDetails', 'StatusDetails', 'DomainStatus'));

        CE_Lib::log(4, 'Directi Result: '.print_r($ret, true));
        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $data = array();
        $data['expiration'] = date('m/d/Y', $ret['endtime']);
        $data['domain'] = $ret['domainname'];
        $data['id'] = $ret['orderid'];
        $data['registrationstatus'] = $ret['currentstatus'];
        $data['purchasestatus'] = isset($ret['domainstatus'][0])? $ret['domainstatus'][0] : $this->user->lang('Unknown');
        $data['autorenew'] = $ret['isOrderSuspendedUponExpiry'] == 'false'? false : true;

        return $data;
    }

    function fetchDomains($params)
    {
        require_once dirname(__FILE__).'/lib/DomOrder.class.php';

        if ($params['Use testing server']) {
            $demo = "demo";
        } else {
            $demo = "";
        }

        $DomOrder = new DomOrder(dirname(__FILE__).'/lib/wsdl/'.$demo.'DomOrder.wsdl');

        $ret = $DomOrder->listOrder(
			                                 $params['Login'],
                                             $params['Password'],
                                             $params['Role'],
                                             $params['Language'],
                                             $params['Parent ID'],
                                             null,
                                             null,
                                             null,
                                             false,
                                             null,
                                             array("Active"), // hide 'Deleted', "Suspended", "InActive", "Pending Delete Restorable"
                                             null,
                                             null,
                                             null,
                                             null,
                                             null,
                                             null,
                                             null,
                                             null,
                                             25,
                                             $params['page'],
                                             array('entity.description asc'));

        CE_Lib::log(4, 'Directi Returned: '.print_r($ret, true));

        if (!is_array($ret)) throw new Exception('Error contacting Directi', EXCEPTION_CODE_CONNECTION_ISSUE);

        if (isset($ret['faultstring']) && $ret['faultstring'] != '') {
            $errors = explode('#~#', $ret['faultstring']);
            if (isset($errors[2])) {
                $error = $errors[2];
            } else {
                $error = $ret['faultstring'];
            }

            throw new Exception($error);
        }

        $domainsList = array();
        require_once 'modules/clients/models/DomainNameGateway.php';
        for ($i = 1; $i <= $ret['recsonpage']; $i++) {
            $dom = DomainNameGateway::splitDomain($ret[$i]['entity.description']);
            $data['id'] = $ret[$i]['orders.orderid'];
            $data['sld'] = $dom[0];
            $data['tld'] = $dom[1];
            $data['exp'] = date('m/d/Y', $ret[$i]['orders.endtime']);
            $domainsList[] = $data;
        }
        $metaData = array();
        $metaData['total'] = $ret['recsindb'];
        $metaData['start'] = 1 + ($params['page'] - 1) * 25;
        $metaData['end'] = ($params['page']) * 25;
        $metaData['numPerPage'] = 25;
        return array($domainsList, $metaData);
    }

    function disablePrivateRegistration($parmas)
    {
        throw new MethodNotImplemented('Method disablePrivateRegistration has not been implemented yet.');
    }

    function setAutorenew($params)
    {
        throw new MethodNotImplemented('Method setAutorenew has not been implemented yet.');
    }

    function getRegistrarLock ($params)
    {
        throw new Exception('Method getRegistrarLock() has not been implemented yet.');
    }

    function setRegistrarLock ($params)
    {
        throw new Exception('Method setRegistrarLock() has not been implemented yet.');
    }

    function sendTransferKey ($params)
    {
        throw new Exception('Method sendTransferKey() has not been implemented yet.');
    }

    function getDNS($params)
    {
        throw new Exception('Getting DNS Records is not supported in this plugin.');
    }

    function setDNS($params)
    {
        throw new Exception('Method setDNS() has not been implemented yet.');
    }

    function hasPrivacyProtection($contactInfo)
    {
        return ($contactInfo['OrganizationName'][1] == 'PrivacyProtect.org');
    }
    function getTransferStatus($params)
    {
        throw new MethodNotImplemented('Method getTransferStatus has not been implemented yet.');
    }
}

?>
