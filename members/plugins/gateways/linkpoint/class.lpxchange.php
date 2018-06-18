<?php
/**
 *  class.lpxchange.php - (c) 2005 Darrel O'Pry, thing.net communications, llc
 *  - I was unhappy linkpoints php class, so I rewrote it. 6/5/2005. -dopry
 *
 *  requires php >=  4.3.0
 *
 *  This class provides a slightly more OO interface than the
 *  on provided by linkpoint. It supports php4 / php5, provides
 *  some sanity checking, and doesn't print errors directly to the
 *  output.
 *
 *  @note
 *
 *  This class will try two transports for the transaction
 *  with linkpoint.
 *	  - liblphp extension,
 *    - curl extension
 *
 *  if it is passed an XML string it will attempt to
 *  send that XML string to the LinkPoint gateway,
 *  and will return an XML string.
 *
 *  if it is passed an array it will convert
 *  that to an XML string, pass the XML to the gateway, then
 *  return a hash.
 */

// Add more validation to buildXML

class lpxchange {
  var $host;
  var $port;
  var $keyfile;
  var $debugging;
  var $debugOutput = '';
  var $htmloutput;
  var $transaction;

  function __construct($host = 'secure.linkpt.net', $port = '1129', $keyfile='linkpoint.pem', $debugging = false, $htmloutput = true) {
    $this->host = $host;
    $this->port = $port;
    $this->keyfile = $keyfile;
    $this->debugging = $debugging;
    $this->htmloutput = $htmloutput;
  }

  function _process_liblphp($xml) {
    $this->debugPrint("Transport:", "liblphp extension");
    return send_stg($xml, $this->keyfile, $this->host, $this->port);
  }

  function _process_extCurl($xml) {
    $host = "https://".$this->host.":".$this->port."/LSGSXML";
    $this->debugPrint("Transport:", "curl extension host: $host");
    $ch = curl_init ();
    curl_setopt ($ch, CURLOPT_URL,$host);
    curl_setopt ($ch, CURLOPT_POST, 1);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt ($ch, CURLOPT_SSLCERT, $this->keyfile);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	// check the existence of a common name and also verify that it matches the hostname provided.
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($this->debugging) curl_setopt ($ch, CURLOPT_VERBOSE, 1);
    #  use curl to send the xml SSL string
    $_resultXML = curl_exec ($ch);

    $this->debugPrint("curl error:",curl_error($ch));
    $this->debugPrint("curl result:",$_resultXML);
    curl_close($ch);
    return $_resultXML;
  }

  function _process_binCurl($xml, $curlPath, $curlArgs) {
    $this->debugPrint("Transport:", "curl binary");
    $host = "https://".$this->host.":".$this->port."/LSGSXML";
    $curlExec = $curlPath;

    //setup some non windows stuff
    if (!getenv("OS") == "Windows_NT") {
    if (!isset($data[curlArgs])) $data[curlArgs] = '-m 300 -s -S';
      $curlExec = $curlExec." $curlArgs ";
    }

    $curlExec = $curlExec." -d \"$xml\" -E $this->keyfile -k $host ";

    $responseXML = exec($curlExec,$resultarray, $resultnum);

    // print debug info before data checks that will change responseXML
    $this->debugPrint("Curl Command:",$curlExec);
    $this->debugPrint("Curl Binary Results:\n", $_responseXML);

    switch ($resultNum) {
      case '0' : break;
      case '1' :  $_responseXML = "<r_error>Curl Binary Error - unsupported protocol.</r_error><r_approved>FAILED</r_approved>"; break;
      case '2' :  $_responseXML = "<r_error>Curl Binary Error - failed to initialize.</r_error><r_approved>FAILED</r_approved>"; break;
      case '127' :  $_responseXML = "<r_error>Curl Binary Error - invalid curl command: $curlExec</r_error><r_approved>FAILED</r_approved>"; break;
      default:  $_responseXML = "<r_error>Curl Binary Error - exit code: $resultnum, see curl man page</r_error><r_approved>FAILED</r_approved>"; break;
    }
    return $_responseXML;
  }

