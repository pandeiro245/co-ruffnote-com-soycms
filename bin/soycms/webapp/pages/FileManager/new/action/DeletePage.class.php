<?php

class DeletePage extends CMSWebPageBase{
	
	function doPost(){
		
		//パス
		$path = $_POST["path"];
		
		//返り値
		$flag = 1;
		echo $flag;	//成功もしくは失敗を返す		
		
		exit;				
	}


    function DeletePage() {
    	WebPage::WebPage();
    	
    }
}
?>