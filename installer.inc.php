<?php
	define('INSTALLER_DIRECTORY',dirname(__FILE__));
	define('INSTALL_ROOT',dirname(__FILE__)."/bin");
	
	define('LOG_FILENAME',INSTALLER_DIRECTORY."/log/install.log");
	
	//define('DEBUG',true);
	
	if(defined("DEBUG") OR !is_readable(dirname(__FILE__)."/installer.build.inc.php")){
		//開発用の環境を作成、ビルド版ならいらない
		function include_r($dir){
			if(is_dir($dir)){
				$files = scandir($dir);
				foreach($files as $file){
					if($file[0] == '.') continue;
					include_r($dir."/".$file);					
				}
			}else{
				include_once($dir);
			}
		}
		$_files = scandir(INSTALLER_DIRECTORY."/develop/requires");
		$files = array();
		foreach($_files as $file){
			if($file[0] == ".") continue;
			$files[] = str_replace(".php","",$file);
		}
		$selialized_functions = unserialize(serialize($files));
		include_r(INSTALLER_DIRECTORY."/develop");
	}else{
		include_once(dirname(__FILE__)."/installer.build.inc.php");
	}
	
	function createLinkFromRelativePath($path, $isAbsoluteUrl = false){
		//schema
		$schema = (isset($_SERVER["HTTPS"]) || defined("SOY2_HTTPS") && SOY2_HTTPS) ? "https" : "http";
		//port
		if( $_SERVER["SERVER_PORT"] == "80" && !isset($_SERVER["HTTPS"]) || $_SERVER["SERVER_PORT"] == "443" && isset($_SERVER["HTTPS"]) ){
			$port = "";
		}elseif(strlen($_SERVER["SERVER_PORT"]) > 0){
			$port = ":".$_SERVER["SERVER_PORT"];
		}else{
			$port = "";
		}
		
		
		//絶対URL
		if(preg_match("/^https?:/",$path)){
			return $path;
		}
		
		//絶対パス
		if(preg_match("/^\//",$path)){
			if($isAbsoluteUrl){
				return $schema . "://".$_SERVER["SERVER_NAME"] .$port. $path;
			}else{
				return $path;
			}
		}
		
		//ドキュメントルート（またはSOY2_DOCUMENT_ROOT）からみたスクリプトの相対パス
		$documentRoot = (defined("SOY2_DOCUMENT_ROOT")) ? SOY2_DOCUMENT_ROOT : $_SERVER["DOCUMENT_ROOT"];
		$documentRoot = str_replace("\\","/",$documentRoot);
		if(strlen($documentRoot) >0 && $documentRoot[strlen($documentRoot)-1] != "/") $documentRoot .= "/";
		
		$script = str_replace("\\","/",$_SERVER["SCRIPT_FILENAME"]);
		$script = str_replace($documentRoot, "/", $script);
		
		//パスを配列にする
		$currentScript = explode("/", $script);
		
		//先頭が空っぽなら捨てておこう
		if($currentScript[0] == "")array_shift($currentScript);
		
		//./の省略は補います。
		if(preg_match("/^[^\.]/",$path)){
			$path = "./".$path;
		}
		
		$paths = explode("/",$path);
		$pathStack = array();
		
		foreach($paths as $path){
			
			if($path == ".."){
				array_pop($currentScript);
				array_pop($currentScript);
				continue;
			}
			
			if($path == "."){
				array_pop($currentScript);
				continue;	
			}
			
			array_push($pathStack,$path);
			
		}
		
		$url = implode("/",array_merge($currentScript,$pathStack));
		
		if($isAbsoluteUrl){
			return $schema . "://".$_SERVER["SERVER_NAME"] .$port."/" .$url;
		}else{
			return "/" .$url;
		}
	}
	
	class SOY2Debug {
	public static function trace(){
		
		
		$args = func_get_args();
		$socket = @fsockopen(self::host(),self::port(), $errno, $errstr,1);
		
		if(!$socket){
			return;
		}
		
		//trace
		$trace = debug_backtrace();
		
		fwrite($socket,"File:".$trace[0]["file"]."(".$trace[0]["line"].")"."\n");
		fwrite($socket,"----------------------------------------------------\n");
				
		foreach($args as $var){
			fwrite($socket,var_export($var,true));
		}
		
		fclose($socket);
		
	}
	
	public static function port($port = null){
		
		static $_port;
		
		if(is_null($_port)){
			$_port = 9999;
		}
		
		if($port){
			$_port = (int)$port;
		}
		
		return $_port;		
	}
	
	public static function host($host = null){
		
		static $_host;
		
		if(is_null($_host)){
			$_host = "127.0.0.1";
		}
		
		if($host){
			$_host = $host;
		}
		
		return $_host;
		
	}
}


?>