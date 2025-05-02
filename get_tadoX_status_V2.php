<?php

session_start(); // Session brauchen wir um uns den Token merken
                 // zu können. Sonst müssten wir uns bei tado immer 
                 // wieder einen neuen abholen.

header('Content-Type: application/json; charset=utf-8');

// Basic defintions

{
	include ('get_tadoX_status_get_token.php');
}

// Switch debug on/off

	$debugging = 1;
//	$debugging = 0;

// 1. tado stuff - get the data
//    Basic stuff for the URLs etc.

{
	$baseURL='https://my.tado.com/'; 		// Base URL
	$authURL='https://auth.tado.com/'; 		// Auth URL
	$auth2URL='https://login.tado.com/'; 		// Auth URL
	$tadoHomePage=$baseURL."api/v2/";		// API path
	$oauthURL=$authURL."oauth/token";		// oAuth path
	$oauth2URL=$auth2URL."oauth2/token";		// oAuth path
	$hopsURL='https://hops.tado.com/homes/';	// tado X
	$energyURL='https://energy-insights.tado.com/api/';	// Energy statistics

// Pathes for the different API calls

	$meCommand="me";
	$homeCommand="homes/";
	$roomsCommand="rooms";
	$roomsAndDevicesCommand="roomsAndDevices";
	$heatPumpCommand="heatPump";
	$airComfortCommand="airComfort";
	$stateCommand="state";
	$usersCommand="users";
	$weatherCommand="weather";
	$energyCommand="consumptionOverview?month=";

// Get an access token first. 

	if (isset($_GET['deviceid'])) {
	   $deviceID=$_GET['deviceid'];
	} else {
	   $deviceID="";
	}

        if (isset($_GET['what'])) {
            writeLogFile("pageCall", '{"what": "'.$_GET['what'].'"}');
	}

	if(!getAccessToken($deviceID)) {
            writeLogFile("error", '{"reason": "cannot obtain token"}');
	    exit;
	}
		
// Open curl session

	$curl=curl_init();

// Fill the header with the access token

	$headers = array(
	  'Content-Type: application/json',
	  sprintf('Authorization: Bearer %s', $_SESSION['access_token'])
	);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT, "tado-webapp");
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

// Get the me part to know the home id. We assume to have one home only.

	curl_setopt($curl, CURLOPT_URL, $tadoHomePage.$meCommand);
	$myTadoMe = curl_exec($curl);
	$myTadoMeArray=json_decode($myTadoMe, true);
	$homeID=($myTadoMeArray["homes"][0]["id"])."/";

// Now let see what is requested

	if (isset($_GET['what'])) {
	    if ($_GET['what']=='rooms') {
        	curl_setopt($curl, CURLOPT_URL, $hopsURL.$homeID.$roomsCommand);
        	$myTadoRooms = curl_exec($curl);
        	//$myTadoRoomsArray=json_decode($myTadoRooms, true);
		print($myTadoRooms);
	    }
	    else if ($_GET['what']=='wp') {
		curl_setopt($curl, CURLOPT_URL, $hopsURL.$homeID.$heatPumpCommand);
		$myTadoHeatPump = curl_exec($curl); 				
		//$myTadoHeatPumpArray=json_decode($myTadoHeatPump, true); 
		print ($myTadoHeatPump);
	    }
	    else if ($_GET['what']=='aircomfort') {
		curl_setopt($curl, CURLOPT_URL, $hopsURL.$homeID.$airComfortCommand);
		$myTadoAirComfort = curl_exec($curl); 				
		print ($myTadoAirComfort);
	    }
	    else if ($_GET['what']=='devices') {
		curl_setopt($curl, CURLOPT_URL, $hopsURL.$homeID.$roomsAndDevicesCommand);
		$myTadoRoomsAndDevices = curl_exec($curl); 				
		//$myTadoRoomsAndDevicesArray=json_decode($myTadoRoomsAndDevices, true); 
		print ($myTadoRoomsAndDevices);
	    }
//	    else if ($_GET['what']=='device') {
//		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'OPTION');
//		curl_setopt($curl, CURLOPT_URL, $hopsURL.$homeID.$roomsAndDevicesCommand.'/device/VA4288505600');
//		$myTadoDevice = curl_exec($curl); 				
//		//$myTadoDeviceArray=json_decode($myTadoDevice, true); 
//		print ($myTadoDevice);
//		curl_setopt($curl, CURLOPT_HTTPGET, true);
//	    }
	    else if ($_GET['what']=='me') {
                curl_setopt($curl, CURLOPT_URL, $tadoHomePage.$meCommand);
                $myTadoMe = curl_exec($curl);
        	$myTadoMeArray=json_decode($myTadoMe, true);
                print ($myTadoMe);
	    }
	    else if ($_GET['what']=='state') {
                curl_setopt($curl, CURLOPT_URL, $tadoHomePage.$homeCommand.$homeID.$stateCommand);
                $myTadoState = curl_exec($curl);
                print ($myTadoState);
	    }
	    else if ($_GET['what']=='users') {
                curl_setopt($curl, CURLOPT_URL, $tadoHomePage.$homeCommand.$homeID.$usersCommand);
                $myTadoUsers = curl_exec($curl);
                print ($myTadoUsers);
	    }
	    else if ($_GET['what']=='weather') {
                curl_setopt($curl, CURLOPT_URL, $tadoHomePage.$homeCommand.$homeID.$weatherCommand);
                $myTadoWeather = curl_exec($curl);
                print ($myTadoWeather);
	    }
	    else if ($_GET['what']=='energy') {
                curl_setopt($curl, CURLOPT_URL, $energyURL.$homeCommand.$homeID.$energyCommand.date("Y-m"));
                $myTadoEnergy = curl_exec($curl);
                print ($myTadoEnergy);
	    }
	    else {
		curl_setopt($curl, CURLOPT_URL, $hopsURL.$homeID.$_GET['what']);
		$myTadoWhat = curl_exec($curl); 				
		//$myTadoWhatArray=json_decode($myWhatDevices, true); 
		print ($myTadoWhat);
	    }
	}

	curl_close($curl);

// Done with getting the tado stuff

}

?>
