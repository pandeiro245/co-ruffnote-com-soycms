<?php define("SOYCMS_VERSION","1.7.4");?><?php $selialized_functions = unserialize('a:8:{i:0;s:14:"apache_modules";i:1;s:12:"create_cache";i:2;s:8:"htaccess";i:3;s:17:"install_directory";i:4;s:14:"php_extensions";i:5;s:8:"php_inis";i:6;s:11:"php_version";i:7;s:11:"zip_archive";}');?><?php
//http://php.morva.net/manual/ja/function.sys-get-temp-dir.phpから参�?�
if(!function_exists("sys_get_temp_dir")){
	function sys_get_temp_dir()
    {
        // Try to get from environment variable
        if ( !empty($_ENV['TMP']) )
        {
            return realpath( $_ENV['TMP'] );
        }
        else if ( !empty($_ENV['TMPDIR']) )
        {
            return realpath( $_ENV['TMPDIR'] );
        }
        else if ( !empty($_ENV['TEMP']) )
        {
            return realpath( $_ENV['TEMP'] );
        }

        // Detect by creating a temporary file
        else
        {
            // Try to use system's temporary directory
            // as random name shouldn't exist
            $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
            if ( $temp_file )
            {
                $temp_dir = realpath( dirname($temp_file) );
                @unlink( $temp_file );
                return $temp_dir;
            }
            else
            {
                return FALSE;
            }
        }
    }
}

function sys_get_writable_temp_dir(){
    	static $dirname = null;
    	
    	if(is_null($dirname)){
    	 	$dirname = sys_get_temp_dir();
    	
	    	if(!$dirname || !is_writable($dirname)){
	    		//�?ンポラリ�?ィレクトリに書き込み権限がな�?と�?
	    		$dirname = INSTALLER_DIRECTORY."tmp";
	    		if(!file_exists($dirname)){
	    			mkdir($dirname);
	    		}
	    		
	    		if(!is_dir($dirname) || !is_writable($dirname)){
	    			$dirname = null;
	    			return false;
	    		}
	    	}
    	}
    	
    	return $dirname;
	    	
    }
?>
<?php
error_reporting(0);
ini_set("display_errors","0");
?>
<?php

class CheckRequirement{
	
	const FATAL = 4;
	const CAUTION = 3;
	const NOTICE = 2;
	const NOERROR = 1;
	
	private $name;
	private $description;
	private $result;
	
	private $message = array(
		self::FATAL=>array(),
		self::CAUTION=>array(),
		self::NOTICE=>array(),
		self::NOERROR=>array()
	);
	    		

	function getName() {
		return $this->name;
	}
	function setName($name) {
		$this->name = $name;
	}
	function getDescription() {
		return $this->description;
	}
	function setDescription($description) {
		$this->description = $description;
	}
	function getResult() {
		return $this->result;
	}
	function setResult($result) {
		$this->result = $result;
	}
	function getMessage() {
		return $this->message;
	}
	function addMessage($level,$message){
		if(is_array($message)){
			$this->message[$level] = array_merge($this->message[$level],$message);
		}else{
			$this->message[$level][] = $message;
		}
	}
	
	function getResultString($resultCode = null){
		if(is_null($resultCode)){
			$resultCode = $this->getResult();
		}
		switch($resultCode){
			case self::FATAL:
				return "FATAL";
			case self::CAUTION:
				return "CAUTION";
			case self::NOTICE:
				return "NOTICE";
			case self::NOERROR:
				return "OK";
			default:
				return "ERROR: インスト�?�ルエラー";
		}
	}
	
