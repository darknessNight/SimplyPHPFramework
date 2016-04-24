<?php
class cms_pages_admin_admin{
    /**
     *
     * @var kernel 
     */
    private $kernel=null;
    
    function __construct(&$kernel) {
        $this->kernel=&$kernel;
        /*global $kernel;
        $query=$kernel->sql->query("SELECT `name`,`value` FROM `".$kernel->config->table."options`");
        while($opt=$query->fetch())
            $this->options[$opt['name']]=$opt['value'];*/
    }
    public function indexAction($array){
        $pages=$this->getSinglePage('home', '1');
        $page=null;
        for($i=0;$i<count($pages);$i++){
            if($this->kernel->config->userLang==$pages[$i]['lang']) {$page=$i;break;}
            if($this->kernel->config->lang==$pages[$i]['lang']) $page=$i;
        }
        $page=$pages[$page];
        $this->kernel->view->setVar('TITLE', $page['name']);
        $this->kernel->view->setVar('DESCRIPTION', $page['description']);
        $this->kernel->view->setVar('KEYWORDS', $page['keyword']);
        $this->kernel->view->addBlockWithTitle($page['name'], $page['content']);
    }
    public function postAction($array){
        $this->kernel->view->addBlockWithTitle('Wykryto stronę: '.$array['name'],'Wykryto stronę o nazwie "'.$array['name'].'" i indeksie "'.$array['page'].'"');
    }
    private function getSinglePage($var,$value){
        $query=$this->kernel->sql->prepare("SELECT * FROM `mycms_pages` WHERE `$var`=:value");
        $query->bindValue(':value', $value, PDO::PARAM_STR);
        $query->execute();
        $page=$query->fetchAll(PDO::FETCH_ASSOC);
        $query->closeCursor();
        return $page;
    }
} 
?>
