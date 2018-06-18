<?php
require_once 'modules/admin/models/SnapinPlugin.php';
require_once 'library/CE/XmlFunctions.php';

class Plugintldportal extends SnapinPlugin
{
    public $title = 'New TLD Portal';

    public function init()
    {
        $this->settingsNotes = lang('When enabled this snapin allows your customers the use of the eNom TLD Watchlist.');
        $this->addMappingForPublicMain("view", "New TLD Portal", 'When enabled this snapin allows your customers the use of the eNom TLD Watchlist', 'icon-th');
    }

    function getVariables()
    {

        $email = $this->settings->get('Support E-mail');
        $company = $this->settings->get('Company Name');

        $variables = array(
            'Plugin Name'       => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => 'New TLD Portal',
            ),
            'Login' => array(
                'type'          => 'text',
                'description'   => lang('Enter your username for your eNom reseller account.  Don\'t have an eNom account?  Get a FREE account <a target="_blank" href="https://www.clientexec.com/members/index.php?fuse=admin&view=snapin&controller=snapins&plugin=enomform">here</a>.'),
                'value'         => '',
                'required'      => true
            ),
            'Password'  => array(
                'type'          => 'password',
                'description'   => lang('Enter the password for your eNom reseller account.'),
                'value'         => '',
            ),
            'Company Name' => array(
                'type'          => 'text',
                'description'   => '',
                'value'         => $company,
            ),
            'Support Email Address' => array(
                'type'          => 'text',
                'description'   => lang('Will appear as the Reply To field for email sent on your behalf.'),
                'value'         => $email,
            ),
            'Default Portal URL' => array(
                'type'          => 'text',
                'description'   => lang('Where we should send your users when communicating to them about their new TLD lists.<br/><br/> By clicking update you agreee to the <a href="http://www.enom.com/terms/agreement.aspx?page=tldportalreseller" target="_blank">terms & conditions</a><br/><br/><a target="_blank" href="http://www.enom.com/tld-portal/manage.aspx">Click Here For Additional Configuration</a>'),
                'value'         => CE_Lib::getSoftwareURL() . '/index.php?fuse=admin&view=snapin&controller=snapins&plugin=tldportal',
            ),
            'Portal ID' => array(
                'type'          => 'hidden',
                'description'   => 'Internal value for Portal ID',
                'value'         => '',
            ),
            'Public Description'       => array(
                'type'        => 'hidden',
                'description' => 'Description to be seen by public',
                'value'       => 'Pre-order and track Domains and TLDs',
            ),
        );

        return $variables;
    }

    function view()
    {
        $this->overrideTemplate = true;

        if ( $this->user->isAnonymous() ) {
            $msg = $this->user->lang("Permission Denied");
            CE_Lib::addErrorMessage('<b>'.$msg.'</b><br/>'.$this->user->lang('You must be logged in to view this page.'));
            CE_Lib::redirectPage('index.php?fuse=home&view=login');
            return;
        }

        $token = $this->getAPIToken();

        if ( $token != '' ) {

            echo '<div id="tldportal-root"></div>
                <script src="https://tldportal.com/api/embed?token=' . $token . '" type="text/javascript"></script>';
        }

        else if ( $token == '' ) {
           $msg = $this->user->lang("Configuration Error");
           CE_Lib::addErrorMessage('<b>'.$msg.'</b><br/>'.$this->user->lang('This snapin has yet to be configured by admin.'));
           CE_Lib::redirectPage('index.php');
        }

    }

    function getAPIToken()
    {
        $arguments = array(
            'command'  => 'Portal_GetToken',
            'PortalUserID' => $this->user->getId(),
            'Email' => $this->user->getEmail()
        );

        $return = $this->makeRequest($arguments);

        if ( isset($return->token) ) {
            return $return->token;
        }
        return '';
    }

    function makeRequest($arguments)
    {
        require_once 'library/CE/NE_Network.php';

        $request = 'https://reseller.enom.com/interface.asp';
        $arguments['uid'] = $this->settings->get('plugin_tldportal_Login');
        $arguments['pw'] = $this->settings->get('plugin_tldportal_Password');
        $arguments['responsetype'] = 'XML';
        $arguments['Source'] = 'ClientExec';
        $arguments['SourceID'] = 40;

        $i = 0;
        foreach ($arguments as $name => $value) {
            $value = urlencode($value);
            if (!$i) $request .= "?$name=$value";
            else $request .= "&$name=$value";
            $i++;
        }

        $response = NE_Network::curlRequest($this->settings, $request, false, false, true);
        if (!$response) return false;

        libxml_use_internal_errors(true);
        $xmlObj = simplexml_load_string($response);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);

        //let's check for an error
        if (isset($index['ERR1'])) {
           $error_index = $index['ERR1'][0];
           $msg = $this->user->lang("Configuration Error");
           CE_Lib::addErrorMessage('<b>'.$msg.'</b><br/>'.$vals[$error_index]['value']);
           CE_Lib::redirectPage('index.php');

        }

        if ( !$xmlObj ) {
            throw new Exception('Failed to load XML');
        }
        return $xmlObj;
    }
}
