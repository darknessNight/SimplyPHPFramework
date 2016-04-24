<?php
/**
 * File with kernel class
 * @todo
 * dodać captha
 */
require_once(CORE . 'lib/rewrite.php');

/**
 * kernel
 * load needed libs and config. start CMS
 * @version 1.1.2 alfa
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2014, Paweł Przytarski
 */
class kernel {
    private $responseCode = 200;

    /**
     * access variable with MongoDB class
     * @var MongoDB
     * @access public
     */
    public $db = null;

    /**
     * access variable with view module class
     * @var ViewHtml|atom
     * @access public
     */
    public $view=null;
    /**
     * array with error logs
     * @var array
     * @access private
     */
    private $errors=array();
    /**
     * class with excluding settings
     * @var config
     * @access public
     */
    public $config=null;
    /**
     * access array for languages
     * @var array
     * @access public
     */
    public $lang=array();
    /**
     * access var to session
     * @var session
     */
    public $session=null;
    /**
     * access var to mailer
     * @var PHPmailer
     */
    public $mail=null;
    /**
     * rewrite module
     * @var Rewrite
     */
    public $rewrite=null;

    private $mnClient = null;

    /**
     * load all needed data, aplications and modules. Connect to database and save logs
     * @global array $lang array from lang file
     * @param boolean $database if true kernel load database driver (PDO class)
     * @param boolean $session if true kernel load and active session
     * @param boolean $mail if true kernel load PHPmailer
     */
    public function __construct($database=true,$session=true,$mail=false) {
        header('X-Powered-By: unknowed');
        header('Server: unknowed');
	header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');

	require_once CORE.'lib/autoloader.php';
        $loader=new autoloader(array(APPS,MODULES,CONFIG));
        $loader->register();
        //load excluding settings
        //require_once CONFIG.'config.php';
        $this->config=new config();
        error_reporting($this->config->errorReporting);
        unset($this->config->errorReporting);

        //choose languages for users. language choose from headers
        $language=$this->config->lang;
        $langs = explode(',', explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]);
	foreach ($langs as $value) {
            if(!preg_match('/([a-zA-Z][a-zA-Z]\-[a-zA-Z][a-zA-Z])/', $value))
                $value=preg_replace('/([a-zA-Z][a-zA-Z])/', '\\1_\\1', $value);
            else $value=  str_replace ('-', '_', $value);
            if(file_exists(LANG.$value.'/lang.php')) {$language=$value;break;}
        }

        //load language file

        if(!include_once LANG.$language.'/lang.php'){
                if(!include_once LANG.'pl_pl/lang.php'){
                   $this->errors[] = array('Default language isn\'t exists', 'system', $this->logTime());
		die('Languages files don\'t exists');
                }
        }
        $this->config->userLang = $language;
	//global $lang;
	$this->lang = $lang;

	//create instant of PDO and connect with database
        if($database){
            try {
		$mongo = new MongoClient("mongodb://" . $this->config->dbserver . ":" . $this->config->dbport, [
		    "username" => $this->config->dbuser,
		    "password" => $this->config->dbpass,
		    "db" => $this->config->dbname
		]);
		$this->mnClient=$mongo;
		$this->db = $mongo->selectDB($this->config->dbname);
	    }
            catch (Exception $e){
                $this->errors[] = array('Can\'t connect to database', 'system', $this->logTime());
		die($this->lang['notConnect2Database']);
            }
        }
        //delete informations about database
        unset($this->config->dbserver);
	unset($this->config->dbuser);
	unset($this->config->dbpass);
	unset($this->config->dbport);
	unset($this->config->dbname);

	//load routing module
            try{
                $this->rewrite = new Rewrite($this->config->routingFile, false, $this->config->smartAddress);
	}
            catch(Exception $e){
                echo $e->getMessage().'<br/>';
                $this->errors[] = array($e->getMessage() . ' - ' . $e->getFile() . ', ' . $e->getLine(), 'system', $this->logTime());
		die("<br/>\n<b>Fatal error</b>");
            }

        //change logs path from relative to absolute
        $this->config->errorlogs=realpath($this->config->errorlogs);
        $this->config->adminlogs=realpath($this->config->adminlogs);

