<?php
SOY2::import("domain.cms.Page");

/**
 * @table BlogPage
 */
class BlogPage extends Page{
	
	const TEMPLATE_ARCHIVE = "archive";
	const TEMPLATE_TOP = "top";
	const TEMPLATE_ENTRY = "entry";
	const TEMPLATE_POPUP = "popup";
	
	//トップページのURL
	private $topPageUri = "";
	
    //単体ページのURL
	private $entryPageUri = "article";
	
	//月別ページのURL
	private $monthPageUri = "month";
	
	//カテゴリ別ページのURL
	private $categoryPageUri = "category";
	
	//RSSページのURL
	private $rssPageUri = "feed";
	
	//トップページの表示件数
	private $topDisplayCount = 10;
	
	//月別ページの表示件数
	private $monthDisplayCount = 10;
	
	//カテゴリ別ページの表示件数
	private $categoryDisplayCount = 10;
	
	//RSSの表示件数
	private $rssDisplayCount = 10;
	
	//単体ページの生成フラグ
	private $generateEntryFlag = true;
	
	//トップページの生成フラグ
	private $generateTopFlag = true;
	
	//月別ページの生成フラグ
	private $generateMonthFlag = true;
	
	//カテゴリ別ページの生成フラグ
	private $generateCategoryFlag = true;
	
	//RSSの生成フラグ
	private $generateRssFlag = true;
	
	//トップページのタイトルフォーマット
	private $topTitleFormat = "%BLOG%";
	
	//月別ページのタイトルフォーマット
	private $monthTitleFormat = "%BLOG%";
	
	//カテゴリー別ページのタイトルフォーマット
	private $categoryTitleFormat = "%BLOG%";
	
	//単体ページのタイトルフォーマット
	private $entryTitleFormat = "%BLOG%";
	
	//フィードのタイトルフォーマット
	private $feedTitleFormat = "%BLOG%";
	
	
	//使用するラベル一覧
	private $blogLabelId;
	
	//カテゴリ分けに使うラベル一覧
	private $categoryLabelList = array();
	
	private $description;
	
	private $author;
	
	//コメントのデフォルト承認
	private $defaultAcceptComment;
	
	private $defaultAcceptTrackback;
	
	/**
	 * @param startWithSlash /で始まるかどうか
	 */
	function getEntryPageUri($startWithSlash = false) {
		if($startWithSlash && strlen($this->entryPageUri)>0){
			return "/" . $this->entryPageUri;
		}else{
			return $this->entryPageUri;
		}
	}
	function setEntryPageUri($entryPageUri) {
		$this->entryPageUri = $entryPageUri;
	}
	function setCategoryLabelList($list){
		if(!is_array($list))return;
		$this->categoryLabelList = $list;
	}
	function getCategoryLabelList(){
		return $this->categoryLabelList;
	}
	
	/**
	 * トップページのURL
	 */
	function getTopPageURL($withPageUri = true){
		if($withPageUri && strlen($this->getUri()) >0){
			if(strlen($this->getTopPageUri()) >0){
				return $this->getUri() . "/" . $this->getTopPageUri();
			}else{
				return $this->getUri();
			}
		}else{
			return $this->getTopPageUri();
		}
	}
	/**
	 * エントリーページのURLを取得（末尾はスラッシュ付き）
	 * 
	 * @param withPageUri ページのUriを追加するかどうか
	 */
	function getEntryPageURL($withPageUri = true){
		$url = "";
		if($withPageUri && strlen($this->getUri()) >0){
			$url .= $this->getUri() . "/";
		}
		if(strlen($this->getEntryPageUri()) >0){
			$url .= $this->getEntryPageUri() . "/";
		}
		return $url;
	}
	/**
	 * カテゴリーアーカイブのURL（末尾はスラッシュ付き）
	 */
	function getCategoryPageURL($withPageUri = true){
		$url = "";
		if($withPageUri && strlen($this->getUri()) >0){
			$url .= $this->getUri() . "/";
		}
		if(strlen($this->getCategoryPageUri()) >0){
			$url .= $this->getCategoryPageUri() . "/";
		}
		return $url;
	}
	/**
	 * 月別アーカイブのURL（末尾はスラッシュ付き）
	 */
	function getMonthPageURL($withPageUri = true){
		$url = "";
		if($withPageUri && strlen($this->getUri()) >0){
			$url .= $this->getUri() . "/";
		}
		if(strlen($this->getMonthPageUri()) >0){
			$url .= $this->getMonthPageUri() . "/";
		}
		return $url;
	}
	/**
	 * RSSページのURL
	 */
	function getRssPageURL($withPageUri = true){
		if($withPageUri && strlen($this->getUri()) >0){
			return $this->getUri() . "/" . $this->getRssPageUri();
		}else{
			return $this->getRssPageUri();
		}
	}

