<?php

/**
 * @table Label
 */
class Label {

	const ORDER_MAX = 10000000;

	/**
	 * @id
	 */
	private $id;
	
	private $caption;
	
	private $description;
	
	private $alias;
	
	private $icon;
	
	private $color = 0;
	
	/**
	 * @column background_color
	 */
	private $backgroundColor = 16777215;
	
	/**
	 * @column display_order
	 */
	private $displayOrder;
	
	/**
	 * @no_persistent
	 */
	private $entryCount = 0;
	
	function getId() {
		return $this->id;
	}
	function setId($id) {
		$this->id = $id;
	}
	function getCaption() {
		return $this->caption;
	}
	function getDisplayCaption() {
		return htmlspecialchars($this->caption, ENT_QUOTES, "UTF-8");
	}
	function setCaption($caption) {
		$this->caption = $caption;
	}

	function getAlias() {
   		if(strlen($this->alias)<1){
   			return $this->getId();
   		}
		return $this->alias;
	}
	function setAlias($alias) {
		$this->alias = $alias;
	}

	function getDescription() {
		return $this->description;
	}
	function getDisplayDescription() {
		return htmlspecialchars($this->description, ENT_QUOTES, "UTF-8");
	}
	function setDescription($description) {
		$this->description = $description;
	}

	function getIcon() {
		return $this->icon;
	}
	function setIcon($icon) {
		$this->icon = $icon;
	}
	
	function getIconUrl(){
		
		$icon = $this->getIcon();
		
		if(!$icon)$icon = "default.gif";
		
		return CMS_LABEL_ICON_DIRECTORY_URL . $icon;
		
	}

	function getEntryCount() {
		return $this->entryCount;
	}
	function setEntryCount($entryCount) {
		$this->entryCount = $entryCount;
	}

	function getColor() {
		return $this->color;
	}
	function setColor($color) {
		$this->color = $color;
	}
	function getBackgroundColor() {
		return $this->backgroundColor;
	}
	function setBackgroundColor($backgroundColor) {
		$this->backgroundColor = $backgroundColor;
	}
	function getDisplayOrder() {
		return $this->displayOrder;
	}
	function setDisplayOrder($displayOrder) {
		if(((int)$displayOrder) >= Label::ORDER_MAX)return;
		$this->displayOrder = $displayOrder;
	}
	function setDefaultDisplayOrder() {
		$this->displayOrder = Label::ORDER_MAX;
	}
	
	function compare($label){
		$a1 = $this->getDisplayOrder();
		$b1 = $label->getDisplayOrder();
		if(is_null($a1))$a1 = Label::ORDER_MAX;
		if(is_null($b1))$b1 = Label::ORDER_MAX;
		
		if($a1 === $b1){
			return ($this->getId() < $label->getId()) ? +1 : -1;
		}
		
		return ($a1 < $b1) ? -1 : +1;
		
	}
	
	/**
	 * サイトの管理者でないユーザが編集可能なラベルかどうか
	 */
	function isEditableByNormalUser(){
		return (strpos($this->getCaption(),"*") !== 0);
	}
}
?>