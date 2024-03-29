<?php
/*
 * SOYCMS Extension Configure
 */

if(!defined("EXT_MODE_DERECTORY_NAME")) define("EXT_MODE_DERECTORY_NAME", "extmock");

$isExtMode = (isset($_COOKIE["soycms_ext"])) ? $_COOKIE["soycms_ext"] : 0;

if(isset($_GET["ext_mode"])){
	$isExtMode = ($isExtMode) ? 0 : 1;
	setcookie("soycms_ext", $isExtMode, time() + 7*24*60*60, "/");

	SOY2PageController::jump("");
	exit;
}

//ログインしているかどうか？
$isLoggined = UserInfoUtil::isLoggined();
$isExtLink = true;

//ログインしている場合
if($isLoggined === true){
	
	//ディフォルトユーザの場合はクッキーの情報を見る
	if(UserInfoUtil::isDefaultUser()){
		//$isExtModeの値はそのまま
		
	//ディフォルトユーザ以外の場合
	}else{
		//デフォルトユーザ以外は常にextmode
		$isExtMode = 1;
		$isExtLink = false;
	}
	
}else{
	$isExtMode = 0;
	$isExtLink = false;
}


if($isExtMode){
	SOY2HTMLConfig::PageDir(dirname(__FILE__) . "/" . EXT_MODE_DERECTORY_NAME . "/");
	define("SOY2HTML_AUTO_GENERATE", true);

	/**
	 * 外部JSファイルの読み込みを追加する
	 */
	HTMLHead::addScript("ext.js", array(
		"src" => SOY2PageController::createRelativeLink("./js/ext.js") . "?" . SOYCMS_BUILD_TIME
	));

	/**
	 * 外部CSSファイルの読み込みを追加する
	 */
	HTMLHead::addLink("ext",array(
		"rel" => "stylesheet",
		"type" => "text/css",
		"href" => SOY2PageController::createRelativeLink("./css/ext.css")."?".SOYCMS_BUILD_TIME
	));
	

	if(!isset($_GET["updated"])){
		DisplayPlugin::hide("updated");
	}

}else{
	//
}

//extmodeのリンクを表示させるか？
if($isExtLink === true){
	//表示させる
}else{
	DisplayPlugin::hide("ext_mode_link");
}
?>