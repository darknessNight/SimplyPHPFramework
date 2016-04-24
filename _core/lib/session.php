<?php
/**
 * File with session class
 * @todo utworzenie renameURL i checkCookie
 */

/**
 * manage session and sessions data
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2014, Paweł Przytarski
 * @version 0.0.8
 */
class session{
    /**
     * number of session
     * @var string
     * @access private
     */
    private $id=0;
    /**
     * temporary files path
     * @var string
     * @access private
     */
    private $dir;
    /**
     * array of data session
     * @var array
     * @access private
     */
    public $session = array();

    /**
     * time of expire session (minutes)
     * @var integer
     * @access private
     */
    private $expire;
    /**
     * use cookie
     * @var boolean
     * @access private
     */
    private $cookie=true;
    /**
     * session name
     * @var string
     * @access private
     */
    private $name;
    /**
     * time of start session
     * @var integer
     * @access private
     */
    private $start;
    /**
     * array of chars for ID
     * @var array
     * @access private
     */
    private $chars='qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    /**
     * Lenght of ID
     * @var integer
     * @access private
     */
    private $IDlen=40;
    /**
     * active debug mode
     * @var bool
     * @access private
     */
    private $debug=false;
    /**
     * access to kernel uses only in MyCMS
     * @var kernel
     */
    private $kernel=null;
    /**
     * start session and change configuration
     * @param integer $expire time (minutes) of expire session
     * @param path $dir path of session data save
     * @param boolean $globalArray if true used the $_SESSION array
     * @param string $name session name
     * @param boolean $cookie use cookie
     */
    public function __construct(&$kernel,$expire=180,$dir='./tmp',$name='Session',$cookie=true,$debug=false) {
        $this->kernel=&$kernel;
        $this->expire=$expire;
        $this->name = (strlen($name) > 0 ? $name : 'Session');

	session_cache_expire($expire);
	session_name($this->name);

	$this->cookie = true;
	$this->getSession();
    }
    /**
     * save session data
     */
    public function __destruct() {
        //add to array information about user
        $data = $this->session + $this->userInfo();
	if (!isset($this->session['REMOTE_ADDR']) || ($this->session['REMOTE_ADDR'] == $data['REMOTE_ADDR'] && $this->session['HTTP_USER_AGENT'] == $data['HTTP_USER_AGENT'])) {
	    $_SESSION = $data;
	} else {
	    $_SESSION = array();
	}
    }
    /**
     * regenerate session id and check id can be use
     * @throws Exception if can't save session file
     */
    public function regenerateSession() {
	session_regenerate_id();
	$this->id = session_id();
    }
    /**
     * get data actualy session
     * @throws Exception if Can't read session
     */
    private function getSession() {
	    if (!$this->cookie) {
	    if (isset($this->kernel->rewrite->vars[$this->name]) && is_string($this->kernel->rewrite->vars[$this->name])) {
		    session_id($this->kernel->rewrite->vars[$this->name]);
		session_start();
	    } else {
		session_start();
		$this->id = session_id();
		}
	}
	else
	    session_start();

	$data = $_SESSION;
	if (!isset($data['REMOTE_ADDR']) || !($data['REMOTE_ADDR'] == $this->userInfo()['REMOTE_ADDR'] && $data['HTTP_USER_AGENT'] == $this->userInfo()['HTTP_USER_AGENT'])) {
	    $this->session = array();
	} else {
	    $this->session = $data;
	}
    }
    /**
     * easy access to session data
     * @param string|integer $index index of element in session array
     * @return null|mixed if index exist in session array return value else return null
     */
    public function __get($index){
        if($index=='REMOTE_ADDR' || $index=='HTTP_USER_AGENT' || $index=='COOKIE_ACTIVE') return null;
        if(@array_key_exists($index, $this->session))
            return $this->session[$index];
        else return null;
    }
    /**
     * easy add new element to session array
     * @param string|integer $index index of element to add to session array
     * @param mixed $value value of element
     * @return bool if operaction is success
     */
    public function __set($index,$value){
        if($index=='REMOTE_ADDR' || $index=='HTTP_USER_AGENT' || $index=='COOKIE_ACTIVE') return false;
            return $this->session[$index]=$value;
    }
    /**
     * delete session data of specified index
     * @param string $index
     * @return boolean false if failure
     */
    public function __unset($index){
        if($index=='REMOTE_ADDR' || $index=='HTTP_USER_AGENT' || $index=='COOKIE_ACTIVE') return false;
        if(array_key_exists($index, $this->session)){
            unset($this->session[$index]);
            return true;
        }else return false;
    }
    /**
     * check is element exist
     * @param string|integer $name index of element session array
     * @return bool if element exist return true else false
     */
    public function __isset($name) {
        if($name=='REMOTE_ADDR' || $name=='HTTP_USER_AGENT' || $name=='COOKIE_ACTIVE') return true;
        return isset($this->session[$name]);
    }
    public function __toString() {
        return 'Session active';
    }
    /**
     * add to URL session id
     */
    private function renameAddress(){
        //uses only in CMS
        $this->kernel->rewrite->addAdditionalVar($this->name,$this->id);

    }
    /**
     * check is cookie active. refered to this same URL, but with additional GET var
     */
    private function checkCookie(){
        if(isset($_GET['TEST_COOKIE_ACTIVE'])){
            if(isset($_COOKIE['TEST_COOKIE_ACTIVE'])){
                $this->cookie=true;
                $this->session['COOKIE_ACTIVE']=true;
            }else{
                $this->cookie=false;
                $this->session['COOKIE_ACTIVE']=false;
            }
        }else{
            setcookie('TEST_COOKIE_ACTIVE','1',time()+20);
            if(strpos($_SERVER['REQUEST_URI'],'?')===false)
                header('Location: '.$_SERVER['REQUEST_URI'].'?TEST_COOKIE_ACTIVE');
            else header('Location: '.$_SERVER['REQUEST_URI'].'&TEST_COOKIE_ACTIVE');
        }
    }
    /**
     * return array of the user's IP address and user agent
     * @return array array of the user's IP address and user agent
     */
    private function userInfo(){
        return array('REMOTE_ADDR'=>$_SERVER['REMOTE_ADDR'],'HTTP_USER_AGENT'=>$_SERVER['HTTP_USER_AGENT']);
    }

    public function destroy() {
	session_destroy();
    }

    /**
     * active or deactive debug mode
     * @param bool $debug
     */
    public function debug($debug=true){
        $this->debug=$debug;
    }
    /**
     * return site URL
     * @return string URL of site
     */
    private function host() {
        $link = pathinfo($_SERVER['SCRIPT_NAME']);
        if($link['dirname'] == '/') $link['dirname'] = NULL;
        if($link['dirname'] == '\\') $link['dirname'] = NULL;
        if($_SERVER['REQUEST_SCHEME']=='https')
            return 'https://'.$_SERVER['SERVER_NAME'].$link['dirname'].'/';
        else return 'http://'.$_SERVER['SERVER_NAME'].$link['dirname'].'/';
    }
}
?>
