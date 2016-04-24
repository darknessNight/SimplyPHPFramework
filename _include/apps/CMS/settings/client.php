<?php
/**
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2013, Paweł Przytarski
 * @version 0.1.4
 */
class cms_settings_client{
    private $options=array();
    /**
     *
     * @var kernel
     */
    private $kernel=null;

    function __construct(&$kernel) {
        $this->kernel=&$kernel;
        $opts = $kernel->db->settings->find();
	foreach ($opts as $opt)
	    $this->options[$opt['name']]=$opt['value'];
    }

    function __destruct() {
	$this->kernel->view->setVar('LOGO', '<img src="' . kernel::host() . $this->options['logo'] . '" alt="Logo" />');
	if($this->kernel->view->issetVar('TITLE'))
            $name=$this->kernel->view->getVar('TITLE');
        else $name='';
        if(strlen($name)==0){
            $this->kernel->view->setVar('TITLE', $this->options['title']);
        }else {
            $this->kernel->view->setVar('TITLE', $name.' :: '.$this->options['title']);
        }
    }
    public function __get($name) {
	    if(array_key_exists($name, $this->options))
                return $this->options[$name];
	else
	    return NULL;
    }
    public function __set($name, $value) {
            $this->options[$name] = $value;
    }
    public function __toString() {
        return 'Settings';
    }
}
?>
