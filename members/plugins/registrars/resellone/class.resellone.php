<?php

require_once dirname(__FILE__).'/../../../library/CE/XmlFunctions.php';

/**
 * A class to hand domain lookups and registrations to ResellOne.
 * @package Clientexec
 * @author Juan David Bolivar <juan@clientexec.com>
 * @version August 21, 2007
 */
class ResellOne
{
    var $rpcHandlerVersion = '0.9';
    var $rpcHandlerPort = 52443;
    var $host;
    var $user;
    var $key;
    /**
     * Constructs a new ResellOne object.
     *
     * @param string $host The ResellOne URL
     * @param string $user The reseller username
     * @param string $key The resellers private key
     * @return ResellOne An ResellOne object
     */
    function __construct($host, $user, $key)
    {
        $this->host = $host;
        $this->user = $user;
        $this->key = $key;
    }

    /**
     * Checks the availibility of a domain.
     *
     * @param string $domain The domain name to lookup
     * @return an xmlized array result - use print_r() to view the structure
     */
    function lookup_domain($domain)
    {
        $request = "<data_block>
                      <dt_assoc>
                        <item key='protocol'>XCP</item>
                        <item key='action'>LOOKUP</item>
                        <item key='object'>DOMAIN</item>
                        <item key='attributes'>
                          <dt_assoc>
                            <item key='domain'>".$domain."</item>
                          </dt_assoc>
                        </item>
                      </dt_assoc>
                    </data_block>";
        $response = $this->_buildAndSendRequest($request);
        if ($response == null) {
            return null;
        }

        $response = XmlFunctions::xmlize($response);
        return $response['OPS_envelope']['#']['body'][0]['#']['data_block'][0]['#']['dt_assoc'][0]['#']['item'];
    }

