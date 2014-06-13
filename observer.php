<?php
	include_once(dirname(__FILE__)."/installer.inc.php");
	
	try{
		$str = LogVector::observe();
		echo $str;
		exit;	
	}catch(Exception $e){
		echo json_encode(array(
			"finished" => true
		));
		exit;
	}
	
	function getInstallcount(){
		$xml = simplexml_load_file(INSTALLER_DIRECTORY."/dat/info.xml");
		if($xml === false){
			return null;
		}
		
		return (string)$xml->files;		
		
	}
	
?>