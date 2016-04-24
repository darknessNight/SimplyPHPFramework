<?php
/**
 * autoloader class
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 */
class autoloader {
    private $dirs=array();
    private $register=false;
    /**
     * 
     * @param array $dirs array of path where is files with class
     * @throws Exception if anyone dir doesn't exist
     */
    function __construct($dirs) {
        if(!is_array($dirs)) throw new Exception ('dirs isn\'t array');
        foreach ($dirs as $dir){
            if(!is_dir($dir)) throw new Exception($dir.' doesn\'t dir');
        }
        $this->dirs=$dirs;
        $this->register=true;
    }
    
    public function autoload($className){
        $className=str_replace('_', '/', $className);
        foreach($this->dirs as $dir){
            if(file_exists($dir.$className.'.php')){
                require_once $dir.$className.'.php';
                return true;
            }
        }
        return false;
    }
    public function register(){
        if($this->register){
            spl_autoload_register(array($this, 'autoload'));
            $this->register=false; 
        }
    }
}

?>