    /**
     * Registers a domain name with ResellOne.
     *
     * @param array $params An array of parameters.
     * @return an xmlized array result - use print_r() to view the structure
     */
    function register_domain($params)
    {
        $contactblock = "  <dt_assoc>
                            <item key='state'>".$params['RegistrantStateProvince']."</item>
                            <item key='first_name'>".$params['RegistrantFirstName']."</item>
                            <item key='country'>".$params['RegistrantCountry']."</item>
                            <item key='address1'>".$params['RegistrantAddress1']."</item>
                            <item key='last_name'>".$params['RegistrantLastName']."</item>
                            <item key='address2'></item>
                            <item key='address3'></item>
                            <item key='postal_code'>".$params['RegistrantPostalCode']."</item>
                            <item key='fax'></item>
                            <item key='city'>".$params['RegistrantCity']."</item>
                            <item key='phone'>".$params['RegistrantPhone']."</item>
                            <item key='email'>".$params['RegistrantEmailAddress']."</item>
                            <item key='org_name'>".$params['RegistrantOrganizationName']."</item>
                            <item key='lang_pref'>".$params['RegistrantLanguage']."</item>
                           </dt_assoc>";

        /* tld specific items */
        switch($params['tld']) {
            case 'ca':
                $tldspecific = "<item key='isa_trademark'>".$params['ExtendedAttributes']['cira-isa-trademark']."</item>
                 <item key='legal_type'>".$params['ExtendedAttributes']['cira_legal_type']."</item>";

                 //domain_description       lang_pref
                 //                         'EN' = English, 'FR' = French
                 //add
                 //<item key='domain_description'></item>
                 //<item key='lang_pref'>".$params['RegistrantLanguage']."</item>

                break;
            case 'us':
                $tldspecific = "<item key='tld_data'>
                        <dt_assoc>
                          <item key='nexus'>
                           <dt_assoc>
                            <item key='category'>".$params['ExtendedAttributes']['us_nexus']."</item>
                            <item key='app_purpose'>".$params['ExtendedAttributes']['us_purpose']."</item>
                            <item key='validator'>".$params['RegistrantCountry']."</item>
                           </dt_assoc>
                          </item>
                         </dt_assoc>
                        </item>";
                break;
            case 'name':
                $tldspecific = "<item key='tld_data'>
                      <dt_assoc>
                       <item key='forwarding_email'>".$params['RegistrantEmailAddress']."</item>
                      </dt_assoc>
                     </item>";
                break;
            default:
                $tldspecific = "";
                break;
        }

        /* custom name servers */
        $ns = "";
        if ($params['Custom NS']) {
            $ns = "<item key='nameserver_list'>
                    <dt_array>";
            for ($i = 1; $i <= 12; $i++) {
                if (isset($params["NS$i"])) {
                    $ns.= "<item key='".($i-1)."'>
                          <dt_assoc>
                           <item key='sortorder'>".$i."</item>
                           <item key='name'>".$params['NS'.$i]['hostname']."</item>
                          </dt_assoc>
                         </item>";
                } else {
                    break;
                }
            }
            $ns .= " </dt_array>
                    </item>";
        }

        $request = "<data_block>
                     <dt_assoc>
                      <item key='action'>SW_REGISTER</item>
                      <item key='object'>DOMAIN</item>
                      <item key='protocol'>XCP</item>
                      <item key='attributes'>
                       <dt_assoc>
                        <item key='auto_renew'>1</item>
                        <item key='contact_set'>
                         <dt_assoc>
                          <item key='admin'>
                           ".$contactblock."
                          </item>
                          <item key='billing'>
                           ".$contactblock."
                          </item>
                          <item key='owner'>
                           ".$contactblock."
                          </item>
                          <item key='tech'>
                           ".$contactblock."
                          </item>
                         </dt_assoc>
                        </item>
                        <item key='custom_nameservers'>".$params['Custom NS']."</item>
                        <item key='custom_tech_contact'>1</item>
                        <item key='domain'>".$params['domain']."</item>
                        <item key='f_lock_domain'>1</item>
                        <item key='handle'>process</item>
                        ".$tldspecific."
                        ".$ns."
                        <item key='period'>".$params['NumYears']."</item>
                        <item key='reg_username'>".$params['ExtendedAttributes']['domainUsername']."</item>
                        <item key='reg_password'>".$params['ExtendedAttributes']['domainPassword']."</item>
                        <item key='reg_type'>new</item>
                       </dt_assoc>
                      </item>
                     </dt_assoc>
                    </data_block>";

        $response = $this->_buildAndSendRequest($request);
        if ($response == null) {
            return null;
        }
        $response = XmlFunctions::xmlize($response);
        return $response['OPS_envelope']['#']['body'][0]['#']['data_block'][0]['#']['dt_assoc'][0]['#']['item'];
    }

    /**
     * Private function used for building the xml request and sending it.
     *
     * @param String $request The xml request body
     * @return The xml string response
     */
    function _buildAndSendRequest($request)
    {
        $request = '<?xml version=\'1.0\' encoding="UTF-8" standalone="no" ?>
                    <!DOCTYPE OPS_envelope SYSTEM "ops.dtd">
                    <OPS_envelope>
                     <header>
                      <version>0.9</version>
                     </header>
                     <body>
                       '.$request.'
                     </body>
                    </OPS_envelope>';
        $signature = md5(md5($request.$this->key).$this->key);
        $header = "POST ".$this->host." HTTP/1.0\r\n";
        $header .= "Content-Type: text/xml\r\n";
        $header .= "X-Username: " . $this->user . "\r\n";
        $header .= "X-Signature: " . $signature . "\r\n";
        $header .= "Content-Length: " . strlen($request) . "\r\n\r\n";
        $fp = @fsockopen ("ssl://$this->host", $this->rpcHandlerPort, $errno, $errstr, 30);
        if (!$fp) {
            CE_Lib::log(4, "Couldn't connect to ResellOne: $errno, $errstr");
            return null;
        }
        fputs ($fp, $header . $request);
        $response = "";
        while (!feof($fp))
        {
            $response .= fgets ($fp, 1024);
        }
        fclose($fp);
        CE_Lib::log(4, "ResellOne response: " . $response);
        // drop the headers so we can xmlize it
        $arrResponse = explode("\n", $response);
        $response = "";
        $flag = false;
        foreach ($arrResponse as $line)
        {
            if ($flag) $response .= $line."\n";
            else if (trim($line) == "") $flag = true;
        }
        return $response;
    }
}

?>
