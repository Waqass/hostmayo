<?php
/**
 *
 * Api Client
 * @author CHENWP
 *
 */
require_once('component/TcpClient.php');

class ApiClient {
	protected $config;
	protected $client;
	protected $lastResult;
	protected $lastRequest;
	protected $lastResponse;

	public function __construct($config='') {
		if ( '' == $config ) {
			$this->config = require('config.php');
		} else {
			$this->config = $config;
		}
	}

	public function buildCommand($category, $action, $params=array()) {
	    if ($category=='client' && in_array($action, array('Login','Logout'))) {
			$params = array('clid' => $this->config['user']);
		}
	    $key    = $this->getKeyOfChksum($category, $action);
	    $cltrid = $this->getCltrid();
	    $chksum = $this->getChksum($category, $action, $params, $key, $cltrid);

	    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n".
               "<request>\n".
	           "    <category>".htmlentities($category)."</category>\n".
	           "    <action>".htmlentities($action)."</action>\n".
	           "    <params>\n";

	    foreach ( $params as $k=>$v ) {
	        $k = htmlentities($k);
	        if ( is_array($v) ) {
	            foreach ( $v as $v2 ) {
	                $v2 = htmlentities($v2);
	                $xml .= "        <param name=\"{$k}\">{$v2}</param>\n";
	            }
	        } else {
	            $v = htmlentities($v);
	            $xml .= "        <param name=\"{$k}\">{$v}</param>\n";
	        }
	    }

	    $xml .= "    </params>\n".
	            "    <cltrid>".htmlentities($cltrid)."</cltrid>\n".
	            "    <chksum>".htmlentities($chksum)."</chksum>\n".
                "</request>\n";
	    return $xml;
	}

	protected function getCltrid(){
	    return 'client' . $this->config['user'] . date('ymdhis') . rand(1000, 9999);
	}

	protected function getKeyOfChksum($category, $action) {
	    $key = '';
	    switch($category) {
	        case 'domain':
	            switch($action) {
	                case 'DeleteDomain':
        	            $key = 'deldomain';
        	            break;
	                default:
	                    $key = strtolower($action);
	                    break;
	            }
	            break;
	        case 'account':
			case 'client':
	            $key = strtolower($action);
	            break;
	        case 'ssl':
	            $key = $action;
	            break;
	        default:
	            $key = strtolower($action);
	            break;
	    }
	    return $key;
	}