	function getResultStyle($resultCode = null){
		
		if(is_null($resultCode)){
			$resultCode = $this->getResult();
		}
		switch($resultCode){
			case self::FATAL:
				return "color:red;font-weight:bold";
			case self::CAUTION:
				return "color:red;";
			case self::NOTICE:
				return "color:blue";
			case self::NOERROR:
				return "color:black";
			default:
				return "ERROR: インスト�?�ルエラー";
		}
	}

	
}


	define('CLEAR_REQUEST_FILE',InstallLogic::getFairDirName(sys_get_temp_dir())."request_46456637");
	define('COUNT_FILE',InstallLogic::getFairDirName(sys_get_temp_dir())."count_425257373257");
	define('UNDER_INSTALLATION_FILE',InstallLogic::getFairDirName(sys_get_temp_dir())."flag_12456233454");
	define('UNDER_INSTALLATION_FLAG',file_exists(UNDER_INSTALLATION_FILE));
	
	class LogVector {
		private function LogVector(){}
		
		
		private $log_dat = array();
		private $progress_handle = null;
		
		private function getInstance(){
			static $instance = null;
			if(is_null($instance)){
				$instance = new LogVector();
			}
			return $instance;
		}
		
		
		public function push_back($action,$message){
			$str = sprintf("%6s    ",$action).$message;
			self::getInstance()->log_dat[] = $str;
			if($action == "cp"){
				self::updateCount();	
			}
			
		}
		
		public function getLogdata(){
			return self::getInstance()->log_dat;
		}
		
		public static function startObserving(){
			if(UNDER_INSTALLATION_FLAG){
				throw new Exception("インスト�?�ルはすでに実行されて�?ます�??");
			}
			@file_put_contents(UNDER_INSTALLATION_FILE,"");
			return true;
			
		}
		public static function updateCount($output = false){
			static $counter = 0;
			if($output){
				return @file_get_contents(COUNT_FILE);
			}else{
				$counter++;
				@file_put_contents(COUNT_FILE,(string)$counter);
			}
			
		}
		public static function observe(){
			return json_encode(array("number"=>self::updateCount(true),"finished"=>!UNDER_INSTALLATION_FLAG));
		}
		
		public static function finishOvserving(){
			$retry = 3;
			while(unlink(UNDER_INSTALLATION_FILE) === false){
				if($retry == 0){
					throw new Exception("オブザーブファイルの削除に失�?");
					break;
				}
				$retry --;
				sleep(1);
			}
			
			return true;
		}
	}
	
	

	class InstallLogic {
		
		/**
		 * SOYCMSのインスト�?�ルを行う
		 *
		 * @param string $src
		 * @param string $dst
		 * @return boolean
		 */
		public static function install($src,$dst,$param){
			$src = self::getFairDirName($src);
			$dst= self::getFairDirName($dst);
			
			
			$dst = self::getFairDirName($_SERVER["DOCUMENT_ROOT"]).$dst;
			
			//再帰�?にターゲ�?トディレクトリを作�?�す�?
			self::mkdir_r($dst);
			
			try{
				LogVector::startObserving();
				self::preInstallation($src,$dst,$param);
				self::copy_r($src,$dst,$param);
				self::endInstallation($src,$dst,$param);
				LogVector::finishOvserving();
				return true;
			}catch(Exception $e){
				//var_dump($e);
				//SOY2Debug::trace($e);
				LogVector::finishOvserving();
				return false;
			}
		}
		
		/**
		 * コピ�?�前�?�処�?
		 */
		private static function preInstallation($src,$dst,$param){
			
		}
		
		/**
		 * コピ�?�後�?�処�?
		 */
		private static function endInstallation($src,$dst,$param){
			self::makeMySQLConfigurationFile($src,$dst,$param);
			self::makeAccessDenyHtaccessFile($src,$dst,$param);
		}
		
		/**
		 * MySQLの設定ファイルを作る
		 */
		private static function makeMySQLConfigurationFile($src,$dst,$param){
			$mysql_config_path = self::getFairDirName(realpath($dst))."common/config/db/mysql.php";

			if(strlen($param->mysql_port)>0){
				$dsn = 'mysql:host='.$param->mysql_host.';port='.$param->mysql_port.';dbname=' .$param->mysql_dbname;
			}else{
				$dsn = 'mysql:host='.$param->mysql_host.';dbname=' .$param->mysql_dbname;
			}
			
			$config = array();
			$config[] = '<?php';
			$config[] = 'define("ADMIN_DB_DSN","'.$dsn.'");';
			$config[] = 'define("ADMIN_DB_PASS","'.$param->mysql_pass.'");';
			$config[] = 'define("ADMIN_DB_USER","'.$param->mysql_usr.'");';
			$config[] = 'define("ADMIN_DB_EXISTS",file_exists(SOY2::RootDir()."db/cms.db"));';
			$config[] = 'define("CMS_FILE_DB",ADMIN_DB_DSN);';
			$config[] = 'define("CMS_FILE_DB_EXISTS",file_exists(SOY2::RootDir()."db/file.db"));';
			$config[] = '?>';
			
			file_put_contents($mysql_config_path,implode("\n",$config));		
		}
		
		/**
		 * アクセス拒否の.htaccessを指定ディレクトリに保存す�?
		 * common
		 */
		private static function makeAccessDenyHtaccessFile($dst){
			$targetDirs = array(
				"common",
			);
			$dst = self::getFairDirName(realpath($dst));
			foreach($targetDirs as $dir){
				$file = $dst.$dir."/.htaccess";
				if(!file_exists($file)) file_put_contents($file, "Deny from All\n");
			}
		}
		
		/**
		 * アクセス可の.htaccessを指定ディレクトリに保存す�?
		 * プラグイン�?ィレクトリ
		 */
		private static function makeAccessAllowHtaccess($dst){
			$targetDirs = array(
				"common/site_include/plugin",
				"",
			);
			$dst = self::getFairDirName(realpath($dst));
			foreach($targetDirs as $dir){
				$file = $dst.$dir."/.htaccess";
				if(!file_exists($file)) file_put_contents($file, "Allow from All\n");
			}
		}
		
		/**
		 * インスト�?�ルログを取得す�?
		 *
		 * @return array
		 */
		public static function getLogData(){
			return LogVector::getLogdata();
		}
		
		/**
		 * 再帰ファイルコピ�?�
		 *
		 * @param string $src
		 * @param string $dst
		 */
		private static function copy_r($src,$dst,$param = null){
			//SOY2Debug::trace($src);
			if(!is_dir($src)){
				throw new Exception($src."は�?ィレクトリではありません�?");
			}
			
			$handle = opendir($src);
			
			if($handle === false){
				throw new Exception($src."の�?ィレクトリオープンに失�?");
			}
			
			while (false !== ($filename = readdir($handle))) {
				$complete_src_fname = realpath($src . $filename);
				$complete_dst_fname = self::getFairDirName(realpath($dst)) . $filename;
				
				if(is_dir($complete_src_fname)){
					if($filename[0] == '.') continue;
					
					if(!file_exists($complete_dst_fname)){
						//ターゲ�?トディレクトリがなかったら作�?�を試み�?
						if(@mkdir($complete_dst_fname) === false){
							throw new Exception($complete_dst_fname."�?ィレクトリの作�?�に失�?");
						}

						LogVector::push_back("mkdir",$complete_dst_fname);
						
					}else{
						if(!is_dir($complete_dst_fname)){
							throw new Exception($complete_dst_fname,"がすでに存在し�?�ディレクトリがありません�?");
						}
					}
					LogVector::push_back("cd",$complete_dst_fname);
					self::copy_r(self::getFairDirName($complete_src_fname),self::getFairDirName($complete_dst_fname));	
				}else{
					if(self::my_copy($complete_src_fname,$complete_dst_fname) === false){
						throw new Exception($complete_dst_fname."のコピ�?�に失�?");
					}
					LogVector::push_back("cp",$complete_dst_fname);
					
				}
				
				
			}
		}
		
		/**
		 * ユーザー定義ファイルコピ�?�関数
		 *
		 * @param string $src
		 * @param string $dst
		 * @return boolean
		 */
		private static function my_copy($src,$dst){
			return copy($src,$dst);
		}
		
		
		
		
		/**
		 * 再帰�?に�?ィレクトリを作�?�す�?
		 * 
		 * @param string $directory
		 * @return boolean
		 */
		public static function mkdir_r($directory,&$writefiles = array()){
			if(file_exists($directory)){
				return true;
			}
			
			$dir = str_replace("\\","/",$directory);
				
			$directories = preg_split('/\//',$dir);
			
			//上から検索して、ディレクトリが存在しな�?ところまで下がる�??
			$currentDir = "";
			while(count($directories) > 0){
				$currentDir .= array_shift($directories)."/";
					
				if(!file_exists($currentDir)){
					break;
				}
			}
			
			//�?後まで�?ィレクトリの作�?�を試みる[�?ィレクトリが少なくとも存在して�?な�?ことまでは保証されて�?る�?�で、�?回�?�mkdirは発行される]
			$writefiles = array();
			do{
				$result = @mkdir($currentDir);
				
				$writefiles[] = $currentDir;
				
				if($result === false){
					return false;
				}
				$currentDir .= array_shift($directories)."/";
			}while($directories > 0);
			
			return true;
			
		}
		
		/**
		 * 対象�?ィレクトリに競合しな�?ファイル名を取�?
		 *
		 * @param string $dirname
		 */
		public static function getUniqueFileName($dirname, $file_prefix = "soycms_installer"){
			$testfilename = self::getFairDirName($dirname).$file_prefix;
			
			while(file_exists($testfilename)){
				$testfilename .= mt_rand(10,99);
			}
			
			return $testfilename;
		}
		
		/**
		 * 整形されたディレクトリ名を取�?
		 *
		 * @param string $dirname
		 */
		public static function getFairDirName($dirname){
			
			$dirname = str_replace("\\","/",$dirname);
			
			if(substr($dirname,-1) != "/"){
				return $dirname."/";
			}else{
				return $dirname;
			}
		}
	}
	


	class InstallParameters{
		
		var $installDirectory = "cms/";
		var $licenseConfirm;
		var $error_msg;
		var $mysql_dbname;
		var $mysql_host;
		var $mysql_usr;
		var $mysql_pass;
		var $mysql_port;
		
		private $lastErrorMessage = "";
		
		function getLastErrorMessage(){
			return $this->lastErrorMessage;
		}
		
		private function setErrorMessage($errorMsg){
			$this->lastErrorMessage = $errorMsg;
		}
		
		
	}


abstract class InstallationStage {

    function InstallationStage() {
    }
    
    abstract function display();
    
    abstract function execute();
    
    private $active;
    var $installParam;
    
    function doPost(){}
    
    final function prepare(){
	    $this->installParam = @$_SESSION["installParameter"];
		
		if(!$this->installParam instanceof InstallParameters ){
			$this->installParam = new InstallParameters();
		}
    }
    
    final function dismiss(){
    	$_SESSION["installParameter"] = $this->installParam;
    }
        
    function setNextStage($next){
    	$this->setInstallationStage($next);
    }
    
    function redirect_me(){
    	$this->dismiss();
		$scheme = (isset($_SERVER["HTTPS"]) || defined("SOY2_HTTPS") && SOY2_HTTPS) ? "https" : "http";
		if( $_SERVER["SERVER_PORT"] == "80" && !isset($_SERVER["HTTPS"]) || $_SERVER["SERVER_PORT"] == "443" && isset($_SERVER["HTTPS"]) ){
			$port = "";
		}elseif(strlen($_SERVER["SERVER_PORT"]) > 0){
			$port = ":".$_SERVER["SERVER_PORT"];
		}else{
			$port = "";
		}
    	header("Location: $scheme://".$_SERVER["HTTP_HOST"].$port.$_SERVER["REQUEST_URI"]);
    	exit;
    }
    
    function jumpStage($next){
    	$this->setNextStage($next);
    	$this->redirect_me();
    }

    function getDBType(){
		return get_soycms_db_type();		
		
	}    
    
	static function getInstallationStage(){
		if(!isset($_SESSION["InstallationStage"])){
			return "HelloCMSStage";
		}else{
			return $_SESSION["InstallationStage"];
		}
	}
	