  function process_payment($data) {
    $this->debugPrint("Location:",'entering process payment');
    $this->debugPrint('Data:', "\n".print_r($data,true));

    if (isset($data["xml"])) {
      $xml = $data["xml"];
    }
    else {
      $xml = $this->encodeXML($data);
    }
    $xml = "$xml";
    $this->debugPrint('XML: ',"\n".$xml);

    $transportUsed = '';
    // now we try our transports in order liblphp extension, culr extension, curl binary
    if (extension_loaded('liblphp')) {
       $transportUsed = 'liblphp';
       $responseXML = $this->_process_liblphp($xml);
    }
    elseif (extension_loaded('curl')) {
       $transportUsed = 'curl';
       $responseXML = $this->_process_extCurl($xml);
    }
    elseif (isset($data[curlPath])) {
       $transportUsed = 'curl binary';
       $responseXML = $this->_process_binCurl($xml, $data['curlPath'], $data['curlArgs']);
    }
    else {
       $transportUsed = 'no transports';
       $responseXML =  "<r_error>no transports: ext liblphp failed!, ext curl failed!, binary curl failed!</r_error><r_approved>FAILED</r_approved>";
    }

    if (strlen($responseXML) < 4) {
       $responseXML = "<r_error>Could not connect to gateway. Transport used: $transportUsed</r_error><r_approved>FAILURE</r_approved>";
    }

    $this->debugPrint("Location:", 'leaving process payment');
    $this->debugPrint('response XML:', "\n". print_r($responseXML,true));

    if (isset($data['xml'])) {
    	return $responseXML;
    }
    else {
      return $this->decodeXML($responseXML);
    }
  }

  function debugPrint($head, $body) {
    if ($this->debugging) {
        $this->debugOutput .= "$head: $body\n";
      //  implement a proper debugPrint.
      // print out incoming hash
      //if ($this->htmloutput)  print "<pre>$head:".htmlspecialchars($body)."</pre><br>";
      //else print "$head: $body\n";
    }
  }

  function getDebugOutput() {
      return $this->debugOutput;
  }


  // decode XML
  // sweet little xml parsing trick. I forgot how it works.. I remember being happy about
  // PREG_SET_ORDER.
  function decodeXML($xml) {
    preg_match_all ("/<(.*?)>(.*?)\<\/(.*?)>/", $xml, $out, PREG_SET_ORDER);
    $size = sizeof($out);
    for($i=0;$i< $size; $i++) {
      $output[$out[$i][1]] = trim($out[$i][2]);
    }
    return $output;
  }

  function encodeXML($data) {
    $xmldata = $this->mapArray($data);
    $this->debugPrint("EncodeXML: xmldata:",print_r($xmldata, true));

    $xml =  $this->array2xml('order',$xmldata);

    $this->debugPrint("EncodeXML: xmltext:", $xml);

    return $xml;
  }


