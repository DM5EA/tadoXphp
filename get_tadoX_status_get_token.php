<?php

// Versuchen wir also oAuth2.
// Dreh- und Angelpunkt ist das File /opt/fhem/tadoTokenDir/tadoToken.txt.
// In diesem wird der aktuelle Token Status festgehalten.
//
// {"access_token":"xxx",
//  "expires_in":600,
//  "refresh_token":"xxx",
//  "expires_time":1741610503}
//
// 2025-03-10: Bisher wird davon ausgegangen, dass der refresh-token in diesem File 
// gültig ist und funktioniert.
// Das Generieren einer komplett neuen Kette mit einer neuen deviceID ist noch nicht
// implementiert. Dazu muss die Routine getAccessToken erweitert werden. Wird eine
// deviceId übergeben, dann muss ein neuer access-token mit dem momentan auskommentierten
// Block erzeugt werden. Dazu ist vorher der Aufruf des scripts get_tado_get_device_token.php
// nötig. Dann muss die Freigabe gemäß der Web Site:
// https://support.tado.com/en/articles/8565472-how-do-i-authenticate-to-access-the-rest-api
// erfolgen.
// Das Script get_tadoX_status_V2.php sieht bereits die Übergabe eines Parameters deviceid
// in der URL vor. 
// 2025-03-11: Implementierung der neuen deviceID. Dazu ist das Script wie folgt zu starten:
// http://192.168.43.61/tado/get_tadoX_status_V2.php?deviceid=<device_code>&what=me
// <device_code> aus dem Ergebnis des Calls von get_tadoX_get_device_token.php
// 2025-05-01:
// https://www.php.net/manual/en/function.flock.php
// getAccessToken muss das Token File öffnen, locken, unlocken und schließen. Dann
// muss der File-Descriptor an die beiden Routinen übergeben werden.
// Ganzes file lesen: https://www.php.net/manual/en/function.fread.php

function getAccessToken($deviceID) {

// Diese Funktion verwendet die Variablen $_SESSION und $oauth2URL global.
// diese müssen also gesetzt sein
// Der Token wird in $_SESSION['access_token'] und $_SESSION['refresh_token'] übergeben.

	global $_SESSION, $oauth2URL;
	
	writeLogFile("getAccessToken called", "");

	$fh = readTokenFile();
	if (!$fh) {
	   return false;
	}

	if ($deviceID != "") {

	   $oauthParams=array (
		  "client_id" => "1bb50063-6b0c-4d11-bd99-387f4a91cc46",
		  "device_code" => $deviceID,
		  "grant_type" => "urn:ietf:params:oauth:grant-type:device_code");
	  
	   $oAuthcurl=curl_init();
	   curl_setopt($oAuthcurl, CURLOPT_HEADER, true);
	   curl_setopt($oAuthcurl, CURLOPT_RETURNTRANSFER, true);
	   curl_setopt($oAuthcurl, CURLOPT_POST, true);
	   curl_setopt($oAuthcurl, CURLOPT_HEADER, 'Content-Type: application/x-www-form-urlencoded');
	   curl_setopt($oAuthcurl, CURLOPT_USERAGENT, "tado-web-app");
	   curl_setopt($oAuthcurl, CURLOPT_URL, $oauth2URL);
	   curl_setopt($oAuthcurl, CURLOPT_POSTFIELDS, $oauthParams);

	   $tokenData=curl_exec($oAuthcurl);
	   writeLogFile("getAccessToken new deviceID", $tokenData);

	   $myTadoTokenArray=json_decode($tokenData, true);

	   curl_close($oAuthcurl);

	   $_SESSION['access_token']=$myTadoTokenArray["access_token"];
	   $_SESSION['refresh_token']=$myTadoTokenArray["refresh_token"];
	   $_SESSION['expires_in']=$myTadoTokenArray["expires_in"];
	   $_SESSION['expires_time']=time()+$myTadoTokenArray["expires_in"];

	   writeTokenFile($fh);

	} else if ($_SESSION['expires_time'] <= time()) {

// Refresh token

	   $oauthParams=array (
		"client_id" => "1bb50063-6b0c-4d11-bd99-387f4a91cc46",
		"grant_type" => "refresh_token",
		"refresh_token" => $_SESSION['refresh_token']);

	   $oAuthcurl=curl_init();
	   curl_setopt($oAuthcurl, CURLOPT_HEADER, true);
	   curl_setopt($oAuthcurl, CURLOPT_RETURNTRANSFER, true);
	   curl_setopt($oAuthcurl, CURLOPT_POST, true);
	   curl_setopt($oAuthcurl, CURLOPT_HEADER, 'Content-Type: application/x-www-form-urlencoded');
	   curl_setopt($oAuthcurl, CURLOPT_USERAGENT, "tado-web-app");
	   curl_setopt($oAuthcurl, CURLOPT_URL, $oauth2URL);
	   curl_setopt($oAuthcurl, CURLOPT_POSTFIELDS, $oauthParams);

	   $tokenData=curl_exec($oAuthcurl);
	   writeLogFile("getAccessToken refresh token", $tokenData);

	   $myTadoTokenArray=json_decode($tokenData, true);

	   curl_close($oAuthcurl);

	   $_SESSION['access_token']=$myTadoTokenArray["access_token"];
	   $_SESSION['refresh_token']=$myTadoTokenArray["refresh_token"];
	   $_SESSION['expires_in']=$myTadoTokenArray["expires_in"];
	   $_SESSION['expires_time']=time()+$myTadoTokenArray["expires_in"];

	   writeTokenFile($fh);
	}

	fflush($fh);
	flock($fh, LOCK_UN);
	fclose($fh);
	writeLogFile("fileLock", '{"lock": "Token file unlocked and closed"}');
	return true;
}