	static function setInstallationStage($stage){
		if(is_null($stage)){
			unset($_SESSION["InstallationStage"]);
		}else{
			$_SESSION["InstallationStage"] = $stage;
		}
	}
}


	/**
	 * @return $result	FATAL	:	インスト�?�ル続行不可能	次へボタンは表示しな�?
	 * 					CAUTION	:	インスト�?�ルは可能であるが�?��?部動作しな�?も�?�がある�??	次へボタンの前にConfirmで確認をと�?
	 * 					NOTICE	:	インスト�?�ル、動作ともに問題�?�な�?が�?�設定として非推奨なも�?�がある�??
	 * 					NOERROR	:	エラーなし�??
	 * @return $message	メ�?セージリス�?
	 */

	function apache_modules($obj,$setupParams){
		
		$obj->setName("Apache/mod_rewrtieモジュール");
		$obj->setDescription("SOY CMSの動作にはmod_rewriteモジュールが�?須です�??");
		
		if(function_exists("apache_get_modules")){
			
			if(in_array("mod_rewrite", apache_get_modules())){
				$obj->setResult(CheckRequirement::NOERROR);
				$obj->addMessage(CheckRequirement::NOERROR,array(
					"ご使用の環�?は、Apache/mod_rewriteモジュールが有効です�??"
				));
			}else{
				$obj->setResult(CheckRequirement::FATAL);
				$obj->addMessage(CheckRequirement::FATAL,array(
					"ご使用の環�?では、Apache/mod_rewriteモジュールが有効ではありません�?",
					"SOY CMSの動作にはmod_rewriteモジュールが�?須です�??"
				));
			}
			
		}else{
			
			if(strpos(php_sapi_name(),"cgi") !== false){
				$obj->setResult(CheckRequirement::NOTICE);
				$obj->addMessage(CheckRequirement::NOTICE,array(
					"CGIモード�?�場合�?�mod_rewriteモジュールの有効性が確認できません�?",
					"mod_rewriteが有効であることを確認して、インスト�?�ルを続行してください�?"
				));
				return;
			}else{
			
				$obj->setResult(CheckRequirement::FATAL);
				$obj->addMessage(CheckRequirement::FATAL,array(
					"ご使用の環�?は、HTTPサーバがApacheではな�?か�?�モジュール�?PHPではありません�?"
				));
				return;
			}
		}
		
	}


?>
<?php

	/**
	 * @return $result	FATAL	:	インスト�?�ル続行不可能	次へボタンは表示しな�?
	 * 					CAUTION	:	インスト�?�ルは可能であるが�?��?部動作しな�?も�?�がある�??	次へボタンの前にConfirmで確認をと�?
	 * 					NOTICE	:	インスト�?�ル、動作ともに問題�?�な�?が�?�設定として非推奨なも�?�がある�??
	 * 					NOERROR	:	エラーなし�??
	 * @return $message	メ�?セージリス�?
	 */

	function create_cache($obj,$setupParams){

		$obj->setName("キャ�?シュ�?ィレクトリの生�?�と、書き込み権�?");
		$obj->setDescription("SOY CMSはキャ�?シュ�?ィレクトリと、その�?ィレクトリへの書き込み権限を�?要とします�??");


		$instDir = InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]).$setupParams->installDirectory;

		$writefiles = array(
			"admin",
			//"common",
			"soycms",
			"app"
		);

		$result = true;

		foreach($writefiles as $folder){
			
			$parent_dir = $instDir.$folder;
			$cache_dir = "{$parent_dir}/cache";
			
			if(!file_exists($parent_dir)){ continue; }

			if(!file_exists($cache_dir)){
				if(mkdir($cache_dir, 0755, true)){
					$result = CheckRequirement::FATAL;
				}
			}

			$test_file = tempnam($cache_dir,"soycms_installer_");

			if(is_writable($cache_dir) && $test_file !== false && is_readable($test_file)){
				$result = CheckRequirement::NOERROR;
			}else{
				$result = CheckRequirement::CAUTION;
			}

		}

		switch($result){
			case CheckRequirement::NOERROR:
				$obj->setResult(CheckRequirement::NOERROR);

				$obj->addMessage(CheckRequirement::NOERROR,array(
					"キャ�?シュ�?ィレクトリ作�?�可能です�??"
				));
				break;
			case CheckRequirement::CAUTION:
			case CheckRequirement::NOTICE:
				$obj->setResult(CheckRequirement::NOTICE);

				$obj->addMessage(CheckRequirement::NOTICE,array(
					"SOY CMSは�?ルートディレクトリ直下に書き込み権限�?�ある�?ィレクトリcacheを�?要とします�??",
					"本環�?下ではSOY CMSインスト�?�ラはこ�?�作業を行えません�?",
					"書き込み権限�?�所有�??を変更して�?ただくか、この作業を手動で行ってください�?"
				));
				break;
			case CheckRequirement::FATAL:
				$obj->setResult(CheckRequirement::FATAL);

				$obj->addMessage(CheckRequirement::FATAL,array(
					"SOY CMSはインスト�?�ル�?ィレクトリへのアクセスに失敗しました�?",
					"書き込み権限などをご確認く�?さい"
				));

		}


	}


