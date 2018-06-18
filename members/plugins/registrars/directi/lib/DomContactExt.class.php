<?php
//	include_once("nusoap.php");
//	include_once("apiutil.php");

	require_once dirname(__FILE__).'/nusoap.php';
        require_once dirname(__FILE__).'/apiutil.php';


	class DomContactExt
	{
		var $serviceObj;
		var $wsdlFileName;
		function DomContactExt($wsdlFileName="wsdl/DomContactExt.wsdl")
		{
			$this->wsdlFileName = $wsdlFileName;
			$this->serviceObj = new lbsoapclient($this->wsdlFileName,"wsdl");
		}
		function setContactDetails(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactId, $contactDetailsHash, $productKey)
		{
			$return = $this->serviceObj->call("setContactDetails",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactId, $contactDetailsHash, $productKey));
			debugfunction($this->serviceObj);
			return $return;
		}
		function isValidRegistrantContact(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactIdArr, $productKeys)
		{
			$return = $this->serviceObj->call("isValidRegistrantContact",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactIdArr, $productKeys));
			debugfunction($this->serviceObj);
			return $return;
		}
		function isValidContact(
			$SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactIdArr, $eligibilityCriteriaArr)
		{
			$return = $this->serviceObj->call("isValidContact",array($SERVICE_USERNAME, $SERVICE_PASSWORD, $SERVICE_ROLE, $SERVICE_LANGPREF, $SERVICE_PARENTID, $contactIdArr, $eligibilityCriteriaArr));
			debugfunction($this->serviceObj);
			return $return;
		}
	}
?>
