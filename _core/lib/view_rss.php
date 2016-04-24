<?php
/**
 * File with rss class
 * @todo
 * opisać i sprawdzić klase
 */

//RSS view class
/**
 * 
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2013, Paweł Przytarski
 * @version 0.1.4
 */
class rss{
    private $header='<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>';
    private $footer=' </channel></rss>';
    private $content='';
    private $isView=false;
    public function __construct($id=NULL){
        $serwis=kernel::host();
        $page='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING']==''?'':'?'.$_SERVER['QUERY_STRING']);
        $this->header.='<link>'.$serwis.'</link>'.
                '<lastBuildDate>'.  date(DATE_RSS).'</lastBuildDate>'.
             '<id>'.$page.'channel'.(is_null($id)?$page:$id).'</id>';
    }
    public function __destruct(){
        $this->view();
    }
    public function view(){
        if(!$this->isView) {echo $this->header.$this->content.$this->footer;header('Content-Type: application/rss+xml');}
        $this->isView=true;
    }
    public function addTitle($title){
        $this->header.='<title>'.$title.'</title>';
    }
    public function addDescription($Description){
        $this->header.='<description>'.$Description.'</description>';
    }
    public function addRights($rights){
        $this->header.='<rights>'.$rights.'</rights>';
    }
    public function addLogo($logo){
        $this->header.='<image>'.$logo.'</image>';
    }
    public function addCategory($category){
        $this->header.='<category>'.$category.'</category>';
    }
    //do obsługi elementów
    public function newElement(){
        $this->content.='<item>';
    }
    public function closeElement($idElement){
        $this->content.='<guid>'.$idElement.'</guid>';
        $this->content.='</item>';
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
    public function addElementUrl($url){
        $this->content.='<link>'.$url.'</link>';
    }
    public function addElementDescription($summary){
        $this->content.='<description>'.$summary.'</description>';
    }
    public function addElementCategory($category){
        $this->content.='<category>'.$category.'</category>';
    }
    public function addElementPublish($date,$string=true){
        if($string){
            $date=date(DATE_RSS,  strtotime($date));
        }else $date=  date (DATE_RSS,$date);
        $this->content.='<pubDate>'.$date.'</puDate>';
    }
    public function addElementAuthor($author,$address){
         $this->content.='<author>'.$address.' ('.$author.')</author>';
    }
}
?>