?>
<?php

	function htaccess($obj,$setupParams){

		$obj->setName(".htaccessとmod_rewriteとPATH_INFOの使用可否");
		$obj->setDescription("SOY CMSは.htaccessでmod_rewriteの設定とアクセス制御を行いま�?");

		if(!function_exists("apache_get_modules")){

			if(strpos(php_sapi_name(),"cgi")!==false){
				$obj->setResult(CheckRequirement::NOTICE);
				$obj->addMessage(CheckRequirement::NOTICE,array(
					"CGIモード�?�場合�?�htaccessの有効性が確認できません�?",
					"ドキュメントルート以下でhtaccessが有効であることを確認して、インスト�?�ルを続行してください�?"
				));
				return;
			}else{
				$obj->setResult(CheckRequirement::CAUTION);
				$obj->addMessage(CheckRequirement::CAUTION,array(
					"ご使用の環�?では、htaccessの有効性が確認できません�?",
					"ドキュメントルート以下でhtaccessが有効であることを確認して、インスト�?�ルを続行してください�?"
				));
				return;
			}
		}


		$parent_dir = InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]).$setupParams->installDirectory;

		$check = new CheckHtaccess($parent_dir);

		list($result, $message) = $check->prepare();
		if(!$result){
			$obj->setResult(CheckRequirement::CAUTION);
			$obj->addMessage(CheckRequirement::CAUTION,array(
				$message,
				"ドキュメントルート以下でhtaccessが有効であることを確認して、インスト�?�ルを続行してください�?"
			));
			return false;
		}

		list($result, $message) = $check->isDeniable();
		if(!$result){
			$obj->setResult(CheckRequirement::CAUTION);
			$obj->addMessage(CheckRequirement::CAUTION,array(
				$message,
				//"AllowOverRide Limitまた�?�Allにしてください�?"
			));
			return false;
		}

		list($result, $message) = $check->isPathInfoEnable();
		if(!$result){
			$obj->setResult(CheckRequirement::CAUTION);
			$obj->addMessage(CheckRequirement::CAUTION,array(
				$message,
			));
			if(strpos(php_sapi_name(),"cgi")!==false){
				$obj->addMessage(CheckRequirement::CAUTION,array(
					"?�?CGI版ではPATH_INFOが使えるかど�?かを判定できな�?ことがあります�?��?",
				));
			}
			return false;
		}

		list($result, $message) = $check->isRewriteEnabled();
		if(!$result){
			$obj->setResult(CheckRequirement::FATAL);
			$obj->addMessage(CheckRequirement::FATAL,array(
				$message,
				//"AllowOverRide FileInfo Optionsまた�?�All�?Options Indexes FollowSymLinksの設定を行ってください�?"
			));
			return false;
		}


		$obj->setResult(CheckRequirement::NOERROR);
		$obj->addMessage(CheckRequirement::NOERROR,array(
			"ご使用の環�?では�?.htaccessが有効で�?"
		));

		return true;
	}

	class CheckHtaccess{
		const DIRNAME  = "soycms_installer_test";
		const FILENAME = "check_htaccess.php";

		private $localdir;
		private $testfile;
		private $htaccess;

		function __construct($parent_dir){
			$this->localdir = InstallLogic::getUniqueFileName($parent_dir, self::DIRNAME);
			$this->testfile = $this->localdir."/".self::FILENAME;
			$this->htaccess = $this->localdir."/.htaccess";
			//mkdir($this->localdir);
		}
		function __destruct(){
			@unlink($this->testfile);
			@unlink($this->htaccess);
			@rmdir($this->localdir);
		}

		function prepare(){

			if(!file_exists($this->localdir)){
				//�?ンポラリ・�?ィレクトリ作�??
				if( false === @mkdir($this->localdir, 0755, true) ){
					return array(false, "ご使用の環�?では、ディレクトリの作�?�ができな�?ため、htaccessの有効性が確認できません�?");
				}
			}

			if(!file_exists($this->testfile)){
				//ファイル作�??
				if( false === @file_put_contents($this->testfile, "test") ){
					return array(false, "ご使用の環�?では、ファイルの書き込みができな�?ため、htaccessの有効性が確認できません�?");
				}
			}

			$url = createLinkFromRelativePath(str_replace(InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]), "/", $this->testfile),true);
			$response = @get_headers($url);
			if($response !== false AND count($response) > 0){
				if(strpos($response[0], "200") !== false){
					//OK
				}elseif(strpos($response[0], "403") !== false){
					return array(false, "ご使用の環�?では、インスト�?�ル�?ィレクトリへのアクセスが制限されて�?るため�?�設定を確認できません�?");
				}elseif(strpos($response[0], "401") !== false){
					return array(false, "ご使用の環�?では、インスト�?�ル�?ィレクトリにBasic/Digest認証がかかって�?るため�?�設定を確認できません�?");
				}

			}else{
				return array(false, "ご使用の環�?では、インスト�?�ラーからインターネットにアクセスできな�?ため、設定を確認できません�?");
			}


			//allowurlfopen
			if(!ini_get("allow_url_fopen")){
				return array(false, "ご使用の環�?では、allow_url_fopen==\"0\"のため、htaccessの有効性が確認できません�?");
			}

			return array(true,"");
		}

		function isRewriteEnabled(){
			if(!in_array("mod_rewrite",apache_get_modules())){
				return array(false, "Apacheのmod_rewriteモジュールが有効ではありません�?");
			}

			//
			$token = md5(mt_rand());
			@file_put_contents($this->testfile, $token);

			//htaccess作�??
			$script =
				"RewriteEngine on\n".
				'RewriteCond %{REQUEST_URI} !/'.self::FILENAME."\n".
				'RewriteRule ^.*$ '.self::FILENAME
				;
			@file_put_contents($this->htaccess,$script);

			$url = createLinkFromRelativePath(str_replace(InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]), "/", $this->localdir),true);
			$html = @file_get_contents($url);

			@unlink($this->htaccess);

			if($html === false || $html != $token){
				return array(false, "ご使用の環�?では、htaccessでRewrite設定が行えません�?");
			}else{
				return array(true, "OK");
			}

		}

		function isPathInfoEnable(){
			@unlink($this->htaccess);

			@file_put_contents($this->testfile, '<?php echo @$_SERVER["PATH_INFO"];');

			$url = createLinkFromRelativePath(str_replace(InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]), "/", $this->localdir),true);
			$url .= "/".basename($this->testfile);
			$pathinfo = "/".md5(mt_rand());

			$html = @file_get_contents($url.$pathinfo);

			if(strlen($html) ==0 OR $html != $pathinfo){
				return array(false, "ご使用の環�?では、PATH_INFOが取得できません�?");
			}else{
				return array(true, "OK");
			}

		}

		function isDeniable(){
			$token = md5(mt_rand());
			@file_put_contents($this->testfile, $token);

			//htaccess作�??
			$script =
				"Order Deny,Allow\n".
				"Deny from All\n"
				;
			@file_put_contents($this->htaccess,$script);

			$url = createLinkFromRelativePath(str_replace(InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]), "/", $this->localdir),true);
			$response = @get_headers($url);

			@unlink($this->htaccess);

			if($response !== false AND count($response) > 0 ){
				if(strpos($response[0], "403") !== false){//Access Forbidden
					return array(true, "OK");
				}elseif(strpos($response[0], "401") !== false){//Authorization Required
					/*
					 * Satisfy anyとBasic/Digest認証が有効になって�?る�?��?
					 */
					return array(true, "Basic/Digest認証が有効になって�?ます�??");
				}

			}else{
				return array(false, "ご使用の環�?では、htaccessでアクセス可否設定が行えません。セキュリ�?ィ上問題が発生する可能性があります�??");
			}

		}
	}




?>
<?php

	/**
	 * @return $result	FATAL	:	インスト�?�ル続行不可能	次へボタンは表示しな�?
	 * 					CAUTION	:	インスト�?�ルは可能であるが�?��?部動作しな�?も�?�がある�??	次へボタンの前にConfirmで確認をと�?
	 * 					NOTICE	:	インスト�?�ル、動作ともに問題�?�な�?が�?�設定として非推奨なも�?�がある�??
	 * 					NOERROR	:	エラーなし�??
	 * @return $message	メ�?セージリス�?
	 */

	function install_directory($obj,InstallParameters $setupParams){
		
		$obj->setName("インスト�?�ル�?ィレクトリへの書き込み権�?");
		$obj->setDescription("SOY CMSインスト�?�ラはインスト�?�ル�?ィレクトリへの書き込み権限を�?要とします�??");
		
		$installDir = InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]).$setupParams->installDirectory;
		
		list($result,$msg) = isWritableDirectory($installDir);
		
		if($result === false){
			$obj->setResult(CheckRequirement::FATAL);
			$obj->addMessage(CheckRequirement::FATAL,array(
				"�?定されたインスト�?�ル�?ィレクトリ[".$installDir."]に書き込み権限がな�?か�?�インスト�?�ル�?ィレクトリの�?定が不正です�??",
				"SOYCMSをインスト�?�ルするために書き込み権限が�?要です�??"
			));
		}else{
			$obj->setResult(CheckRequirement::NOERROR);
			$obj->addMessage(CheckRequirement::NOERROR,array(
				"�?定されたインスト�?�ル�?ィレクトリ[".$installDir."]にインスト�?�ル可能です�??"
				));
		}
		
	}
	
	/**
	 * �?定された�?ィレクトリが書き込み可能かど�?か判�?
	 *
	 * @return boolean
	 */
	function isWritableDirectory($installDirectory){

		if(is_null($installDirectory)){
			return array(false,"�?ィレクトリが指定されて�?ません�?");
		}
		
		$directory = InstallLogic::getFairDirName($installDirectory);

		if(!file_exists($directory)){
			if(false === @mkdir($directory, 0755, true)){
				return array(false,"�?定された�?ィレクトリを作�?�できません�?");
			}
		}

		if(!is_dir($directory)){
			return array(false,"対象がディレクトリではありません�?");
		}

		$result = @chmod($directory, 0755);
		if(!is_writable($directory) && $result === false){
			return array(false,"�?定された�?ィレクトリの書き込み権限がありません�?");
		}
		if(!is_executable($directory) && $result === false){
			return array(false,"�?定された�?ィレクトリ�?のファイルにアクセスできる権限がありません�?");
		}
		
		$testfname = InstallLogic::getUniqueFileName($directory, "soycms_installer_check_dir");

		if(false === @mkdir($testfname, 0755, true)){
			return array(false,"�?定された�?ィレクトリ�?に�?ィレクトリを作�?�できません�?");
		}
		if(false === @rmdir($testfname)){
			return array(false,"�?定された�?ィレクトリ�?に作�?�したディレクトリを削除できません�?");
		}

		if(false === @file_put_contents($testfname,"DUMMY_DATA")){
			return array(false,"�?定された�?ィレクトリ�?にファイルを作�?�できません�?");
		}
		if(false === @file_get_contents($testfname,"DUMMY_DATA")){
			return array(false,"�?定された�?ィレクトリ�?に作�?�したファイルを読み込めません�?");
		}
		if(false === @unlink($testfname)){
			return array(false,"�?定された�?ィレクトリ�?に作�?�したファイルを削除できません�?");
		}
		
		return array(true, "OK");
	}


