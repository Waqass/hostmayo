<?php

require_once('nusoap.php');

class soap_code extends wsdl {

        /**
    * construct-o-rama*
    * @access   public
    */
    function __construct($wsdl){

        $this->wsdl($wsdl);

    }

    function getCode($method,$parameters){

                $opData = $this->getOperationData($method);
                // create param string
                if(sizeof($opData['input']['parts']) > 0){
        foreach($opData["input"]["parts"] as $name => $type){
                $uqType = mb_substr($type,strrpos($type,":")+1);
                $ns = mb_substr($type,0,strrpos($type,":"));
                if($opData['input']['use'] == 'literal'){
                        $phpType = 'literal';
                } else {
                        $phpType = $this->getPHPType($uqType,$ns);
                }
                $paramStr .= "\$$name,";
                $paramDeclare .=
                "// set parameter $name\n".
                "\$$name = ".$this->varToString($parameters[$name],$phpType).";\n\n";
        }
        $paramStr = mb_substr($paramStr,0,strlen($paramStr)-1);
        }

        $str =
        "$paramDeclare".
        "// set the URL or path to the WSDL document\n".
        "\$wsdl = \"$this->wsdl\";\n\n".
        "// instantiate the SOAP client object\n".
        "\$soap = new soapclient(\$wsdl,\"wsdl\");\n\n".
        "// get the SOAP proxy object, which allows you to call the methods directly\n".
        "\$proxy = \$soap->getProxy();\n\n".
        "// get the result, a native PHP type, such as an array or string\n".
        "\$result = \$proxy->$method($paramStr);\n\n";
        return $str;
    }

    function varToString($value,$type){
        if($type == "array" || $type == "struct"){
                //print "varToString(): got an array of type '$type' and value '$value'<br>";
                foreach($value as $k => $v){
                        $quote = is_numeric($v) ? "" : "\"";
                        //print "testing param $v... ";
                        if($type == "array"){
                                $str .= ",$quote$v$quote";
                                //print "it's numeric<br>";
                        } else {
                                $str .= ",$k=>$quote$v$quote";
                                //print "it's a string<br>";
                        }
                }
                return "array(".mb_substr($str,1).")";
        } else {
                if(is_numeric($value)){
                        return "$value";
                } else {
                        return "\"$value\"";
                }
        }
        }
}

session_start();

session_cache_limiter('private');

if ($REQUEST_METHOD=='POST') {

   //header('Expires: ' . gmdate("D, d M Y H:i:s", time()+1000) . ' GMT');
   //header('Cache-Control: Private');
}

print "
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">