        //set session's options
        if($session){
            if(!$this->activeSession())
                die('System file don\'t exists');
        }
        //set PHPmailer options
        if($mail){
            if(!$this->activeMail())
                die('System file don\'t exists');
        }

    }
    /**
     * save log error and unset objects
     */
    public function __destruct() {
	$this->sendResponseCode();
	try{
            unset($this->db);
	    unset($this->rewrite);
            unset($this->view);
		$this->mnClient->Close();
		unset($this->mnClient);
	}
        catch(Exception $e){
            echo 'System error';
	    $this->errors[] = array($e->getMessage() . ' - ' . $e->getFile() . ', ' . $e->getLine(), 'system', $this->logTime());
	    $this->save2log();
	}
	$this->save2log();
    }
    /**
     * return website URL
     * @return string website folder URL
     */
    public static function host() {
	return Rewrite::host();
    }
    /**
     * loading apps if app is in database and app is active
     *
     * @param string $name name of app to load
     * @param array $apps array where add app
     * @return bool false if app not loaded
     */
    public function loadApp($name,&$apps){
        if (is_null($this->db))
	    return false;
        try {
            $query = $this->db->apps->findOne(['name' => $name]);
	    if (!is_null($query) && $query['active']) {
		$class=$name.'_'.TYPE;
                if(class_exists($class)){
                    if(is_array($apps)){
                        $apps+=array($name=>new $class($this));
                    }else{
                        $apps=new $class($this);
                    }
                    return true;
                }
		else
		    $this->errors[] = array('Requested app isn\'t exist', 'system', $this->logTime());
	    }
	    else
		$this->errors[] = array('Requested app "' . $name . '" isn\'t active', 'system', $this->logTime());
	    return false;
	}
        catch(Exception $e){
            die('System error');
	    $this->errors[] = array($e->getMessage() . ' - ' . $e->getFile() . ', ' . $e->getLine(), 'system', $this->logTime());
	}
    }
    /**
     * loading apps if app is in database and app is active
     *
     * @param string $name name of app to load
     * @return bool|string false if app not loaded else name of loaded app
     */
    public function loadHomeApp(&$apps){
        if (is_null($this->db))
	    return false;
	if($this->config->debug)
                echo "Loading home application...\n";
        try {
            if($this->config->debug)
                echo "Search home application.\n";
	    $query = $this->db->apps->findOne(['active' => '1', 'home' => '1']);
	    if($this->config->debug)
                echo "SQL query send.\n";
            if (!is_null($query['name'])) {
		if ($this->loadApp($query['name'], $apps))
		    return $query['name'];
	    }
        }
        catch(Exception $e){
            if($this->config->debug)
                echo $e->getMessage ().' - '.$e->getFile ().', '.$e->getLine ();
            else echo 'System error';
            exit();
            $this->errors[] = array($e->getMessage() . ' - ' . $e->getFile() . ', ' . $e->getLine(), 'system', $this->logTime());
	}
    }
    /**
     * load every active and required apps
     *
     * This function use loadApp function
     *
     * @param array $apps array where add app
     * @return bool if false apps not loaded
     */
    public function loadRequiredApps(&$apps){
        if (is_null($this->db))
	    return false;
	if($this->config->debug)
            echo "Loading apps...\n";
        $data = $this->db->apps->find(['required' => '1', 'active' => '1']);
	foreach ($data as $app){
            $this->loadApp($app['name'],$apps);
            if($this->config->debug)
                echo "Load {$app['name']}.\n";
        }
        return true;
    }
    /**
     * loading theme and choose class for view(html or rss)
     * @param string $type string name of used view type
     */
    public function loadTheme($type='html',$theme='default'){
        if(file_exists(CORE.'lib/view_'.$type.'.php')){
            if(include_once CORE.'lib/view_'.$type.'.php')
                    try{
                        if($type=='html')
                            $this->view = new ViewHtml(THEMES, $theme);
		    else $this->view=$type();
                    }
                    catch(Exception $e){
                        $this->errors[] = array($e->getMessage() . ' - ' . $e->getFile() . ', ' . $e->getLine(), 'system', $this->logTime());
		    die("<br/>\n<b>Fatal error</b>");
                    }
            else {
		$this->errors[] = array('System file isn\'t exist', 'system', $this->logTime());
		die($this->lang['systemFileNotFound']);
	    }
	} else {
	    $this->errors[] = array('System file isn\'t exist', 'system', $this->logTime());
	    die($this->lang['systemFileNotFound']);
	}
    }
    /**
     * return time for logs
     * @return string string with date to log
     */
    private function logTime(){
        return date('Y-m-d H:i:s');
    }
    /**
     * saving errors log to file
     */
    private function save2log() {
	if(empty($this->errors)) return;
        $log=fopen($this->config->errorlogs, 'a');
        if($log){
            flock($log, LOCK_EX);
            foreach($this->errors as $error)
                fwrite($log, $error[2]."\t".$error[1]."\t".$error[0].";\n");
            flock($log, LOCK_UN);
            fclose($log);
        }
    }
    /**
     * Loading PHPmailer class and prepare to use
     * @return boolean result activating PHPmailer
     */
    public function activeMail(){
        if(!@include_once  CORE.'lib/PHPmailer/class.phpmailer.php'){
            $this->errors[] = array('PHPmailer module doesn\'t exist', 'system', $this->logTime());
	    return false;
        }
        try{
            $this->mail= new PHPMailer();
        }
        catch(Exception $e){
            $this->errors[] = array('Session exception: ' . $e->getMessage(), 'system', $this->logTime());
	    throw $e;
        }
        if($this->config->mailer=='smtp')
            $this->mail->isSMTP();
        else
            $this->mail->isMail();
        $this->mail->From=$this->config->mailfrom;
        $this->mail->FromName=$this->config->mailfromname;
        $this->mail->PluginDir=MODULES.'PHPmailer';
        $this->mail->Host=$this->config->mailhost;
        $this->mail->Username=$this->config->mailuser;
        $this->mail->Password=$this->config->mailpass;
        $this->mail->Port=$this->config->mailport;
        $this->mail->SMTPAuth=$this->config->mailauth;
        $this->mail->SMTPSecure = $this->config->mailSecure;
        $this->mail->XMailer='MyCMS';
        $this->mail->CharSet='UTF-8';
        unset($this->config->mailfrom);
        unset($this->config->mailfromname);
        unset($this->config->mailhost);
        unset($this->config->mailuser);
        unset($this->config->mailpass);
        unset($this->config->mailport);
        unset($this->config->mailauth);
        unset($this->config->mailSecure);
        return true;
    }
    /**
     * active session
     * @return boolean result activating session
     * @throws Exception Exception from Session class
     */
    public function activeSession(){
        if(!@include_once CORE.'lib/session.php'){
            $this->errors[] = array('System file doesn\'t exist', 'system', $this->logTime());
	    return false;
        }
        try{
            $this->session=new session($this,$this->config->sessiontime, $this->config->tmpPath, 'MyCMSsession', $this->config->cookieSession);
        }
        catch(Exception $e){
            $this->errors[] = array('Session exception: ' . $e->getMessage(), 'system', $this->logTime());
        }
        return true;
    }

    public function setResponseCode($code) {
	if (is_int($code) && $code >= 200 && $code < 600) {
	    $this->responseCode = $code;
	}
    }

    private function sendResponseCode() {
	if ($this->responseCode != 200) {
	    $responseText;
	    //header($_SERVER['SERVER_PROTOCOL'] . ' ' . $this->responseCode . ' ' . $responseText);
	    header($_SERVER['SERVER_PROTOCOL'] . ' ' . $this->responseCode);
	    $this->view->setVar('TITLE', 'Error ' . $this->responseCode);
	    if (isset($this->lang['error' . $this->responseCode])) {
		$this->view->setVar('CONTENT_TITLE', $this->responseCode);
		$this->view->setVar('CONTENT', $this->lang['error' . $this->responseCode]);
	    } else {
		$this->view->setVar('CONTENT_TITLE', $this->responseCode);
		$this->view->setVar('CONTENT', $this->lang['error404']);
	    }
	}
    }

}
?>