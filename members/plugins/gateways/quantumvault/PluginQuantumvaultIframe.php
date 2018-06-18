<?php

    function _quantumilf_http_post($host, $path, $data, $port = 80){
        $req = _quantumilf_qsencode($data);
        $response = '';
        if(false == ( $fs = fsockopen("ssl://secure.quantumgateway.com", 443, $errno, $errstr, 30))){
            die('Could not open socket');
        }else{
            $http_request  = "POST $path HTTP/1.0\r\n";
            $http_request .= "Host: $host\r\n";
            $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
            $http_request .= "Content-Length: " . strlen($req) . "\r\n";
            $agent = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (affgrabber)";
            $http_request .= "User-Agent: $agent\r\n";
            $http_request .= "\r\n";
            $http_request .= $req;

            fwrite($fs, $http_request);
            while(!feof($fs)){
                $response .= fgets($fs, 1160); // One TCP-IP packet
            }

            fclose($fs);
            $response = explode("\r\n\r\n", $response, 2);
        }
        return $response;
    }

    function _quantumilf_qsencode ($data){
        $req = "";
        foreach($data AS $key => $value){
            $req .= $key . '=' . urlencode(stripslashes($value)) . '&';
        }

        // Cut the last '&'
        $req = mb_substr($req, 0, strlen($req) -1);
        return $req;
    }

    function quantumilf_getCode($API_Username, $API_Key, $width, $height, $amount = '0', $id = '0', $custid = '0', $method = '0', $addtoVault = 'N', $skipshipping = 'N'){
        $thereturn = array();
        $random = rand(1111111111, 9999999999);
        $random = (int) $random;
        $response = _quantumilf_http_post("secure.quantumgateway.com",
                                          "/cgi/ilf_authenticate.php",
                                          array('API_Username' => $API_Username,
                                                'API_Key'      => $API_Key,
                                                'randval'      => $random,
                                                'lastip'       => $_SERVER['REMOTE_ADDR']
                                               ),
                                          443
                                         );
        if(is_array($response)){
            if($response[1] != 'error'){
                $extrapars = '';
                if($method != '0'){
                    $extrapars .= "&METHOD=$method";
                }
                if($addtoVault != 'N'){
                    $extrapars .= "&AddToVault=$addtoVault";
                }
                if($addtoVault != 'N'){
                    $extrapars .= "&skip_shipping_info=$skipshipping";
                }
                if($custid != '0'){
                    $extrapars .= "&CustomerID=" . urlencode($custid);
                }
                if($amount != '0'){
                    $extrapars .= "&Amount=$amount";
                }
                if($id != '0'){
                    $extrapars .= "&ID=" . urlencode($id);
                }

                $thereturn['iframe'] = '<iframe src="https://secure.quantumgateway.com/cgi/ilf.php?k=' . $response[1] . '&ip=' . $_SERVER['REMOTE_ADDR'] . $extrapars . '" height="' . $height . '" width="' . $width . '" frameborder="0"></iframe><br/>';
                $thereturn['script']  = '<script src="https://secure.quantumgateway.com/javascript/prototype.js" type="text/javascript"></script>
    <script type="text/javascript">
        function refreshSession(thek, theip){
            var randomnumber = Math.random();
            var tposturl = "../plugins/gateways/quantumvault/PluginQuantumvaultIframe.php?cachebuster=" + randomnumber;
            var thequerystring = "ajax=true&ip=" + theip + "&k=" + thek;
            var thecheck = new Ajax.Request(tposturl, { method: "post", parameters: thequerystring });
        }

        setInterval("refreshSession(\'' . $response[1] . '\', \'' . $_SERVER['REMOTE_ADDR'] . '\')", 20000);
    </script>
    ';
            }
        }
        return $thereturn;
    }

    if(isset($_POST['ajax'])){
        if($_POST['ajax'] == "true"){
            $response = _quantumilf_http_post("secure.quantumgateway.com",
                                              "/cgi/ilf_refresh.php",
                                              array('ip' => $_POST['ip'],
                                                    'k'  => $_POST['k']
                                                   ),
                                              443
                                             );
        }
    }
?>