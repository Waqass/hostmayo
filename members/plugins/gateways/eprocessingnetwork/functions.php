<?php

/*************************************************
// Program: PHPAUTHNET AIM
// Version: 1.0
// Author: Hasan Robinson 
// Copyright (c) 2002,2003 AuthnetScripts.com
// All rights reserved.
//
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.
//
//------------------------------------------------------------------------

Support for PHPAUTHNET AIM:
support@authnetscripts.com

Or, write to:
Sound Commerce
4741 Central
Suite 347
Kansas City, MO 64112

The latest version of PHPAUTHNET AIM can be obtained from:
http://www.authnetscripts.com

*************************************************/

//Include Configuration File


/**** Minimum Requirements ****/
$data .= "x_Login=$authnet[login]&";
$data .= "x_Tran_Key=$authnet[tran_key]&";


/**** Contact Information ****/
$data .= "x_First_Name=$authnet[firstname]&";
$data .= "x_Last_Name=$authnet[lastname]&";
$data .= "x_Address=$authnet[address]&";
$data .= "x_City=$authnet[city]&";
$data .= "x_State=$authnet[state]&";
$data .= "x_Zip=$authnet[zip]&";
$data .= "x_Email=$authnet[email]&";
$data .= "x_Email_Customer=$authnet[email_customer]&";
$data .= "x_phone=$authnet[phone]&";
$data .= "x_country=$authnet[country]&";
$data .= "x_company=$authnet[company]&";
$data .= "x_Customer_Organization_Type=$authnet[organization_type]&";
$data .= "x_customer_ip=$authnet[customer_ip]&"; 

/*
$data .= "x_fax=$authnet[fax]&";
$data .= "x_customer_tax_id=$authnet[customer_ssn]&";
*/

/**** Order Information ****/
$data .= "x_Amount=$authnet[amount]&";
$data .= "x_Card_Num=$authnet[cardnum]&";
$data .= "x_Exp_Date=$authnet[expdate]&";
$data .= "x_Cust_ID=$authnet[cust_id]&";
$data .= "x_Invoice_Num=$authnet[invoice_num]&";
$data .= "x_Description=$authnet[description]&";


$data .= "x_card_code=$authnet[card_code]&";
$data .= "x_trans_id=$authnet[trans_id]&";


/**** Authorizenet Configuration Defaults ****/ 
$data .= "x_Version=$authnet[version]&";
$data .= "x_Delim_Data=TRUE&";
$data .= "x_Delim_Char=,&";
$data .= "x_Type=$authnet[type]&";
$data .= "x_Test_Request=$authnet[test]&";
$data .= "x_Method=$authnet[method]&";
$data .= "x_relay_response=false&";


/**** Email Receipt Configuration ****/
//$data .= "x_Merchant_Email=$authnet[merchant_email]&";
/*
$data .= "x_email_customer=$authnet[email_customer]&";
*/

CE_Lib::log(4, 'Calling ePN using cURL extension');

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch,CURLOPT_URL,$authnet['url']);
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

//Start ob to prevent curl_exec from displaying stuff.
ob_start();
curl_exec($ch);

//Get contents of output buffer into the authnet_array.
$authnet_array = ob_get_contents();
curl_close($ch);

//End ob and erase contents.
ob_end_clean();
CE_Lib::log(4, 'ePN response: '. $authnet_array);
$authnet_array = explode(",",$authnet_array);


//Split the contents into an array
$authnet_results = array(
    "x_response_code"        => "$authnet_array[0]",
    "x_response_subcode"     => "$authnet_array[1]",
    "x_response_reason_code" =>  "$authnet_array[2]",
    "x_response_reason_text" => "$authnet_array[3]",
    "x_auth_code"            => "$authnet_array[4]",
    "x_avs_code"             => "$authnet_array[5]",
    "x_trans_id"             => "$authnet_array[6]",
    "x_invoice_num"          => "$authnet_array[7]",
    "x_description"          => "$authnet_array[8]",
    "x_amount"               => "$authnet_array[9]",
    "x_method"               => "$authnet_array[10]",
    "x_type"                 => "$authnet_array[11]",
    "x_cust_id"              => "$authnet_array[12]",
    "x_first_name"           => "$authnet_array[13]",
    "x_last_name"            => "$authnet_array[14]",
    "x_company"              => "$authnet_array[15]",
    "x_address"              => "$authnet_array[16]",
    "x_city"                 => "$authnet_array[17]",
    "x_state"                => "$authnet_array[18]",
    "x_zip"                  => "$authnet_array[19]",
    "x_country"              => "$authnet_array[20]",
    "x_phone"                => "$authnet_array[21]",
    "x_fax"                  => "$authnet_array[22]",
    "x_email"                => "$authnet_array[23]",
    "x_ship_to_first_name"   => "$authnet_array[24]",
    "x_ship_to_last_name"    => "$authnet_array[25]",
    "x_ship_to_company"      => "$authnet_array[26]",
    "x_ship_to_address"      => "$authnet_array[27]",
    "x_ship_to_city"         => "$authnet_array[28]",
    "x_ship_to_state"        => "$authnet_array[29]",
    "x_ship_to_zip"          => "$authnet_array[30]",
    "x_ship_to_country"      => "$authnet_array[31]",
    "x_tax"                  => "$authnet_array[32]",
    "x_duty"                 => "$authnet_array[33]",
    "x_freight"              => "$authnet_array[34]",
    "x_tax_exempt"           => "$authnet_array[35]",
    "x_po_num"               => "$authnet_array[36]",
    "x_md5_hash"             => "$authnet_array[37]",
    "x_card_code"            => "$authnet_array[38]"
);
?>
