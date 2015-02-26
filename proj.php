#!/usr/bin/php
<?php
//error_reporting(0);
include 'php_serial.class.php';
include_once('projectorCommands.inc');

$skipJSsettings = 1;
include_once '/opt/fpp/www/config.php';
include_once '/opt/fpp/www/common.php';

$pluginName  = "ProjectorControl";

include_once 'functions.inc.php';

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);

$logFile = $settings['logDirectory'] . "/".$pluginName.".log";
$myPid = getmypid();

//$cfgServer="192.168.192.15";
$cfgPort="3001";
$cfgTimeOut=10;
$DEBUG=false;
$SERIAL_DEVICE="";
$callBackPid="";

//$DEVICE = ReadSettingFromFile("DEVICE",$pluginName);
$DEVICE = $pluginSettings['DEVICE'];

//$DEVICE_CONNECTION_TYPE = ReadSettingFromFile("DEVICE_CONNECTION_TYPE",$pluginName);
$DEVICE_CONNECTION_TYPE = $pluginSettings['DEVICE_CONNECTION_TYPE'];

//$IP = ReadSettingFromFile("IP",$pluginName);
$IP = $pluginSettings['IP'];

//$PORT = ReadSettingFromFile("PORT",$pluginName);
$PORT = $pluginSettings['PORT'];

//$ENABLED = ReadSettingFromFile("ENABLED",$pluginName);
$ENABLED = $pluginSettings['ENABLED'];

//$PROJECTOR = urldecode(ReadSettingFromFile("PROJECTOR",$pluginName));
$PROJECTOR = $pluginSettings['PROJECTOR'];

$PROJECTOR_READ = $PROJECTOR;

if(trim($PROJECTOR_READ == "" )) {
	logEntry("No Projector configured in plugin, exiting");
	exit(0);
	
}

$options = getopt("c:d:h:p:s:z:");


if($options["d"] == "") {
	echo "Must specify device type using -d: (-dIP or -dSERIAL) \n";
	exit(0);
}

if($options["d"] == "IP" && $options["h"] == "") {
	echo "If using -dIP must supply hostname or IP xxx.xxx.xxx.xxx\n";
	exit(0);
}

if($options["h"] != "" && $options["p"] == "") {
	$PORT = "3001";
}

if($options["d"] == "SERIAL" && $options["s"] =="" ) {
	logEntry("MUST SPECIFY PORT -sttyUSB");
	exit(0);
	
}

if($options["s"] != "" && $options["d"] == "SERIAL") {
	$SERIAL_DEVICE="/dev/".$options["s"];
}

if($options["p"] !="" && $options["d"] == "IP") {
	$PORT = $options["p"];
}
if($options["z"] != "") {
	$callBackPid = $options["z"];
}
logEntry("callback pid: ".$callBackPid);





$cmd= strtoupper(trim($options["c"]));

//loop through the array of projectors to get the command
$projectorIndex = 0;
//set the found flag, do not send a command if the name and command cannot be found

$PROJECTOR_FOUND=false;

for($projectorIndex=0;$projectorIndex<=count($PROJECTORS)-1;$projectorIndex++) {
	
	if($PROJECTORS[$projectorIndex]['NAME'] == $PROJECTOR_READ) {
		echo "CMD: ".$cmd."\n";
	//	print_r($PROJECTORS[$projectorIndex]);
			while (list($key, $val) = each($PROJECTORS[$projectorIndex])) {
				echo "key: ".$key." -- value: ".$val."\n";
				if(strtoupper(trim($key)) == $cmd) {
					$PROJECTOR_FOUND=true;
					$PROJECTOR_CMD = $val;
					$PROJECTOR_BAUD=$PROJECTORS[$projectorIndex]['BAUD_RATE'];
					$PROJECTOR_CHAR_BITS=$PROJECTORS[$projectorIndex]['CHAR_BITS'];
					$PROJECTOR_STOP_BITS=$PROJECTORS[$projectorIndex]['STOP_BITS'];
					$PROJECTOR_PARITY=$PROJECTORS[$projectorIndex]['PARITY'];
					continue;	
				}
  			  //echo "$key => $val\n";
			}
	}
}

logEntry("PROJECTOR CMD FOUND: PROJECTOR: ".$PROJECTOR_READ." CMD: ".$cmd." : ".$PROJECTOR_CMD);
logEntry("BAUD RATE: ".$PROJECTOR_BAUD);
logEntry("CHAR BITS: ".$PROJECTOR_CHAR_BITS);
logEntry("STOP BITS: ".$PROJECTOR_STOP_BITS);
logEntry("PARITY: ".$PROJECTOR_PARITY);
if(!$PROJECTOR_FOUND) {
	logEntry("No projector command found: exiting");
	exit(0);
	
}

if(strtoupper($options["d"]) =="SERIAL") {
	logEntry("Opening Serial device: ".$SERIAL_DEVICE);

	$serial = new phpSerial;

	$serial->deviceSet($SERIAL_DEVICE);
	$serial->confBaudRate($PROJECTOR_BAUD);
	$serial->confParity($PROJECTOR_PARITY);
	$serial->confCharacterLength($PROJECTOR_CHAR_BITS);
	$serial->confStopBits($PROJECTOR_STOP_BITS);
	$serial->deviceOpen();
	$DEVICE="SERIAL";
}

if(strtoupper($options["d"]) == "IP") {

	$cfgServer = $options["h"];

	$fs = fsockopen($cfgServer, $PORT, $errno, $errstr, $cfgTimeOut);

	if(!$fs) {
		logEntry("ERROR connecting to projector controller");// "Error connecting to projector controller";
	}
	$DEVICE="IP";


}

logEntry("Sending command: DEVICE: ".$DEVICE." COMMAND: ".$PROJECTOR_CMD);

switch ($DEVICE) {
	
	case "SERIAL":
		
		
	$serial->sendMessage("$PROJECTOR_CMD");
	sleep(1);
	$serial->deviceClose();
	exit(0);
	
	break;
	
	case "IP":
		
	fputs($fs,$PROJECTOR_CMD);
	sleep(1);
	fclose($fs);
	exit(0);
	break;
	
}
?>
