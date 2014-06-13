<?php

	if(version_compare(PHP_VERSION, '5.0.0', '<')){
		echo "<pre>";
		echo "You are running PHP 4. SOY CMS runs only on PHP 5. The minimum requirement is 5.2.0. PHP 5.2.2 or higher is recommended.\n";
		echo "ご利用のサーバーはPHP 4で動いています。SOY CMSはPHP 5のサーバーでのみ動作します（5.2.0以降必須、5.2.2以降推奨）。";
		echo "</pre>";
		exit;
	}else{
		include_once(dirname(__FILE__)."/installer.inc.php");
	}
	
	session_start();
	
	$stage = InstallationStage::getInstallationStage();
	
	if(class_exists($stage)){
		$stage_obj = new $stage();
	}else{
		$stage_fname = dirname(__FILE__)."/stage/".$stage.".class.php";
		
		if(file_exists($stage_fname)){
			include_once($stage_fname);
			$stage_obj = new $stage();
		}else{
			echo "<pre>";
			echo "インストールパッケージが欠落しています。\n";
			echo "</pre>";
			$_SESSION = array();
			exit;
		}
	}
	
	$stage_obj->prepare();
	
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		$stage_obj->doPost();
	}
	
	$stage_obj->execute();
	//css
	$css_path = createLinkFromRelativePath("./css/style.css");
	
	//prototypejs
	$js_path = createLinkFromRelativePath("./prototype.js");
	
?>
<html>

<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="<?php echo $css_path; ?>" />
<script type="text/javascript" src="<?php echo $js_path; ?>"></script>
<title>SOY CMS インストール</title>
</head>
<body>

<div id="wrapper">
	<div id="upperMenu">
		<div id="logo"><!----></div>
		
		<br style="clear:both;" />
	</div>
	
	<div id="content">
			<h2>SOY CMS インストール</h2>
			
			<div class="info">
			<?php
				display_active_box($stage_obj);
			?>
			</div>
			
				<form method="POST" action="<?php echo $_SERVER["REQUEST_URI"] ?>">				
			<?php
				$stage_obj->display();
			?>
				</form>
	</div>
	
	<div id="footer">
		<div id="footer_left"></div>
		<div id="footer_right"></div>
		<div id="copyright">Copyright &copy; <?php echo date("Y", filemtime(__FILE__)); ?> Nippon Institute of Agroinformatics Ltd. All rights reserved.</div>
	</div>
	
</div>
</body>
</html><?php
	$stage_obj->dismiss();
	

function display_active_box($stage_obj){
	
	$array = array(
		array(
			"msg"=>"はじめに",
			"obj"=>array("HelloCMSStage")
		),
		array(
			"msg"=>"ライセンスの確認",
			"obj"=>array("LicenseConfirmStage")
		),
		array(
			"msg"=>"インストール先の決定",
			"obj"=>array("ConfirmParametersStage","SetParameterStage,MySQLSetupStage")
		),
		array(
			"msg"=>"動作環境の確認",
			"obj"=>array("PreInstallationStage")
		),
		array(
			"msg"=>"実行",
			"obj"=>array("InstallStage")
		),
		array(
			"msg"=>"完了",
			"obj"=>array("EndStage")
		)
	);
	
	for($i = 0;$i < count($array); $i++){
		
		if(in_array(get_class($stage_obj),$array[$i]["obj"])){
			$class = "box active";
		}else{
			$class = "box";
		}
		
		?>
		<div class="<?php echo $class; ?>">
		<h3>STEP-<?php echo ($i+1);?></h3>
		<p>
		<?php
			echo $array[$i]["msg"];
		?>
		</p>
		</div>
		<?php
		
		if($i != (count($array)-1)){
			echo '<div class="arrow">&rarr;</div>';
		}else{
			echo '<br style="clear:both;"/>';
		}
	}

}

?>