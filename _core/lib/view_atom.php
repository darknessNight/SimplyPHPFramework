<?php
/**
 * File with atom class
 * @todo
 * opisać i sprawdzić klase
 */

/**
 * 
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2013-2014, Paweł Przytarski
 * @version 1.0.4
 */
class atom{
    private $content='<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom">';
    private $isView=false;
    public function __construct($id=NULL){
        $serwis=kernel::host();
        $page='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING']==''?'':'?'.$_SERVER['QUERY_STRING']);
        $this->content.='<link href="'.$page.'" rel="self" /><link href="'.$serwis.'"/>'.
                '<updated>'.  date(DATE_ATOM).'</updated>'.
             '<id>'.$page.'channel'.(is_null($id)?$page:$id).'</id>';
    }
    public function __destruct(){
        $this->view();
    }
    public function view(){
        if(!$this->isView) {echo $this->content.'</feed>';
        header('Content-Type: application/atom+xml');}
        $this->isView=true;
    }
    public function addTitle($title){
        $this->header.='<title>'.$title.'</title>';
    }
    public function addSubtitle($title){
        $this->header.='<subtitle>'.$title.'</subtitle>';
    }
    public function addAuthor($name,$email=null){
        $this->header.='<author><name>'.$name.'</name><email>'.$email.'</email></author>';
    }
    public function addLogo($logo){
        $this->header.='<logo>'.$logo.'</logo>';
    }
    public function addIcon($icon){
         $this->header.='<icon>'.$icon.'</icon>';
    }
    public function addRights($rights){
        $this->header.='<rights>'.$rights.'</rights>';
    }
    public function addCategory($category){
        $this->header.='<category term="'.$category.'"/>';
    }
    //do obsługi elementów
    public function newElement(){
        $this->content.='<entry>';
    }
    public function closeElement($idElement){
        $this->content.='<id>'.$idElement.'</id>';
        $this->content.='</entry>';
    }
    public function addElementTitle($title){
        $this->content.='<title>'.$title.'</title>';        
    }
    public function addElementUpdate($date,$string=true){
        if($string){
            $date=date(DATE_ATOM,  strtotime($date));
        }else $date=  date (DATE_ATOM,$date);
        $this->content.='<update>'.$date.'</update>';
    }
    public function addElementUrl($url,$typeUrl=null){
        $this->content.='<link rel="alternate" '.(is_null($typeUrl)?'':'type="'.$typeUrl.'"').' href="'.$url.'"/>';
    }
    public function addElementContent($content){
        $this->content.='<content>'.$content.'</content>';
    }
    public function addElementSummary($summary){
        $this->content.='<summary>'.$summary.'</summary>';
    }
    public function addElememtContentWithType($content,$type){
        $this->content.='<content type="'.$type.'">'.$content.'</content>';
    }
    public function addElementSummaryWithType($summary,$type){
        $this->content.='<summary type="'.$type.'">'.$summary.'</summary>';
    }
    public function addElementCategory($category){
        $this->content.='<category term="'.$category.'"/>';
    }
    public function addElementPublish($date,$string=true){
        if($string){
            $date=date(DATE_ATOM,  strtotime($date));
        }else $date=  date (DATE_ATOM,$date);
        $this->content.='<publish>'.$date.'</publish>';
    }
    public function addElementRights($rights){
        $this->content.='<rights>'.$rights.'</rights>';
    }
    public function addElementAuthor($author,$address=null,$page=null){
         $this->content.='<author><name>'.$author.'</name><uri>'.$page.'</uri><email>'.$address.'</email></author>';
    }
}
?>
