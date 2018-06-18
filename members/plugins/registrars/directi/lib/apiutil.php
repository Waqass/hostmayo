<?php

    /* Includes and globals removed by Mike Mallinson */

	function debugfunction($serviceObj)
	{
		$debug = false;
		if($debug)
		{
			print "<b>Debug Mode is True:</b></br></br>";
			print "<b>XML Sent:</b><br><br>";
			print "<xmp>" . str_replace("<", "\n     <", str_replace(">", ">\n",$serviceObj->request)) . "</xmp>";
			print "<br><b>XML Received:</b><br><br>";
			print "<xmp>" . str_replace("<", "\n     <", str_replace(">", ">\n",$serviceObj->response)) . "</xmp>";
			print "<br>";
		}
	}
	function getArrayFromString($strValue)
	{
		$tok = strtok($strValue, ",");
		$arrValue= array();
		while ($tok)
		{
			$arrValue[]=$tok;
			$tok = strtok(",");
		}
		return $arrValue;
	}
	function getVectorFromStringOld($strValue)
	{
		$tok = strtok($strValue, "#");
		$arrValue= array();
		while ($tok)
		{
			$arrValue[]=$tok;
			$tok = strtok("#");
		}
		return $arrValue;
	}

	function getVectorFromString($strValue)
	{
		return $strValue;
	}

	function getHashFromStringOld($strValue)
	{
		$tok = strtok($strValue, "#");
		while ($tok)
		{
			$p=strrpos($tok,"->");
			$hashValue[mb_substr($tok,0,$p)]=mb_substr($tok,$p+2); ;
			$tok = strtok("#");
		}
		return $hashValue;
	}

	function getHashFromStringDelimiter($strValue,$delimiter)
	{
		foreach($strValue as $key=>$value)
        {
			$p=strrpos($value,$delimiter);
			$valueDetails = mb_substr($value,$p+2);
            $hashValue[mb_substr($value,0,$p)]=$valueDetails;
        }
		return $hashValue;
	}

	function getHashFromString($strValue)
	{
		foreach($strValue as $key=>$value)
        {
			$p=strrpos($value,"->");
			$valueDetails = mb_substr($value,$p+2);
			print "Check Outer Value ".$valueDetails."<BR>";
			print "Check Split".count(explode(',',$valueDetails));
			if(count(explode(',',$valueDetails)) > 1)
			{
				print "Got More Values<BR>";
				$tok=strtok($valueDetails, ",");
				$innerarry=array() ;
				while ($tok)
				{
					print "Got Inner Values ".$tok."<BR>";
					$innerp=strrpos($tok,"=");
					$innerarrykey=trim(mb_substr($tok,0,$innerp));
					$innerarrvalue=trim(mb_substr($tok,$innerp+1));
					$innerarray[$innerarrykey]=trim($innerarrvalue);
					$tok = strtok(",");
				}
				$valueDetails = $innerarray;
				print "<BR>Check the Ultimate Array";
				print_r ($innerarray);
			}
			else
			{
				$valueDetails = mb_substr($value,$p+2);
			}
            $hashValue[mb_substr($value,0,$p)]=$valueDetails;
        }
		print "<BR>Check the Ultimate Hash";
		print_r ($innerHash);
		return $hashValue;
	}

	function processResponse($returnValue)
	{
		$response = new Response($returnValue);
		print "<b>Output</b><br><br>";
		if($response->isError())
		{
			$errorObj = $response->getErrorObj();
			$errorObj->printError();
		}
		else
		{
			$result = $response->getResult();
			$response->printData($result);
		}
	}
?>