<html>
<head>
        <title>NuSphere Web Service Wizard</title>
        <style type='text/css'>
        body { color: #000000; background-color: white; font-family: arial; margin-left: 5px; margin-top: 5px; }
        #main { margin-left: 30px; font-size: .70em; padding-bottom: 2em; }
    #code { padding:3px; background-color:#ccccff; layer-background-color:#ccccff;}
    #request { padding:3px;background-color: silver; layer-background-color:#ccccff;}
    #response { padding:3px;background-color: silver; layer-background-color:#ccccff;}
        </style>
</head>

<body>
<div id='main'>
        <h3><a href='$PHP_SELF'>NuSphere Web Service Wizard</a></h3>
        The NuSphere Web Service Wizard generates PHP code for calling web services.
        <br><br>
";

// intialize $step
if(!$step){
        $step = 1;
}

/*****************************
step 1: choose a wsdl document, or a service
*****************************/

if($step == 1){

        if($service == 'soapbuilders'){
                print "Getting SOAPBuilders servers... ";
                // get other interop endpoints
                $soapclient = new soapclient('http://www.whitemesa.net/interopInfo');
                $soapbuilders = $soapclient->call('GetEndpointInfo',array('groupName'=>'base'),'http://soapinterop.org/info/','http://soapinterop.org/info/');
                if(!$error = $soapclient->getError()){
                print "done<br>";
                        foreach($soapbuilders as $k => $v){
                                $v['name'] = $v['endpointName'];
                                $endpoints[$v['endpointName']] = $v;
                        }
                } else {
                         $buffer .= $error;
                }
        }
        if($service == 'xmethods'){
                $wsdl = "http://www.xmethods.net/interfaces/query.wsdl";
                print "Getting XMethods services... ";
                //$soap = new soapclient($wsdl,"wsdl");
                //$proxy = $soap->getProxy();
                //$xmethods = $proxy->getAllServiceSummaries();
                $soap = new soapclient('http://www.xmethods.net/interfaces/query');
                $xmethods = $soap->call('getAllServiceSummaries',array(),'http://www.xmethods.net/interfaces/query');
                if(!$error = $soap->getError()){
                        print "done<br>";
                        foreach($xmethods as $v){
                                $endpoints[$v['name']] = $v;
                        }
                } else {
                        $buffer .= $error;
                }
        }

        if(is_array($endpoints)){
                $buffer .= "
                <form action='$PHP_SELF' method='post'>
                <input type='hidden' name='step' value='2'>
                <strong>Step 1:</strong> Choose a service
                <select name='wsdlURL' onChange='submit(this.form)'>
                        <option>...";

                foreach($endpoints as $k => $v){
                        $buffer .= "<option value='".$v['name']."::".$v['wsdlURL']."'>$k\n";
                }

                $buffer .= "</select>";
        } elseif($myWSDL){
                $step = 2;
        } else         {
                $buffer .= "
                <form action='$PHP_SELF' method='post'>
                <input type='hidden' name='step' value='1'>
                <strong>Choose a service:</strong>
                <select name='service' onChange='submit(this.form)'>
                        <option>...
                        <option value='xmethods'>XMethods Services
                        <option value='soapbuilders'>SoapBuilders Servers
                </select>
        <br><br>
                or enter a WSDL document URL:
                <input type='text' name='myWSDL'>
                <input type='submit' value='Load'>";
        }
        $buffer .= "</form>";
}

/*********************************
step 2: choose an operation
*********************************/

if($step == 2){
        // get manual entry
        if($myWSDL != ""){
                $wsdlURL = $myWSDL;
                $serviceName = "Custom WSDL";
        } else {
                $arr = explode('::',$wsdlURL);
                $serviceName = stripslashes($arr[0]);
                $wsdlURL = $arr[1];
        }
        print "Loading WSDL... ";
        // load wsdl, get operation list
        if(!isset($codegen) || get_class($codegen) != 'soap_code' || $codegen->wsdl != $wsdlURL){
               // session_register('codegen');
		   // Deprecated @link http://www.php.net/manual/en/function.session-register.php
                $codegen = new soap_code($wsdlURL);
        }
        print "done.<br>";
        if(!$error = $codegen->getError()){
                // print form
                $buffer .= "
                <form action='$PHP_SELF' method='post'>
                <input type='hidden' name='step' value='3'>
                <input type='hidden' name='wsdlURL' value='$wsdlURL'>
                <input type='hidden' name='serviceName' value='".stripslashes($serviceName)."'>
                <strong>Current Service:</strong> <a href='$wsdlURL'>".stripslashes($serviceName)."</a><br><br>
                <strong>Step 2: Choose an operation</strong>
                <select name='operation' onChange='submit(this.form)'>
                        <option>...";

                        foreach($codegen->getOperations('soap') as $op => $data){
                                $buffer .= "<option value='$op'>$op\n ";
                        }

                $buffer .= "</select>
                </form>";
        } else {
                $buffer .= "<br>ERROR: $error<br>";
        }
}

/**********************************************
step 3: print form for entering parameters
**********************************************/

if($step == 3){
        // load wsdl, get operation list
        print "Loading WSDL... ";
        if(!isset($codegen) || get_class($codegen) != 'soap_code'){
                print "not set<br>";
            print "class: ".get_class($codegen)."<br>";
                $codegen = new soap_code($wsdlURL);
        }
        print "done<br>";

        $opData = $codegen->getOperationData($operation);
        // print form
        $buffer .= "
        <form action='$PHP_SELF' method='post'>
        <strong>Current Service:</strong> <a href='$wsdlURL'>".stripslashes($serviceName)."</a><br>
        <strong>Current Operation:</strong> $operation<br>";
        //if($opData['documentation'] != ''){
                $buffer .= "<strong>Documentation:</strong> ".$opData['documentation']."<br>";
        //}
        $buffer .=
        "<input type='hidden' name='step' value='4'>
        <input type='hidden' name='serviceName' value='".stripslashes($serviceName)."'>
        <input type='hidden' name='wsdlURL' value='$wsdlURL'>
        <input type='hidden' name='operation' value='$operation'>
        <strong>Step 3: Enter parameters</strong><br>";

        if(count($opData['input']['parts']) > 0){
                foreach($opData['input']['parts'] as $k => $v){
                        // get unqualified name
                        $type = mb_substr($v,strrpos($v,":")+1);
                        if($opData['input']['use'] == 'literal'){
                                $buffer .= "This is a document/literal style operation, which passes an xml document
                                to the service, and receives one in return. Below is the document structure. Please add
                                element or attribute content where necessary.<br>";
                                $buffer .= "<input type='hidden' name='types[$k]' value='$phpType'>";
                                $buffer .= "<textarea name='parameters[$k]' rows=4 cols=40>";
                                $buffer .= $codegen->serializeTypeDef($type);
                                $buffer .= "</textarea>";
                        } else {
                                $buffer .= "<br><strong>Parameter:</strong> $k of type '$type'<br>";
                                if($frmData = $codegen->typeToForm($k,$type)){
                                        $buffer .= $frmData;
                                } else {
                                        $buffer .= "Could not get data for parameter $k of type '$type'";
                                }
                        }
                }
        } else {
                $buffer .= "<br>No input parameters for this operation.<br>";
        }
        $buffer .= "
    <p>
    <input type='radio' name='execute'value='yes'> Generate Code and Execute it<br>
    <input type='radio' name='execute' value='no'> Generate Code
    </p>";
        $buffer .= "<input type='submit' value='generate code'></form>";
}

/**********************************************
step 4: print PHP code
**********************************************/

if($step == 4){
        print "Generating code from WSDL... ";
        //$codegen = new soap_code($wsdlURL);
        if(!isset($wsdl)){
                print "not set<br>";
                $codegen = new soap_code($wsdlURL);
        }
        // get code
        $code = $codegen->getCode($operation,$parameters);
        print "done<br>";

        // print result
        $buffer .= "
        <strong>Current Service:</strong> <a href='$wsdlURL'>".stripslashes($serviceName)."</a><br>
        <strong>Current Operation:</strong> $operation<br><br>";

        if($execute == 'yes'){
        // test result - do a real-time execution of your service
        eval($code);
        $buffer .= "<strong>Result:</strong> $result<br>";
        $buffer .= "<strong>Result Details:</strong><br>";
        ob_start();
        var_dump($result);
        $d = ob_get_contents();
        ob_end_clean();

        $buffer .= nl2br($d);

        $request = $proxy->request;
        $response = str_replace("xmlns:","\n xmlns:",str_replace("><",">\n<",$proxy->response));
        $wirereps = "
        <br>
        <div id='request'><strong>Request:</strong>".formatDump($request)."</div><br>
        <div id='response'><strong>Response:</strong>".formatDump($response)."</div><br>";
                }
        //

        $buffer .= "
        <br>
        <div id='code'><strong>Generated PHP Code:</strong><br><br>".formatDump($code)."</div>";
        //
        $buffer .= $wirereps;
        //
}

$footer = "
</div>
</body>
</html>";

print $buffer.$footer;

function formatDump($str){
        $str = htmlspecialchars($str);
        //$str = preg_replace("\t",'',$str);
        return nl2br($str);
}

?>