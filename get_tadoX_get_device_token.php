<?php

		$oauthParams=array (
		  "client_id" => "1bb50063-6b0c-4d11-bd99-387f4a91cc46",
		  "scope" => "offline_access");
	  
		$oAuthcurl=curl_init();
		curl_setopt($oAuthcurl, CURLOPT_HEADER, true);
		curl_setopt($oAuthcurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($oAuthcurl, CURLOPT_POST, true);
		curl_setopt($oAuthcurl, CURLOPT_HEADER, 'Content-Type: application/x-www-form-urlencoded');
		curl_setopt($oAuthcurl, CURLOPT_USERAGENT, "tado-web-app");
		curl_setopt($oAuthcurl, CURLOPT_URL, "https://login.tado.com/oauth2/device_authorize");
		curl_setopt($oAuthcurl, CURLOPT_POSTFIELDS, $oauthParams);

		$tokenData=curl_exec($oAuthcurl);

		print $tokenData;
          
		$myTadoTokenArray=json_decode($tokenData, true);

		curl_close($oAuthcurl);

//		$_SESSION['access_token']=$myTadoTokenArray["access_token"];
//		$_SESSION['refresh_token']=$myTadoTokenArray["refresh_token"];
?>
