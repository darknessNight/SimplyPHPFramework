<?php
class cms_settings_admin_admin{
    /**
     *
     * @var kernel
     */
    private $kernel;
    public function __construct(&$kernel) {
        $this->kernel=&$kernel;
    }

    public function indexAction(){
        $this->kernel->view->addBlockWithTitle('Jakaś treść','Jakaś treść');
    }
} 
?>