	protected function getChksum($category, $action, $params, $key, $cltrid) {
	    $clid       = $this->config['user'];
	    $md5_clpass = md5($this->config['pass']);

	    $chksum = '';
	    $commonStr = $clid.$md5_clpass.$cltrid.$key;
	    switch ($category) {
	        case 'domain':
	            switch($action) {
	                case 'CheckDomain':
	                case 'InfoDomain':
	                case 'DeleteDomain':
	                case 'InfoDomainExtra':
	                case 'UpdateDomainExtra':
	                case 'UpdateDomainDns':
	                case 'UpdateDomainStatus':
	                case 'GetAuthcode':
	                case 'InfoIDShield':
	                case 'AppIDShield':
	                case 'UpdateIDShield':
	                case 'RenewIDShield':
	                case 'DeleteIDShield':
	                case 'QueryRegTransfer':
	                case 'RequestRegTransfer':
	                case 'CancelRegTransfer':
	                    $commonStr .= $params['domaintype'].$params['domain'];
	                    break;
	                case 'CreateDomain':
	                    if (preg_match("/\.eu$/i", trim($params['domain']))) {
	                        $commonStr .= $params['domaintype'].$params['domain'].$params['period'].$params['dns'][0].$params['dns'][1].$params['registrant'].$params['password'];
	                    } else if (preg_match("/\.asia$/i", trim($params['domain']))) {
	                        $commonStr .= $params['domaintype'].$params['domain'].$params['period'].$params['dns'][0].$params['dns'][1].$params['registrant'].$params['admin'].$params['tech'].$params['billing'].$params['ced'].$params['password'];
	                    } else {
	                        $commonStr .= $params['domaintype'].$params['domain'].$params['period'].$params['dns'][0].$params['dns'][1].$params['registrant'].$params['admin'].$params['tech'].$params['billing'].$params['password'];
	                    }
	                    break;
	                case 'RenewDomain':
	                    $commonStr .= $params['domaintype'].$params['domain'].$params['period'];
	                    break;
	                case 'UpdateDomainPwd':
	                    $commonStr .= $params['domaintype'].$params['domain'].$params['password'];
	                    break;
	                case 'GetTmNotice':
	                    $commonStr .= $params['domaintype'].$params['lookupkey'];
	                    break;
	                case 'GetDomainPrice':
	                    $commonStr .= $params['domaintype'].$params['domain'].$params['op'].$params['period'];
	                    break;
	                case 'UpdateXxxMemberId':
	                    $commonStr .= $params['domaintype'].$params['lookupkey'].$params['memberid'];
	                    break;
	                case 'CheckContact':
	                    $commonStr .= $params['domaintype'] . $params['contactid'];
	                    break;
	                case 'CreateContact':
	                    if (isset($params['name']) && $params['name']!='') {
	                        $commonStr .= $params['name'];
	                    }
	                    if (isset($params['org']) && $params['org']!='') {
	                        $commonStr .= $params['org'];
	                    }
	                    if (isset($params['email']) && $params['email']!='') {
	                        $commonStr .= $params['email'];
	                    }
	                    break;
	                case 'UpdateContact':
	                    $commonStr .= $params['domaintype'] . $params['domain'] . $params['contacttype'];
	                    break;
	                case 'ChangeRegistrant':
	                    $commonStr .= $params['domaintype'] . $params['domain'];
	                    if (isset($params['name']) && $params['name']!='') {
	                        $commonStr .= $params['name'];
	                    }
	                    if (isset($params['org']) && $params['org']!='') {
	                        $commonStr .= $params['org'];
	                    }
	                    if (isset($params['email']) && $params['email']!='') {
	                        $commonStr .= $params['email'];
	                    }
	                    break;
	                case 'CheckHost':
	                case 'InfoHost':
	                case 'DeleteHost':
	                    $commonStr .= $params['domaintype'] . $params['hostname'];
	                    break;
	                case 'CreateHost':
	                    $commonStr .= $params['domaintype'] . $params['hostname'];
	                    if (isset($params['addr']) && $params['addr']!='') {
	                        $commonStr .= $params['addr'];
	                    }
	                    break;
	                case 'UpdateHost':
	                    $commonStr .= $params['domaintype'] . $params['hostname'];
	                    if (isset($params['addaddr']) && $params['addaddr']!='') {
	                        $commonStr .= $params['addaddr'];
	                    }
	                    if (isset($params['remaddr']) && $params['remaddr']!='') {
	                        $commonStr .= $params['remaddr'];
	                    }
	                    break;
	                case 'QueryCustTransfer':
	                    $commonStr .= $params['domaintype'] . $params['domain'] . $params['op'];
	                    break;

                case 'RequestCustTransfer':
	                    $commonStr .= $params['domaintype'] . $params['domain'] . $params['password'] . $params['curID'];
	                    break;
	                case 'CustTransferSetPwd':

                    $commonStr .= $params['domaintype'] . $params['domain'] . $params['password'];
	                    break;
	                default:
	                    $params = ksort($params);
	                    $commonStr .= implode("", $params);
	                    break;
	            }
	            break;
	        case 'account':
	            switch($action) {
	                case 'GetAccountBalance':
	                case 'GetCustomerInfo':
	                case 'ModCustomerInfo':
	                    break;
	                default:
	                    $params = ksort($params);
	                    $commonStr .= implode("", $params);
	                    break;
	            }
	            break;
	        case 'ssl':
	            switch($action) {
	                case 'ParseCSR':
	                case 'Order':
	                case 'GetApproverEmailList':
	                case 'GetCerts':
	                    break;
	                case 'ResendApproverEmail':
	                case 'Cancel':
	                case 'Info':
	                case 'Reissue':
	                case 'ResendFulfillmentEmail':
	                case 'ChangeApproverEmail':
	                    $commonStr .= $params['orderId'];
	                    break;
	                default:
	                    $params = ksort($params);
	                    $commonStr .= implode("", $params);
	                    break;
	            }
	            break;
	    }
	    $chksum = md5($commonStr);
	    return $chksum;
	}