	/**
	 * 保存用のstdObjectを返します。
	 */
	function getConfigObj(){
		
		$obj = new stdClass();
		
		$obj->topPageUri = $this->topPageUri;
		$obj->entryPageUri = $this->entryPageUri;
		$obj->monthPageUri = $this->monthPageUri;
		$obj->categoryPageUri = $this->categoryPageUri;
		$obj->rssPageUri = $this->rssPageUri; 
		
		$obj->blogLabelId = $this->blogLabelId;
		$obj->categoryLabelList = $this->categoryLabelList;
		
		$obj->topDisplayCount = $this->topDisplayCount;
		$obj->monthDisplayCount = $this->monthDisplayCount;
		$obj->categoryDisplayCount = $this->categoryDisplayCount;
		$obj->rssDisplayCount = $this->rssDisplayCount;
		
		$obj->generateTopFlag = $this->generateTopFlag;
		$obj->generateMonthFlag = $this->generateMonthFlag;
		$obj->generateCategoryFlag = $this->generateCategoryFlag;
		$obj->generateRssFlag = $this->generateRssFlag;
		$obj->generateEntryFlag = $this->generateEntryFlag;
		
		$obj->topTitleFormat = @$this->topTitleFormat;
		$obj->monthTitleFormat = @$this->monthTitleFormat;
		$obj->categoryTitleFormat = @$this->categoryTitleFormat;
		$obj->entryTitleFormat = @$this->entryTitleFormat;
		$obj->feedTitleFormat = @$this->feedTitleFormat;
		
		$obj->description = @$this->description;
		$obj->author = @$this->author;
		
		$obj->defaultAcceptComment = @$this->defaultAcceptComment;
		$obj->defaultAcceptTrackback = @$this->defaultAcceptTrackback;
		
		return $obj;
	}
	
	function _getTemplate(){
		
		$array = @unserialize($this->getTemplate());
		
		if(!is_array($array)){
			$array = array(
				BlogPage::TEMPLATE_ARCHIVE => "",
				BlogPage::TEMPLATE_TOP => "",
				BlogPage::TEMPLATE_ENTRY => "",
				BlogPage::TEMPLATE_POPUP => "",			
			);
		}
		
		return $array;
	}
	
	/**
     * アーカイブテンプレート
     */
    function getArchiveTemplate(){
    	$template = $this->_getTemplate();
    	return $template[BlogPage::TEMPLATE_ARCHIVE];
    }
	
	/**
	 * ブログトップページ
	 */
	function getTopTemplate(){
		$template = $this->_getTemplate();
    	return $template[BlogPage::TEMPLATE_TOP];
	}
   
    /**
     * エントリーテンプレート
     */
	function getEntryTemplate(){
		$template = $this->_getTemplate();
    	return $template[BlogPage::TEMPLATE_ENTRY];
	}
    	
    /**
     * ポップアップコメントテンプレート
     */
     function getPopUpTemplate(){
     	$template = $this->_getTemplate();
    	return $template[BlogPage::TEMPLATE_POPUP];
     }

	 function getMonthPageUri() {
     	return $this->monthPageUri;
     }
     function setMonthPageUri($monthPageUri) {
     	$this->monthPageUri = $monthPageUri;
     }
     function getCategoryPageUri() {
     	return $this->categoryPageUri;
     }
     function setCategoryPageUri($categoryPageUri) {
     	$this->categoryPageUri = $categoryPageUri;
     }
     function getRssPageUri() {
     	return $this->rssPageUri;
     }
     function setRssPageUri($rssPageUri) {
     	$this->rssPageUri = $rssPageUri;
     }
     function getTopDisplayCount() {
     	return $this->topDisplayCount;
     }
     function setTopDisplayCount($topDisplayCount) {
     	$this->topDisplayCount = (int)$topDisplayCount;
     }
     function getMonthDisplayCount() {
     	return $this->monthDisplayCount;
     }
     function setMonthDisplayCount($monthDisplayCount) {
     	$this->monthDisplayCount = (int)$monthDisplayCount;
     }
     function getCategoryDisplayCount() {
     	return $this->categoryDisplayCount;
     }
     function setCategoryDisplayCount($categoryDisplayCount) {
     	$this->categoryDisplayCount = (int)$categoryDisplayCount;
     }
     function getRssDisplayCount() {
     	return $this->rssDisplayCount;
     }
     function setRssDisplayCount($rssDisplayCount) {
     	$this->rssDisplayCount = (int)$rssDisplayCount;
     }