function buildTokenJSON() {

	$tokenArray = array("access_token" => $_SESSION['access_token'], 
			    "refresh_token" => $_SESSION['refresh_token'], 
			    "expires_in" => $_SESSION['expires_in'], 
			    "expires_time"=> $_SESSION['expires_time']);
	$tokenJSON = json_encode($tokenArray);
	return $tokenJSON;
}

function readTokenFile() {

	global $_SESSION, $oauth2URL;

	$filename="/opt/fhem/tadoTokenDir/tadoToken.txt";
	$filehandle=fopen($filename, "r+");
	if (!$filehandle) {
	   return false;
	}
	if (flock($filehandle, LOCK_EX)) {
	   writeLogFile("fileLock", '{"lock": "Token file opened and locked"}');
	   $tokenData=fread($filehandle, filesize($filename));
	   writeLogFile("readTokenFile", $tokenData);
	   if ($tokenData === FALSE) {
	      $_SESSION['access_token']="";
	      $_SESSION['refresh_token']="";
	      $_SESSION['expires_in']=0;
	      $_SESSION['expires_time']=time();
       	   } else {
	      $myTadoTokenArray=json_decode($tokenData, true);
	      $_SESSION['access_token']=$myTadoTokenArray["access_token"];
	      $_SESSION['refresh_token']=$myTadoTokenArray["refresh_token"];
	      $_SESSION['expires_in']=$myTadoTokenArray["expires_in"];
	      $_SESSION['expires_time']=$myTadoTokenArray["expires_time"];
	   }
	   return $filehandle;
	} else {
	   return false;
	}
}

function writeTokenFile($fh) {

	$tokenData = buildTokenJSON();
	ftruncate($fh, 0);      // Important as the token length may vary
	fseek($fh, 0);
	fwrite($fh, $tokenData);
	writeLogFile("writeTokenFile", $tokenData);

}

function writeLogFile($text1,$text2) {

	global $debugging;

	if ($debugging == 1) {
	   $logFile = fopen("/opt/fhem/tadoTokenDir/logToken.log", "a");
	   $json = json_encode(json_decode($text2), JSON_PRETTY_PRINT);
	   fwrite($logFile, $text1."\n");
	   fwrite($logFile, date("Y-m-d H:i:s", time())." ".time()."\n");
	   fwrite($logFile, $json."\n");
	   fwrite($logFile, "----------------------------------------------\n");
	   fclose($logFile);
	}

}
?>
