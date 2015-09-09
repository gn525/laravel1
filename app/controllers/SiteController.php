<?php

class SiteController extends Controller {
    private $extraCss = array();
    private $extraJs = array();
    
    private $section = '';
    private $pageTitle = '';
    
    public $appDir = 'site';
    
    public function __construct() {
        $this->section = Request::segment(1);
    }
    
    private function templateLayout($content) {
        $data = array();
        //$data['userName'] = $this->user['name'];
        //$data['userId'] = $this->user['id'];
        
        $data['content'] = $content;
        $data['pageTitle'] = $this->pageTitle;
        $data['section'] = $this->section;
        $data['extraCss'] = $this->extraCss;
        $data['extraJs'] = $this->extraJs;
        $data['alertSuccess'] = Session::pull('alertSuccess', '');
        $data['alertInfo'] = Session::pull('alertInfo', '');
        $data['alertWarning'] = Session::pull('alertWarning', '');
        $data['alertDanger'] = Session::pull('alertDanger', '');
        $data['signedIn'] = Auth::check();
        $data['userName'] = Auth::check() ? Auth::user()->first_name.' '.Auth::user()->last_name : '';
        
        return View::make($this->appDir.'/layout', $data);
    }

    protected function getTemplate($contentView = '', $data = array()) {
        $content = View::make($this->appDir.'/'.$contentView, $data);
        $layout = $this->templateLayout($content);

        return $layout;
    }

    protected function addExtraCssFile($file) {
        $this->extraCss[] = asset('/css/'.$file);
    }

    protected function addExternalCss($url) {
        $this->extraCss[] = $url;
    }
    
    protected function addExtraJsFile($file) {
        $this->extraJs[] = asset('/js/'.$file);
    }
    
    protected function addExternalJs($url) {
        $this->extraJs[] = $url;
    }
    
    protected function setPageTitle($title = '') {
        $this->pageTitle = $title;
    }
    
    protected function alertSuccess($message) {
        Session::put('alertSuccess', trim($message));
    }
    
    protected function alertInfo($message) {
        Session::put('alertInfo', trim($message));
    }
    
    protected function alertWarning($message) {
        Session::put('alertWarning', trim($message));
    }
    
    protected function alertDanger($message) {
        Session::put('alertDanger', trim($message));
    }
    
}