?>
<?php

	/**
	 * @return $result	FATAL	:	インスト�?�ル続行不可能	次へボタンは表示しな�?
	 * 					CAUTION	:	インスト�?�ルは可能であるが�?��?部動作しな�?も�?�がある�??	次へボタンの前にConfirmで確認をと�?
	 * 					NOTICE	:	インスト�?�ル、動作ともに問題�?�な�?が�?�設定として非推奨なも�?�がある�??
	 * 					NOERROR	:	エラーなし�??
	 * @return $message	メ�?セージリス�?
	 */
	function php_extensions($obj,$setupParams){
		
		$obj->setName("PHPの外部モジュール");
		$obj->setDescription("SOY CMSはモジュールが�?要で�?");
		
		
		$require_extensions = array(
			"PDO",
			"mbstring",
			"SPL",
			"SimpleXML",
			"JSON",
		);
		
		if(get_soycms_db_type() == "mysql"){
			$require_extensions[] = "pdo_mysql";
		}else{
			$require_extensions[] = "pdo_sqlite";			
		}
		
		
		$loaded = array();
		$unloaded = array();
		
		foreach($require_extensions as $extension){
			if(extension_loaded($extension)){
				$loaded[] = $extension;	
			}else{
				$unloaded[] = $extension;
			}
		}
		
		if(count($unloaded) != 0){
			$obj->setResult(CheckRequirement::FATAL);
			$obj->addMessage(CheckRequirement::FATAL,array(
				"ご使用の環�?では、以下�?�モジュールが利用できな�?ためSOY CMSをご利用できません�?",
				"環�?をご確認く�?さい�?",
				implode(",",$unloaded),
				( in_array("JSON", $unloaded) ? "?�?JSONにつ�?てはPEARのServices_JSONのJSON.phpをcommon/lib/直下に置け�?�動作します�?��?" : "" )
				
			));	
		}else{
			$obj->setResult(CheckRequirement::NOERROR);
			$obj->addMessage(CheckRequirement::NOERROR,array(
				"ご使用の環�?は、以下�?�モジュールがロードされており、SOY CMSをご使用になれます�??",
				implode(",",$loaded)
			));
		}
			
	}
	
	function get_soycms_db_type(){
		$xml = simplexml_load_file(INSTALLER_DIRECTORY."/dat/info.xml");
		if($xml === false){
			return null;
		}
		
		return (string)$xml->dbtype;		
		
	}


?>
<?php

	/**
	 * @return $result	FATAL	:	インスト�?�ル続行不可能	次へボタンは表示しな�?
	 * 					CAUTION	:	インスト�?�ルは可能であるが�?��?部動作しな�?も�?�がある�??	次へボタンの前にConfirmで確認をと�?
	 * 					NOTICE	:	インスト�?�ル、動作ともに問題�?�な�?が�?�設定として非推奨なも�?�がある�??
	 * 					NOERROR	:	エラーなし�??
	 * @return $message	メ�?セージリス�?
	 */
	function php_inis($obj,$setupParams){
		
		$obj->setName("PHPの設�?");
		$obj->setDescription("SOY CMSに�?要なphp.iniの設定�?覧です�??");
		
		
		$require_values = array(
			"magic_quotes_gpc" => "Off"			
		);
		
		$valid = array();
		$invalid = array();
		$require = array();
		
		foreach($require_values as $key => $value){
			if($key == "magic_quotes_gpc"){
				
				if(get_magic_quotes_gpc() == 0){
					$valid[] = "{$key}={$value}";
				}else{
					$require[] = "{$key}={$value}";
					$invalid[] = "{$key}=On";
				}
			}else{
				if(ini_get($key) == $value){
					$valid[] = "{$key}={$value}";	
				}else{
					$require[] = "{$key}={$value}";
					$invalid[] = "{$key}=".ini_get($key);
				}
			}
		}
		
		if(count($invalid) != 0){
			$obj->setResult(CheckRequirement::NOTICE);
			$obj->addMessage(CheckRequirement::NOTICE,array(
				"ご使用の設定では、以下�?��?目が推奨環�?を�?たして�?ません�?",
				"こ�?�ままでも問題なく動作しますが、可能であれば設定を変更してください�?",
				"現在の設�?",
				implode(", ",$invalid),
				"推奨環�?",
				implode(", ",$require),
				
			));	
		}else{
			$obj->setResult(CheckRequirement::NOERROR);
			$obj->addMessage(CheckRequirement::NOERROR,array(
				"ご使用の環�?は、以下�?��?目が動作環�?に適合して�?ます�??",
				implode(", ",$valid)
			));
		}
			
	}
	
	

?>
<?php

	/**
	 * @return $result	FATAL	:	インスト�?�ル続行不可能	次へボタンは表示しな�?
	 * 					CAUTION	:	インスト�?�ルは可能であるが�?��?部動作しな�?も�?�がある�??	次へボタンの前にConfirmで確認をと�?
	 * 					NOTICE	:	インスト�?�ル、動作ともに問題�?�な�?が�?�設定として非推奨なも�?�がある�??
	 * 					NOERROR	:	エラーなし�??
	 * @return $message	メ�?セージリス�?
	 */

	function php_version($obj,$setupParams){
		
		$obj->setName("PHPのバ�?�ジョン�?報");
		$obj->setDescription("SOY CMSはPHPのバ�?�ジョン5.2.0以上�?5.2.2以上推奨)で動作します�??");
		
		$version = phpversion();
		//$version = "5.2.0";
		
		if(version_compare($version,"5.2.2",">=")){
			$obj->setResult(CheckRequirement::NOERROR);
			$obj->addMessage(CheckRequirement::NOERROR,array(
				"ご使用の環�?のPHPのバ�?�ジョンは".$version."です�??"
			));
		}else if(version_compare($version,"5.2.0",">=")){
			$obj->setResult(CheckRequirement::CAUTION);
			$obj->addMessage(CheckRequirement::CAUTION,array(
				"ご使用の環�?のPHPのバ�?�ジョンは".$version."です�??",
				"�?ンプレートパ�?ケージ管�?機�?�はPHPバ�?�ジョン5.2.2以降で動作します�??"
			));
		}else if(version_compare($version,"5.1.6",">=")){
			$obj->setResult(CheckRequirement::CAUTION);
			$obj->addMessage(CheckRequirement::CAUTION,array(
				"ご使用の環�?のPHPのバ�?�ジョンは".$version."です�??",
				"SOY CMSはバ�?�ジョン5.2.0以降でのみ動作します�?5.2.2以上推奨?���??",
				"?�?PEARのServices_JSONのJSON.phpをcommon/lib/直下に置け�?�動作します�?��?"
			));
		}else{
			$obj->setResult(CheckRequirement::FATAL);
			$obj->addMessage(CheckRequirement::FATAL,array(
				"ご使用の環�?のPHPのバ�?�ジョンは".$version."です�??",
				"SOY CMSはバ�?�ジョン5.2.0以降でのみ動作します�?5.2.2以上推奨?���??"
			));
		}
		
	}