     function getGenerateTopFlag() {
     	if(defined('CMS_PREVIEW_MODE') && CMS_PREVIEW_MODE){
     		return true;
     	}else{
     		return $this->generateTopFlag;
     	}
     }
     function setGenerateTopFlag($generateTopFlag) {
     	$this->generateTopFlag = $generateTopFlag;
     }

     function getGenerateMonthFlag() {
     	if(defined('CMS_PREVIEW_MODE') && CMS_PREVIEW_MODE){
     		return true;
     	}else{
     		return $this->generateMonthFlag;
     	}
     }
     function setGenerateMonthFlag($generateMonthFlag) {
     	$this->generateMonthFlag = $generateMonthFlag;
     }

     function getGenerateCategoryFlag() {
     	if(defined('CMS_PREVIEW_MODE') && CMS_PREVIEW_MODE){
     		return true;
     	}else{
     		return $this->generateCategoryFlag;
     	}
     }
     function setGenerateCategoryFlag($generateCategoryFlag) {
     	$this->generateCategoryFlag = $generateCategoryFlag;
     }

     function getGenerateRssFlag() {
     	if(defined('CMS_PREVIEW_MODE') && CMS_PREVIEW_MODE){
     		return true;
     	}else{
     		return $this->generateRssFlag;
     	}
     }
     function setGenerateRssFlag($generateRssFlag) {
     	$this->generateRssFlag = $generateRssFlag;
     }

     function getGenerateEntryFlag() {
     	if(defined('CMS_PREVIEW_MODE') && CMS_PREVIEW_MODE){
     		return true;
     	}else{
     		return $this->generateEntryFlag;
     	}
     }
     function setGenerateEntryFlag($generateEntryFlag) {
     	$this->generateEntryFlag = $generateEntryFlag;
     }

     function getTopTitleFormat() {
     	return $this->topTitleFormat;
     }
     function setTopTitleFormat($topTitleFormat) {
     	$this->topTitleFormat = $topTitleFormat;
     }

     function getMonthTitleFormat() {
     	return $this->monthTitleFormat;
     }
     function setMonthTitleFormat($MonthTitleFormat) {
     	$this->monthTitleFormat = $MonthTitleFormat;
     }

     function getCategoryTitleFormat() {
     	return $this->categoryTitleFormat;
     }
     function setCategoryTitleFormat($CategoryTitleFormat) {
     	$this->categoryTitleFormat = $CategoryTitleFormat;
     }

     function getEntryTitleFormat() {
     	return $this->entryTitleFormat;
     }
     function setEntryTitleFormat($EntryTitleFormat) {
     	$this->entryTitleFormat = $EntryTitleFormat;
     }

     function getBlogLabelId() {
     	return $this->blogLabelId;
     }
     function setBlogLabelId($blogLabelId) {
     	$this->blogLabelId = $blogLabelId;
     }

     function getDescription() {
     	return $this->description;
     }
     function setDescription($description) {
     	$this->description = $description;
     }
     function getAuthor() {
     	return $this->author;
     }
     function setAuthor($author) {
     	$this->author = $author;
     }     

     function getDefaultAcceptComment() {
     	return $this->defaultAcceptComment;
     }
     function setDefaultAcceptComment($defaultAcceptComment) {
     	$this->defaultAcceptComment = $defaultAcceptComment;
     }

     function getDefaultAcceptTrackback() {
     	return $this->defaultAcceptTrackback;
     }
     function setDefaultAcceptTrackback($defaultAcceptTrackback) {
     	$this->defaultAcceptTrackback = $defaultAcceptTrackback;
     }

     function getFeedTitleFormat() {
     	return $this->feedTitleFormat;
     }
     function setFeedTitleFormat($feedTitleFormat) {
     	$this->feedTitleFormat = $feedTitleFormat;
     }

     function getTopPageUri() {
     	return $this->topPageUri;
     }
     function setTopPageUri($topPageUri) {
     	$this->topPageUri = $topPageUri;
     }
}
?>