<?php
/**
* Initialize the request xml string here.
* The string in the tag like '[int]' should be replaced with actual value.
* The string in the square brackets is the data type.
*/
$request_string = '<?xml version="1.0" encoding="UTF-8"?>
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:urn="urn:pt_service">
<soapenv:Header />
<soapenv:Body>
<urn:submit
soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<campaign_code>dllfYg0JBwQ</campaign_code>
<site_url>www.surelifecover.co.uk</site_url>
<client_ip>127.0.01</client_ip>
<policy_type>J</policy_type>
<cover_purpose>1</cover_purpose>
<cover_amount>50000</cover_amount>
<cover_term>1</cover_term>
<title>[1</title>
<first_name>INTRODUCERTEST</first_name>
<last_name>xxxx</last_name>
<dob>1995-04-05</dob>
<email>will@bitscube.com</email>
<home_phone>07111111111</home_phone>
<best_call_time>1</best_call_time>
<gender>F</gender>
<is_smoker>Y</is_smoker>
<title2>1</title2>
<first_name2>ffff</first_name2>
<last_name2>sssss</last_name2>
<dob2>1995-04-05</dob2>
<email2>will@bitscube.com</email2>
<home_phone2>07111111111</home_phone2>
<best_call_time2>2</best_call_time2>
<gender2>M</gender2>
<is_smoker2>N</is_smoker2>
<terms_consent>Y</terms_consent>
<privacy_content>Y</privacy_consent>
<mode>LIVE</mode>
<keyword>01</keyword>
<match_type>02</match_type>
<network>03</network>
<device>04</device>
<ref>00</ref>
<v1>06</v1>
<v2>07</v2>
<v3>08</v3>
<v4>09</v4>
<v5>05</v5>
<i1>1</i1>
<i2>2</i2>
<i3>3</i3>
<i4>4</i4>
<i5>5</i5>
</urn:submit>
</soapenv:Body>
</soapenv:Envelope>
</env:Envelope>';
var_dump($request_string);
/**
* The values of the following fields please refer
*to the reference field list spec*
<cover_purpose>[int]</cover_purpose>//id*
<cover_amount>[int]</cover_amount> //id*
<cover_term>[int]</cover_term> //id*
<title>[int]</title> //id*
<best_call_time>[int]</best_call_time>//id*
<title2>[int]</title2> //id*
<best_call_time2>[int]</best_call_time2> //id*/
//setup the request headers here, notice at the action string and SOAPAction string 
$request_headers = array('Content-Type: application/soap+xml;charset=utf-8;action="urn:pt_service#Webservice#submit"',
'SOAPAction: "urn:pt_service#Webservice#submit"',
'Content-length: '.strlen($request_string) );
//setup the service url here
$service_url = 'https://insurance-api.pingtree.co.uk/api';
//setup the user agent for the request
$user_agent = $_SERVER['HTTP_USER_AGENT'];
//initialize the curl here
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $service_url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLINFO_HEADER_OUT, FALSE );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE );
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE );
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_POST,TRUE );
curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
curl_setopt($ch, CURLOPT_POSTFIELDS,$request_string);
curl_setopt($ch, CURLOPT_HTTPHEADER,$request_headers);
//do the submit process here

$response_string = curl_exec($ch);
//-----------------------------------------------
//parsing the response string here
/*example response below. Please get detail
refer for the reponse in specs.
<?xml version="1.0" encoding="UTF8"?>
<response>
<result>20</result>
<status>1</status>
<commission>10.00</commission>
<reference>347</reference>
<message><![CDATA[Accepted]]></message>
<redirect_url>
http://insurance-api.pingtree.co.uk/redirect?p=bUzbir4VhB-BB-Z_gMxVSy8YT15gxUB9iJMBGxi_aOq5PQuDBf_JPqgvc2egv6orCj6TJkeR5g6tX1y0D7hZgbV6DdBAlzbccKmkFNG4-PgWmaNR9LQ57cUQg6jTOXuB
</redirect_url>
</response>

//-----------------------------------------------*/
curl_close($ch);
echo 'ala';
var_dump($response_string);
?>