?>
<?php

	function zip_archive($obj,$setupParams){
		
		$obj->setName("Zip/Archive設�?");
		$obj->setDescription("SOY CMSはペ�?�ジ雛形管�?にZIP/Archiveを使用しま�?");
		
		$zip = extension_loaded("zip");
		
		if($zip){
			$obj->setResult(CheckRequirement::NOERROR);
			$obj->addMessage(CheckRequirement::NOERROR,array(
				"ご使用の環�?では、ZIP/Archiveの使用が可能です�??"
			));
		}else{
			$obj->setResult(CheckRequirement::NOTICE);
			$obj->addMessage(CheckRequirement::NOTICE,array(
				"ご使用の環�?にZIP/Archive extensionはインスト�?�ルされておりません�?",
				"ZIP/Archiveの導�?�、また�?�PEAR::ZIPArchiveを導�?�してください�?",
				"?���?��?�ジ雛形管�?機�?�を使わな�?場合�?�無視しても問題ありません。�?"
			));
		}
		
	}



class ConfirmParametersStage extends InstallationStage{

    function ConfirmParametersStage() {
    }
    
    function display(){
    	?>
    	
    	<div id="install_config">
	    	<p>以下�?��?ィレクトリにSOY CMSをインスト�?�ルします�??</p>
	    	<p>変更する場合�?�、変更ボタンを押してください�?</p>
	    	
	    	<div id="install_params">
	    		<h4>インスト�?�ル先ディレクトリ</h4>
	    		<p><?php echo InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]).htmlspecialchars($this->installParam->installDirectory,ENT_QUOTES); ?></p>
	    	</div>
    	</div>
    	
    	<div class="navi">
	    	<input type="submit" value="前へ" name="back" id="prev_button"/>
	    	<input type="submit" value="変更" name="modify"/>
	    	<input type="submit" value="次へ" name="next" id="next_button"/>
    	</div>
		
		<p id="next_text">
		<?php
		if($this->getDBType() == "mysql"){
    		echo "次ではMySQLの設定を行います�??</p>";
		}else{
    		echo "次では動作環�?の確認を行います�??</p>";
		}
    	
    }
    
    function doPost(){
		if(isset($_POST["back"])){
			$this->jumpStage("LicenseConfirmStage");	
		}else if(isset($_POST["modify"])){
			$this->jumpStage("SetParameterStage");
		}else if(isset($_POST["next"])){
    		if($this->getDBType() == "mysql"){
				$this->jumpStage("MySQLSetupStage");	
			}else{
				$this->jumpStage("PreInstallationStage");
    		}
		}else{
			
		}	
    }
    
   
    
    function execute(){
    	if(is_null($this->installParam->installDirectory)){
    		$this->installParam->installDirectory = "";
    	}
    	
    }
}


class ConfirmStage extends InstallationStage{

    function ConfirmStage() {
    }
    
    function display(){
   		?>
インスト�?�ル確認�?��?�ジ

   		<?php
   		var_dump($this->getSessionValue("installParams"));
   		?>
   		
   		<div class="navi">
	   		<input type="submit" value="戻�?" name="back">
	   		<input type="submit" value="インスト�?�ル" name="install">
	   	</div>
   		
   		<?php
    }
    
    function doPost(){
		//TODO インスト�?�ルプロセス
		if(isset($_POST["back"])){
			$this->jumpStage("PreInstallationStage");
		}else if(isset($_POST["install"])){
			$this->jumpStage("EndStage");
		}
		
		//$this->jumpStage("EndStage");    	
    }
    
    function execute(){
    	
    }
}


class EndStage extends InstallationStage{

	private $logdata = null;
	
    function EndStage() {
    }
    
    function display(){
    	@file_put_contents(INSTALLER_DIRECTORY . "/installed",$this->installParam->installDirectory);
    	?>
インスト�?�ルが完�?しました�?<br>
<script type="text/javascript">
	function toggle_log(dom){
		var log = document.getElementById("install_log");
		
		if(log.style.display == "none"){
			log.style.display = "";
			dom.innerHTML = "ログを隠�?";
		}else{
			log.style.display = "none";
			dom.innerHTML = "ログを表示";
		}
	}
</script>

		<div class="navi">
			<button style="width:120px;" type="button" onclick="toggle_log(this);">ログを表示する</button>
			<input  style="width:120px;" type="submit" value="管�?�?ペ�?�ジへ">
			<br / ><br />
			<textarea style="display:none" id="install_log" readonly="readonly" wrap="off" class="install_log"><?php echo $this->logdata; ?></textarea>
		</div>
    	<?php
    }
    
    function doPost(){
    	$this->setNextStage(null);

		$scheme = (isset($_SERVER["HTTPS"]) || defined("SOY2_HTTPS") && SOY2_HTTPS) ? "https" : "http";
		if( $_SERVER["SERVER_PORT"] == "80" && !isset($_SERVER["HTTPS"]) || $_SERVER["SERVER_PORT"] == "443" && isset($_SERVER["HTTPS"]) ){
			$port = "";
		}elseif(strlen($_SERVER["SERVER_PORT"]) > 0){
			$port = ":".$_SERVER["SERVER_PORT"];
		}else{
			$port = "";
		}

    	header("Location: $scheme://".$_SERVER["HTTP_HOST"].$port."/".$this->installParam->installDirectory . "admin");
    	
    	exit;    	
    	//$this->redirect_me();
    }
    
    function execute(){
    	$this->logdata = file_get_contents(LOG_FILENAME);
    }
}


class HelloCMSStage extends InstallationStage{
	
	var $installed = false;
	
    function HelloCMSStage() {
    }
    
    function display(){
    	
    	$this->installed = file_exists(dirname(__FILE__)."/installed");
    	
    	if(!$this->installed){
    	
	    	?>
			SOY CMSインスト�?�ルペ�?�ジへようこそ<br>
			こ�?�ウィザード�?�SOY CMS ver.<?php echo SOYCMS_VERSION;?>のインスト�?�ルのガイドをして�?きます�??<br>
			セ�?トア�?プを開始する前に、以下�?�動作環�?を今�?度ご確認く�?さい�?<br>
			?��後�?�ス�?�?プで動作環�?のチェ�?クをします�?��?<br>
			<?php
				$require = file_get_contents(INSTALLER_DIRECTORY."/dat/require");
			?>
			<div id="requirment">
				<h3>SOYCMS動作環�?</h3>
				<div><?php echo $require; ?></div>
			</div>
			
			<div class="navi">
	    		<input type="submit" value="次へ" id="next_button" />
	    	</div>
	    	<?php
	    
    	}else{
    		
    		$path = file_get_contents(dirname(__FILE__)."/installed");
			$this->installParam->installDirectory = $path;    		
    		
    		?>
			SOY CMSインスト�?�ルペ�?�ジへようこそ<br>
			既にSOY CMSはインスト�?�ル済みです�??<br><br>
			再度インスト�?�ルを行う場合�?�、[次へ]よりウィザードを開始して下さ�?�?<br>
			インスト�?�ル先へ移動する�?�合�?�、[確認]を押してください�?
			
			<div id="install_params">
	    		<h4>インスト�?�ル先ディレクトリ</h4>
	    		<p><?php echo InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]).htmlspecialchars($this->installParam->installDirectory,ENT_QUOTES); ?></p>
	    		
	    		<h4>インスト�?�ル先へ移動す�?</h4>
	    		<p><a href="/<?php echo htmlspecialchars($this->installParam->installDirectory,ENT_QUOTES); ?>">確�?</a></p>
    		</div>
    		
    		<div class="navi">
	    		<input type="submit" id="next_button" value="次へ" />
	    	</div>
	    	