	public function request($cmd) {
		if ( !$this->client ) {
			$client = TcpClient::connect($this->config);

		if ( false === $client ) return 1;
			$this->writeLog("GREETING:\r\n" . $client->getLastMessage());
			$this->client = &$client;
		}
		$this->writeLog("REQUEST:\r\n" . $cmd);
		$this->lastRequest = $cmd;
		$rs = $this->client->sendCommand($cmd);
		$this->writeLog("RESPONSE:\r\n" . $rs);
		$this->lastResponse = $rs;
		$requestResult = $this->parseResult($rs);
		if ($requestResult == true) {
			return 0;
		} else {
			return 2;
		}
	}

	public function parseResult($rs) {
		$rs = preg_replace('/>\s*</', '><', $rs);
		if ( preg_match('/<code>(.*)<\/code>/', $rs, $match) ) {
			$code = intval($match[1]);
		} else $code = -1;
		if ( preg_match('/<msg>(.*)<\/msg>/', $rs, $match) ) {
			$msg = $match[1];
		} else $msg = 'null';
		if ( preg_match('/<value>(.*)<\/value>/', $rs, $match) ) {
			$value = $match[1];
		} else $value = 'null';
		if ( preg_match('/<cltrid>(.*)<\/cltrid>/', $rs, $match) ) {
			$cltrid = $match[1];
		} else $cltrid = 'null';
		if ( preg_match('/<svtrid>(.*)<\/svtrid>/', $rs, $match) ) {
			$svtrid = $match[1];
		} else $svtrid = 'null';
		if ( preg_match('/<chksum>(.*)<\/chksum>/', $rs, $match) ) {
			$chksum = $match[1];
		} else $chksum = 'null';
		if ( preg_match('/<category>(.*)<\/category>/', $rs, $match) ) {
			$category = $match[1];
		} else $category = 'null';
		if ( preg_match('/<action>(.*)<\/action>/', $rs, $match) ) {
			$action = $match[1];
		} else $action = 'null';
		$this->lastResult = array(
			'category' => $category,
			'action' => $action,
			'code' => $code,
			'msg' => $msg,
			'value' => $value,
			'cltrid' => $cltrid,
			'svtrid' => $svtrid,
			'chksum' => $chksum,
		);
		if ( preg_match('/<resData>([\s\S]*)<\/resData>/', $rs, $match) ) {
			$resData = $match[1];
		} else $resData = '';
		if ( $code >= 1000 && $code < 2000 ) {
			if ( !$resData ) return true;
		if ( preg_match_all('/<data name=\"(.*)\">([\s\S]*)<\/data>/U', $resData, $match, PREG_SET_ORDER) ) {
				$data = array();
				foreach ( $match as $v ) {
					if ( isset($data[html_entity_decode($v[1])]) ) {
						if ( is_array($data[html_entity_decode($v[1])]) ) {
							$data[html_entity_decode($v[1])][] = html_entity_decode($v[2]);
						} else {
							$data[html_entity_decode($v[1])] = array($data[html_entity_decode($v[1])], html_entity_decode($v[2]));
						}
					} else {
						$data[html_entity_decode($v[1])] = html_entity_decode($v[2]);
					}
				}
				$this->lastResult['resData'] = $data;
			}
			return true;
		}
		return false;
	}

	public function getLastResult() {
		return $this->lastResult;
	}

	public function getLastRequest() {
		return $this->lastRequest;
	}

	public function getLastResponse() {
		return $this->lastResponse;
	}

	private function writeLog($str) {
		if ($this->config['log_record']==false) {
			return;
		}
		CE_Lib::log(4, $str);
	}

	public function __destruct() {
	}
}
