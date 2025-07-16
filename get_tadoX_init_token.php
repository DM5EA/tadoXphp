<html>
<meta name="viewport" content="width=device-width, initial-scale=0.9">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<link rel="apple-touch-icon" href="/apple-touch-icon.png"/>

<body>

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

		print $tokenData."<br><br>";
          
		$myTadoTokenArray=json_decode($tokenData, true);

		print "<a href='". $myTadoTokenArray['verification_uri_complete']."' target=blank>Register device</a><br><br>";

		print "<a href='http://192.168.43.61/tado/get_tadoX_status_V2.php?deviceid=".$myTadoTokenArray['device_code']."&what=me' target=blank>Init token</a><br><br>";

		curl_close($oAuthcurl);

//		$_SESSION['access_token']=$myTadoTokenArray["access_token"]
//		$_SESSION['refresh_token']=$myTadoTokenArray["refresh_token"]
?>

</body>
</html>