	    	<?php
    			
    	}
    }
    
    function doPost(){
    	$this->jumpStage("LicenseConfirmStage");
    }
    
    function execute(){
    	
    }
}

	class InstallStage extends InstallationStage{
		
		function display(){
			?>
<script type="text/javascript">

function notify(value){
	document.getElementById("current").innerHTML = value;	
}

var timer = null;
var current_max = 0;
var current_progress = 0;
var progress_timer = null;
var log_timer = null;
var message_stock = $A([]);
var maxcount = <?php echo (int)$this->getInstallcount();?>;

function startCheckCurrent(){
	
	var url = "<?php echo htmlspecialchars(createLinkFromRelativePath("observer.php"), ENT_QUOTES, "UTF-8"); ?>";
	
	$("message").innerHTML = "現在インスト�?�ル中です�?�しばらくお�?ちください�?";
	
	new Ajax.Request(url, {
		method: "post",
		parameters : "",
		onComplete: function(req){
			try{
				var resObj = eval("("+req.responseText+")");
			}catch(e){
				alert(req.responseText);
			}
			
			if(resObj.finished){
				resObj.number = maxcount;
			}
			current_max = resObj.number;
			
			if(timer){
				clearTimeout(timer);
			}
			if(!resObj.finished){
				timer = setTimeout(startCheckCurrent,500);
			}			
		}
	});
	
	drawProgressBar();
}


function drawProgressBar(){
			
	if(current_progress < current_max){
		current_progress += parseInt((maxcount)*0.01*Math.random());
		current_progress = Math.min(current_progress,current_max);
		$("bar").style.width = Math.min((current_progress/maxcount)*100,100)+"%";
		$("bar").innerHTML = parseInt((current_progress/maxcount)*100)+"%";
			
	}
	
	if(current_progress < maxcount){
		progress_timer = setTimeout(drawProgressBar,50);
	}else{
		$("bar").style.width = "100%";
		$("bar").innerHTML = "100%";
		
		Event.stopObserving(window, 'beforeunload', alertProcessing,false);
		
		$("message").innerHTML = "インスト�?�ルが終�?しました�?";
		//$("main_progress").show();
		$("main_progress").removeAttribute("disabled");
		$("main_progress").innerHTML = "次へ";
		$("main_progress").onclick = function(){
			location.reload();
			return;					
		}
	}
	
}


function postIframe(){
	
	Event.observe(window, 'beforeunload', alertProcessing);
	$("main_progress").setAttribute("disabled","disabled");
	//$("main_progress").hide();
	$("main_progress").innerHTML = "インスト�?�ル中";
	
	var url = "<?php echo htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES, "UTF-8") ?>";
	
	new Ajax.Request(url, {
		method: "post",
		parameters : "",
		onComplete: function(req){}
	});
	
	timer = setTimeout(startCheckCurrent,500);
}

function alertProcessing(e){
	if(!e)e=event;
	return e.returnValue = '現在インスト�?�ル中です�??';
}
</script>

<div id="install">
	<p id="message"></p>
	<div id="progress">
		<div id="bar"></div>
	</div>
</div>

<form id="form1" action="<?php echo $_SERVER["REQUEST_URI"] ?>">
	<div class="navi">
		<button type="submit" name="prev" id="prev_button">戻�?</button>
		<button style="width:200px;" type="button" id="main_progress" onclick="postIframe();">インスト�?�ルを開始す�?</button>
	</div>
</form>
			
			<?php
		}
		function doPost(){
			if(isset($_POST["prev"])){
				if(get_soycms_db_type() == "mysql"){
					$this->jumpStage("MySQLSetupStage");
				}else{
					$this->jumpStage("PreInstallationStage");
				}
			}
			//do nothing
			set_time_limit(0);
			InstallLogic::install(INSTALL_ROOT,$this->installParam->installDirectory,$this->installParam);
					
			file_put_contents(LOG_FILENAME,implode("\n",InstallLogic::getLogData()));
			$this->jumpStage("EndStage");
		}
		function execute(){
		}
		
		function getInstallcount(){
			$xml = simplexml_load_file(INSTALLER_DIRECTORY."/dat/info.xml");
			if($xml === false){
				return null;
			}
			
			return (string)$xml->files;		
			
		}
	}


class LicenseConfirmStage extends InstallationStage{

    function LicenseConfirmStage() {
    }
    
    function display(){
    	$license = htmlspecialchars(file_get_contents(INSTALLER_DIRECTORY."/dat/License"),ENT_QUOTES);
    	?>
    	<script type="text/javascript">
    		function onRadioClick(obj){
    			if(obj.id == "id_radio_accept" && obj.checked){
    				document.getElementById("next_button").removeAttribute("disabled");
    			}else{
    				document.getElementById("next_button").setAttribute("disabled","disabled");
    			}
    		}
    	</script>
    	<div id="license">
	    	<h3>ライセンスの確�?</h3>
	    	<div class="license_wrapper">
	    	<pre><?php echo $license; ?></pre>
	    	</div>

			<div style="width:100%; text-align:right; margin-top: 10px;">
			<input type="checkbox" name="license_confirm" value="accept" id="id_radio_accept" onclick="onRadioClick(this)"><label for="id_radio_accept"> 上記�??容を確認しました�?</label>
			</div>
    	</div>    	
    		
    		
		<div class="navi">
    		<input type="submit" value="前へ" name="back" id="prev_button">
    		<input type="submit" value="次へ" name="next" id="next_button" disabled="disabled">
    	</div>
	    	
    	<?php    	
    }
    
    function doPost(){
    	if($_POST["next"]){
    		
    		if($_POST["license_confirm"] == "accept"){
    			
    			$this->installParam->licenseConfirm = true;
    			
    			$this->jumpStage("ConfirmParametersStage");
    		}else{
    			
    			$this->installParam->licenseConfirm = false;
    			
    			$this->redirect_me();
    		}
    		
    		
    	}else if($_POST["back"]){
    		$this->jumpStage("HelloCMSStage");
    	}else{
    		
    	}
    }
    
    function execute(){
    	
    }
}


class MySQLSetupStage extends InstallationStage{

    function MySQLSetupStage() {
    }
    
    function display(){
    	$params = $this->installParam;
    	if(is_null($params->mysql_host)){
    		$params->mysql_host = "localhost";
    	}
    	
    	?>
    <div id="mysql_configuratoin">
		<h3>MySQL設�?</h3>
		<?php if(strlen($params->error_msg)>0 )echo "<div id='mysql_conf_alert'>".$params->error_msg."</div>"; ?>
	  	<table>
	  		<tr>
	  			<th>ホスト名</th>
	  			<td><input type="text" name="host" value="<?php echo (strlen($params->mysql_host)>0) ? $params->mysql_host : "localhost" ; ?>"></td>
	  		</tr>
	  		<tr>
	  			<th>ポ�?�ト番号?��空�?可?�?</th>
	  			<td><input type="text" name="port" value="<?php echo $params->mysql_port; ?>"></td>
	  		</tr>
	  		<tr>
	  			<th>ユーザー�?</th>
	  			<td><input type="text" name="user" value="<?php echo $params->mysql_usr; ?>"></td>
	  		</tr>
	  		<tr>
	  			<th>パスワー�?</th>
	  			<td><input type="password" name="password" value="<?php echo $params->mysql_pass; ?>"></td>
	  		</tr>
	  		<tr>
	  			<th>�?ータベ�?�ス�?</th>
	  			<td><input type="text" name="dbname" value="<?php echo $params->mysql_dbname; ?>"></td>
	  		</tr>
	  	</table>
	  </div>
  		
	<div class="navi">
		<input type="submit" value="前へ" name="prev" id="prev_button">
		<input type="submit" value="次へ" name="next" id="next_button">
	</div>
 
	<p id="next_text">次では動作環�?の確認を行います�??</p>
	  
    	<?php
    }
    
