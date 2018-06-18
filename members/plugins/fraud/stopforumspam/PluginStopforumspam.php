<?php
require_once 'modules/admin/models/FraudPlugin.php';
require_once 'library/CE/RestRequest.php';

/**
* @package Plugins
*/
class PluginStopforumspam extends FraudPlugin
{

    function getVariables()
    {
    	$variables = array(
            /*T*/'Plugin Name'/*/T*/   => array(
                'type'          => 'hidden',
                'description'   => /*T*/''/*/T*/,
                'value'         => /*T*/'StopForumSpam'/*/T*/
            ),
            /*T*/'Enabled'/*/T*/       => array(
                'type'          => 'yesno',
                'description'   => /*T*/'Enable if you wish CE to use the StopForumSpam API to prevent users from IPs listed in their database from having access.'/*/T*/,
                'value'         => '0'
            ),
            /*T*/'Reject Signup'/*/T*/       => array(
                'type'          => 'yesno',
                'description'   => /*T*/'Determine if you want to block orders based on data from StopForumSpam.com.'/*/T*/,
                'value'         => '0'
            ),
        	/*T*/'Can Block Ip'/*/T*/       => array(
                'type'          => 'hidden',
                'description'   => /*T*/'This plugin can determine if an ip is blockable.  Can be used in conjuction with other plugins to safely make judgements on results from method shouldblockip'/*/T*/,
                'value'         => '1'
            )
        );
        return $variables;
    }

    /**
     * used for new orders
     * @return array
     */
    function execute()
    {

    	$return_array = array();

    	//if localhost let's update ip
    	//TODO let's look at the do not block list of ips
    	if ($this->input["ip"] == "::1") {
    		$url = 'http://www.stopforumspam.com/api?email='.$this->input["email"].'&f=json';
    	} else {
    		$url = 'http://www.stopforumspam.com/api?ip='.$this->input["ip"].'&email='.$this->input["email"].'&f=json';
    	}

    	$request = new RestRequest($url, 'GET');
        $request->execute();
        $result = $request->getResponseBody();
        $result = json_decode(stripslashes("[$result]"), 1);

        //let's check if result was successful
        if (!isset($result[0])) {
        	return array();
        }

        $result = $result[0];
        if (isset($result['email']) && isset($result['email']['frequency'])) {
        	$this->result['email_frequency'] = $result['email']['frequency'];
        }

        if (isset($result['ip']) && isset($result['ip']['frequency'])) {
        	$this->result['ip_frequency'] = $result['ip']['frequency'];
        }

    	return $this->result;
    }

    public function shouldblockip($ip)
    {

    	if ($ip == "::1") return false;

    	//let's look at the do not block list of ips

		$url = 'http://www.stopforumspam.com/api?ip='.$ip.'&f=json';

		$request = new RestRequest($url, 'GET');
        $request->execute();
        $result = $request->getResponseBody();
        $result = json_decode(stripslashes("[$result]"), 1);

        if (!isset($result[0])) {
        	return false;
        }

        $result = $result[0];

        if (isset($result['ip']) && isset($result['ip']['frequency']) && $result['ip']['frequency'] > 0) {
        	//we should block
        	return true;
        }

        return false;

    }

    public function isOrderAccepted() {

    	if ($this->settings->get('plugin_stopforumspam_Reject Signup') == 0) return true;

    	if (isset($this->result['email_frequency']) && ($this->result['email_frequency'] > 0) ) {
    		$this->failureMessages[] = $this->user->lang('Your email address has been associated with spam related activities.');
    	}

        if (isset($this->result['ip_frequency']) && ($this->result['ip_frequency'] > 0) ) {
        	$this->failureMessages[] = $this->user->lang('Your ip has been associated with spam related activities.');
        }

    	if ($this->failureMessages) {
            return false;
        }

        return true;

    }

    /**
     * Gets values I need from order to determine if fraud
     * @param  array $request
     * @return void
     */
    public function grabDataFromRequest($request) {
    	$ip = CE_Lib::getRemoteAddr();
    	$this->input["ip"] = $ip;

    	//get email custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typeEMAIL;
        $result = $this->db->query($query);
        list($tEmailID) = $result->fetch();

        $this->input["email"] = $request['CT_'.$tEmailID];

    }

}