  // externalize sanity checks remove legacy lphp formatted single dim array array processing.
  function mapArray($pdata) {
    $this->debugPrint('Location','entering mapArray');
    $this->debugPrint("Data: ",print_r($pdata, true));
    //first we construct a multidim array  with some validation, then we
    //convert it to xml...

    $tdata = array();
    $errors = array();

    // MERCHANTINFO NODE
    //required for any transaction
    if (isset($pdata["configfile"])) {
      $tdata['merchantinfo']['configfile'] = $pdata["configfile"];
    }
    else {
       $errors['configfile']  = 'configfile required for all transactions';
    }

    //keyfile, host, port are all provided in the gateway connection


    // ORDEROPTIONS NODE ###
    if (isset($pdata["ordertype"])) {
      $validOrderTypes = array('PREAUTH','POSTAUTH','SALE','VOID','CREDIT','CALCSHIPPING','CALCTAX');
      if (in_array($pdata["ordertype"],$validOrderTypes)) {
         $tdata['orderoptions']['ordertype'] = $pdata["ordertype"];
      }
      else {
         $errors['ordertype'] = 'invalid orderoptions->ordertype';
      }
    }
    else {
      $errors['ordertype'] = 'ordertype is required for all transactions';
    }


    if (isset($pdata["result"])) {
      $validResults = array('LIVE','GOOD','DECLINE','DUPLICATE');
      if (in_array($pdata['result'],$validResults)) {
         $tdata['orderoptions']['result'] = $pdata['result'];
      }
      else {
        $errors['result'] = 'invalid orderopions->result';
      }
    }

    if (isset($pdata['cardnumber'])) 	$tdata['creditcard']['cardnumber'] 		=  $pdata['cardnumber'];
    if (isset($pdata['cardexpmonth']))$tdata['creditcard']['cardexpmonth'] 	=  intval($pdata['cardexpmonth']);
    if (isset($pdata['cardexpyear'])) $tdata['creditcard']['cardexpyear'] 	= $pdata['cardexpyear'];
    if (isset($pdata['cvmvalue']))	$tdata['creditcard']['cvmvalue'] 		= $pdata['cvmvalue'];
    if (isset($pdata['cvmindicator']))$tdata['creditcard']['cvmindicator'] 	= $pdata['cvmindicator'];
    if (isset($pdata["track"]))		$tdata['creditcard']['track'] 			= $pdata['track'];

    // BILLING NODE
    if (isset($pdata["name"])) 		$tdata['billing']['name'] 		= $pdata['name'];
    if (isset($pdata["company"]))   $tdata['billing']['company'] 	= $pdata['company'];
    if (isset($pdata["address1"]))  $tdata['billing']['address1'] 	= $pdata['address1'];
    if (isset($pdata["address2"]))	$tdata['billing']['address2'] 	= $pdata['address2'];
    if (isset($pdata["city"]))		$tdata['billing']['city']		= $pdata['city'];
    if (isset($pdata["state"]))		$tdata['billing']['state']		= $pdata['state'];
    if (isset($pdata["zip"]))		$tdata['billing']['zip']		= $pdata['zip'];
    if (isset($pdata["country"]))	$tdata['billing']['country']	= $pdata['country'];
    if (isset($pdata["userid"]))	$tdata['billing']['userid'] 	= $pdata['userid'];
    if (isset($pdata["phone"]))		$tdata['billing']['phone'] 		= $pdata['phone'];
    if (isset($pdata['fax']))		$tdata['billing']['fax'] 		= $pdata['fax'];
    if (isset($pdata['email']))		$tdata['billing']['email'] 		= $pdata['email'];
    if (isset($pdata["addrnum"]))	$tdata['billing']['addrnum']	= $pdata['addrnum'];

    if (isset($pdata['sname']))		$tdata['shipping']['name']		= $pdata['sname'];
    if (isset($pdata['saddress1']))	$tdata['shipping']['address1']	= $pdata['saddress1'];
    if (isset($pdata["saddress2"])) $tdata['shipping']['address2']  = $pdata['saddress2'];
    if (isset($pdata['scity'])) 	$tdata['shipping']['city']  	= $pdata['scity'];
    if (isset($pdata["sstate"]))	$tdata['shipping']['state']     = $pdata['sstate'];
    if (isset($pdata["szip"]))		$tdata['shipping']['zip']      	= $pdata['szip'];
    if (isset($pdata["scountry"]))	$tdata['shipping']['country']   = $pdata['scountry'];
    if (isset($pdata["scarrier"]))	$tdata['shipping']['carrier']   = $pdata['scarrier'];
    if (isset($pdata["sitems"]))	$tdata['shipping']['items']     = $pdata['sitems'];
    if (isset($pdata["sweight"]))	$tdata['shipping']['weight']    = $pdata['sweight'];
    if (isset($pdata["stotal"]))	$tdata['shipping']['total']    = $pdata['stotal'];

    if (isset($pdata["oid"]))		$tdata['transactiondetails']['oid']			= $pdata['oid'];
    if (isset($pdata['ponumber']))	$tdata['transactiondetails']['ponumber']	= $pdata['ponumber'];
    if (isset($pdata['recurring']))	$tdata['transactiondetails']['recurring']	= $pdata['recurring'];
    if (isset($pdata['taxexempt']))	$tdata['transactiondetails']['taxexempt']	= $pdata['taxexempt'];
    if (isset($pdata['terminaltype']))$tdata['transactiondetails']['terminaltype']= $pdata['terminaltype'];
    if (isset($pdata['ip']))			$tdata['transactiondetails']['ip']			= $pdata['ip'];
    if (isset($pdata['transactionorigin']))		$tdata['transactiondetails']['transactionorigin']			= $pdata['transactionorigin'];
    if (isset($pdata['reference_number']))		$tdata['transactiondetails']['reference_number']			= $pdata['reference_number'];
    if (isset($pdata['tdate']))		$tdata['transactiondetails']['tdate']		= $pdata['tdate'];

    if (isset($pdata["chargetotal"]))	$tdata['payment']['chargetotal']	= sprintf("%01.2f", round($pdata['chargetotal'], 2));
    if (isset($pdata['tax']))				$tdata['payment']['tax']			= $pdata['tax'];
    if (isset($pdata['vattax']))			$tdata['payment']['vattax']			= $pdata['vattax'];
    if (isset($pdata['shipping']))		$tdata['payment']['shipping']		= $pdata['shipping'];
    if (isset($pdata['subtotal']))		$tdata['payment']['subtotal']		= $pdata['subtotal'];

    if (isset($pdata["void"])) 			$tdata['telecheck']['void']			= $pdata['void'];
    if (isset($pdata['routing']))			$tdata['telecheck']['routing']		= $pdata['routing'];
    if (isset($pdata['account'])) 		$tdata['telecheck']['account']		= $pdata['account'];
    if (isset($pdata['bankname'])) 		$tdata['telecheck']['bankname']		= $pdata['bankname'];
    if (isset($pdata['bankstate']))		$tdata['telecheck']['bankstate']	= $pdata['bankstate'];
    if (isset($pdata['ssn'])) 			$tdata['telecheck']['ssn']			= $pdata['ssn'];
    if (isset($pdata['dl']))	 			$tdata['telecheck']['dl']			= $pdata['dl'];
    if (isset($pdata['dlstate'])) 		$tdata['telecheck']['dlstate']		= $pdata['dlstate'];
    if (isset($pdata['checknumber'])) 	$tdata['telecheck']['checknumber']	= $pdata['checknumber'];
    if (isset($pdata['accounttype'])) 	$tdata['telecheck']['accounttype']	= $pdata['accounttype'];

    if (isset($pdata["startdate"]))		$tdata['periodic']['startdate']		= $pdata['startdate'];
    if (isset($pdata['installments']))	$tdata['periodic']['installments']	= $pdata['installments'];
    if (isset($pdata['threshold']))		$tdata['periodic']['threshold']		= $pdata['threshold'];
    if (isset($pdata['periodicity']))		$tdata['periodic']['periodicity']	= $pdata['periodicity'];
    if (isset($pdata['pbcomments']))		$tdata['periodic']['comments']		= $pdata['pbcomments'];
    if (isset($pdata['action']))			$tdata['periodic']['action']		= $pdata['action'];

    if (isset($pdata["comments"])) $tdata['notes']['comments'] = $pdata['comments'];
    if (isset($pdata["referred"])) $tdata['notes']['referred'] = $tdata['referred'];

    if(isset($pdata['items']))	$tdata['items'] = $pdata['items'];
    return $tdata;
  }

  // fun with recursion. unrolle a nested
  // array into xml, where the tags are the
  // array keys and the values are the array
  // values.
  function array2xml($node, $children) {
    $xml = "";
    if (is_array($children)) {
      foreach($children as $childname => $child) {
        if (is_array($child)) {
          $xml .= $this->array2xml($childname, $child);
        }
        else {
         $xml .= "\n<$childname>$child</$childname>";
        }
      }
    }
    if (!is_numeric($node)) {
       $xml = "\n<$node>$xml\n</$node>";
    }
    return $xml;
  }
}
?>