    function doPost(){
		
		if(isset($_POST["prev"])){
			$this->jumpStage("ConfirmParametersStage");
		}else if(isset($_POST["next"])){
			
			$host = @$_POST["host"];
			$port = @$_POST["port"];
			$dbname = @$_POST["dbname"];
			$user = @$_POST["user"];
			$pass = @$_POST["password"];
			
			if(strlen($port)>0){
				$dsn = 'mysql:host='.$host.';port='.$port.';dbname=' .$dbname;
			}else{
				$dsn = 'mysql:host='.$host.';dbname=' .$dbname;
			}
			
			$this->installParam->mysql_dbname = $dbname;
			$this->installParam->mysql_host = $host;
			$this->installParam->mysql_usr = $user;
			$this->installParam->mysql_pass = $pass;
			$this->installParam->mysql_port = $port;

			try{
				$pdo = new PDO($dsn,$user,$pass,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
				
				
				if($pdo){
					$pdo->exec("create table soycms_test(id integer);");
					$pdo->exec("drop table soycms_test");
				}else{
					//失�?
				}
				
			}catch(Exception $e){
				$this->installParam->error_msg = "�?ーターベ�?�スへの接続に失敗しました。設定を確認して下さ�?。�??" .
						"<a href=\"#\" onclick=\"document.getElementById('errro_detail').style.display='';this.style.displaye='none';\">詳細</a>" .
						"<p id=\"errro_detail\" style=\"display:none;\">".$e->getMessage()."</p>";

				$this->jumpStage("MySQLSetupStage");
			}
			
			$this->installParam->error_msg = "";
			$this->jumpStage("PreInstallationStage");

		}			
		
		
		
    }
    
    function execute(){
    	
    }
}




class PreInstallationStage extends InstallationStage{

	private $require_results = array();
	
    function PreInstallation() {
    }
    
    function display(){
 		?>
 		<script type="text/javascript">
 			function ToggleDetail(dom,target){
 				if(dom.getAttribute("class") == "requrie_name_free"){
 					dom.setAttribute("class","requrie_name_select");
 					document.getElementById(target).style.display = "";
 				}else{
 					dom.setAttribute("class","requrie_name_free");
 					document.getElementById(target).style.display = "none";
 				}
 			}
 		</script>
 		<div id="check_environment">
	 		<p>ここでは、ご使用の環�?がSOY CMSの動作環�?を�?たして�?るかを確認します�??</p>
		   	<p>以下�?�結果より、赤字があった�?�合�?�、設定を見直してください�?</p>
		   	<p>特に太字�?�赤字�?�SOY CMSの動作上で致命�?となる部�?であるため、�?ずご確認く�?さい�?</p>
		   	<p>青文字�?��?目は、動作上問題�?�ありませんが�?�推奨する環�?ではありません�?</p>
		   	
	 		<?php
		    $total_result = CheckRequirement::NOERROR;
		    foreach($this->require_results as $result){
		    	$total_result = max($result->getResult(),$total_result);
		    	$dom_id = "detail_id_".md5($result->getName());
		    	
		    	if($result->getResult() >= CheckRequirement::CAUTION){
		    		$classname = "requrie_name_select";
		    	}else{
		    		$classname = "requrie_name_free";
		    	}
		    	
		   	?>
		   	
		   	
	 		<div class="requirement">
		 		<h3 class="<?php echo $classname; ?>" style="<?php echo $result->getResultStyle(); ?>" onclick="ToggleDetail(this,'<?php echo htmlspecialchars($dom_id,ENT_QUOTES); ?>');"><?php echo $result->getName(); ?>・・・<?php echo $result->getResultString(); ?></h3>
		 		<div class="reason" id="<?php echo htmlspecialchars($dom_id,ENT_QUOTES); ?>" style="<?php if($result->getResult() < CheckRequirement::CAUTION) echo "display:none";?>">
		 		<?php 
		    		foreach($result->getMessage() as $level => $messages){
		    			foreach($messages as $message){
		    	?>
		    		<?php echo $message."<br>"; ?>
		    	<?php			
		    			}
		    		}
		    	?>
		 		</div>
		 		
	 		</div>
	 		<?php
		    	
		    }
		    ?>
	    </div>
	    
	    <div class="navi">
	    <input type="submit" value="前へ" name="back" id="prev_button" >
	    <?php
	    	switch($total_result){
	    		case CheckRequirement::FATAL:
	    			?>
	    			<input type="submit" id="to_confirm" name="next" style="width:200px;" value="警告を無視してインスト�?�ル" onclick="return confirm('ご使用の環�?ではSOY CMSが動作しません�?\nかまわずインスト�?�ルを続行しますか?�?');">
	    			<?php
	    			break;
	    		case CheckRequirement::CAUTION:
	    			?>
	    			<input type="submit" id="to_confirm" name="next" value="インスト�?�ル" onclick="return confirm('ご使用の環�?では�?部動作しな�?�?目があります�??\nインスト�?�ルを続行しますか?�?');">
	    			<?php
	    			break;
	    		case CheckRequirement::NOTICE:
	    			?>
	    			<input type="submit" id="to_confirm" name="next" value="インスト�?�ル" onclick="return confirm('推奨環�?を�?たして�?ませんがインスト�?�ルを続行しますか?�?');">
	    			<?php
	    			break;
	    		case CheckRequirement::NOERROR:
	    			?>
	    			<input type="submit" id="to_confirm" name="next" value="インスト�?�ル">
	    			<?php
	    			break;
	    		default:
	    			break;
	    	}
			
		?>
		</div>

    	<p id="next_text">次の画面のボタンを押すまではインスト�?�ルは実行されません�?</p>

		<?php
    }
    
    function doPost(){
    	if(isset($_POST["next"])){
			$this->jumpStage("InstallStage");
    	}else if(isset($_POST["back"])){
    		$this->jumpStage("ConfirmParametersStage");
    	}
    	exit;
    }
    
    function execute(){
    	
    	global $selialized_functions;
    	foreach($selialized_functions as $functions){
    		$this->check_require($functions);        	
		}
    }
    
    function check_require($function_name){
    	
    	if(!function_exists($function_name)){
    		return false;
    	}
    	
    	$check = new CheckRequirement();
    	$function_name($check,$this->installParam);
    	
    	$this->require_results[$function_name] = $check;
    	
    	return true;
    	
    }
}


class SetParameterStage extends InstallationStage{

	function SetParameterStage() {
	}
	
	function display(){
		$params = $this->installParam;
		
		?>

	<script type="text/javascript">
		function onChangeInput(form){
			
			var buf = form.value;
			
			if(buf.indexOf("..") != -1){
				while(buf.indexOf("..") != -1){
					buf = buf.replace("..",".");
				}
				
				form.value = buf;	
			}	
			
			document.getElementById("install_path").innerHTML = buf;
			return false;
		}
	</script>		
    	<div id="install_config">
	    	<div id="install_params">
	    		<h4>インスト�?�ル先ディレクトリ</h4>
  	<p style="">ドキュメントルー�?/<input type="text" id="dir_input" style="width:320px" onkeyup="onChangeInput(this)" name="install_directory" value="<?php if(is_string($params->installDirectory) && strlen($params->installDirectory) != 0){ echo $params->installDirectory;}else{echo "";} ?>"/></p>
  	<p style="">[<?php echo InstallLogic::getFairDirName($_SERVER["DOCUMENT_ROOT"]); ?><span id="install_path"><?php if(is_string($params->installDirectory) && strlen($params->installDirectory) != 0){ echo $params->installDirectory;}else{echo "";}?></span>]</p>
	    	</div>
    	</div>
  	
  	<div class="navi">
		<input type="submit" value="キャンセル" name="cancel" id="prev_button"/>
		<input type="submit" value="設�?" name="apply" id="next_button"/>
	</div>
	 
		<?php
	}
	
	function doPost(){
		//TODO validate
		
		if(isset($_POST["apply"])){
			$this->installParam->installDirectory = str_replace("..",".",$_POST["install_directory"]);
			if(substr($this->installParam->installDirectory,-1) != '/'){
				$this->installParam->installDirectory .= '/';
			}
		}
		
		$this->jumpStage("ConfirmParametersStage");
	}
	
	function execute(){
		
	}
}
?>