<?php

class UnstickLabelAction  extends SOY2Action{

    function execute($request,$form,$response) {
    	
    	$dao = SOY2DAOFactory::create("cms.EntryLabelDAO");
    	$dao->begin();
    	foreach($form->label as $label){
    		foreach($form->entry as $entry){
    			$dao->deleteByParams($entry,$label);
    		}
    	}
    	$dao->commit();
    	
    	return SOY2Action::SUCCESS;
    }
}

class UnstickLabelActionForm extends SOY2ActionForm{
	
	var $label = array();
	var $entry = array();
	
	function setLabel($label){
		$this->label = $label;
		if(is_null($this->label)){
			$this->label = array();
		}
	}
	
	function setEntry($entry){
		$this->entry = $entry;
		if(is_null($this->entry)){
			$this->label = array();
		}
	}
	
}
?>