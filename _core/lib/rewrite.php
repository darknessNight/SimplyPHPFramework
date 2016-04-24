<?php
/**
 * rerwite module using routing
 */

/**
 * Description of rewrite
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2014, Paweł Przytarski
 * @version 0.3.1
 */
class Rewrite {

    /**
     * array with rewrite rules
     * @var array
     */
    private $rules=array();
    /**
     * array with additional vars
     * @var array
     */
    private $additional=array();
    /**
     * information about active rewrite adressess
     * @var bool
     */
    private $rewrite=true;
    /**
     * var with name of action, if is NULL, action is not specified
     * @var string|NULL
     */
    public $action;
    /**
     * var with name of app, if is NULL, app  is not specified
     * @var string|NULL
     */
    public $app;

    /**
     * current rule id
     * @var string
     */
    public $current;

    /**
     * variables from URL
     * @var array
     */
    public $vars=array();
    /**
     * information about find address in rules
     * @var bool
     */
    public $search=false;
    /**
     * read rules from file and
     * @param bool $rewrite active of rewriting links
     * @throws Exception if can't read file with rules
     */
     function __construct($rulesFile, $strict = false, $rewrite = true) {
	$this->rewrite=$rewrite;
        if(!$this->readRules($rulesFile,$strict)){
            throw new Exception('Can\'t read rewrite rules');
        }

	if (isset($_GET['_app']) && isset($_GET['_action'])) {
	    $this->app = $_GET['_app'];
	    $this->action = $_GET['_action'];
	    unset($_GET['_app']);
	    unset($_GET['_action']);
	    $this->vars = $_GET;
	    $this->search = true;

	    foreach ($this->rules as $key => $rule) {
		if ($this->app == $rule['__app'] && $this->action == $rule['__action']) {
		    $this->current = $key;
		    return;
		}
	    }
	} else
	//check is rewrite URL active
        if (isset($_SERVER['REDIRECT_URL']) || isset($_GET['action'])) {
	    $path=null;
            //deleting from the URL a path containing the system
            $info=pathinfo($_SERVER['SCRIPT_NAME']);
            if($info['dirname']!='/' && $info['dirname']!='\\')
                $path = str_replace($info['dirname'], '', (isset($_GET['action']) ? $_GET['action'] : $_SERVER['REDIRECT_URL']));
	    else $path=$_SERVER['REDIRECT_URL'];
            //if URL-path is null or '/' is home page
            if(strlen($path)=='' || strlen($path)=='/'){
                $this->search=true;
            }else{
                $this->search=false;
                //check the routing rules in the search path corresponding URL-path
                foreach ($this->rules as $key => $rule) {
		    $rule['__key'] = $key;
		    //if find rules break searching
                    //
                    //default vars
                    $uriVars = array();
		    //reading query string
		    if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
			$tmp = explode('&', $_SERVER['REDIRECT_QUERY_STRING']);
			foreach ($tmp as $var) {
			    $strpos = strpos($var, '=');
			    if ($strpos > 0) {
				$name = substr($var, 0, $strpos);
				$value = substr($var, $strpos + 1);
				$uriVars[$name] = $value;
			    } else {
				$uriVars[$var] = '';
			    }
			}
		    }
		    //count the number of vars in pattern
                    $varc=0;
                    //containg names of vars in pattern
                    $vars=array();
                    // get names of vars from pattern
                    $vars=preg_replace('/[\w\W]*?{([A-Za-z0-9]+?)}[\w\W]*?/','\\1|',$rule['path']);
                    $vars=explode('|', $vars);
                    //prepare patten to use and get default vars
                    foreach($rule as $key => $val){
                        $info=explode(':', $key);
                        switch($info[0]){
                            case 'pattern':$rule['path']=str_replace('{'.$info[1].'}', '('.$val.')', $rule['path']);$varc++; break;
                            case 'default':$uriVars[$info[1]] = $val;
				break;
			}
                    }


		    //if not specified regular expression to vars, to var assigned standard expression
                    $c=0;
                    $rule['path']=preg_replace('/{[a-zA-Z0-9]+}/','(.*)',$rule['path'],-1,$c);
                    $varc+=$c;
                    //if pattern is the same as URL-path end the searching and set all parameters
                    if ($rule['path'] == $path) {
			$this->current = $rule['__key'];
			$this->app=$rule['__app'];
                        $this->action=$rule['__action'];
                        $this->vars = $uriVars;
			$this->search=true;
                        break;
                    //else check as regular expression
                    }elseif(preg_match('/^'.addcslashes ($rule['path'], '/').'$/', $path)){
                        //get from path vars

                        //prepare for regular expression
                        $tmp='';
                        for($i=1;$i<$varc+1;$i++)
                            $tmp.='\\'.$i.'|';
                        $tmp=preg_replace('/'.addcslashes ($rule['path'], '/').'/',$tmp,$path);
                        //Now variables are stored in the following form: var1|var2|var3|
                        //then must be stored as array
                        $tmp=explode('|', $tmp);
                        //Now the variables are assigned to the names in the array
                        for($i=0;$i<$varc;$i++){
                            if($tmp[$i]!='')
                                $uriVars[$vars[$i]] = $tmp[$i];
			}

			$this->current = $rule['__key'];
			$this->app=$rule['__app'];
                        $this->action = $rule['__action'];
			$this->vars = $uriVars;
			$this->search=true;
                        break;
                    }
                }
	    }
        }
    }
    /**
     * read rewrite rules from file
     * @param string $filename path to file with rewrite rules
     * @param boolean $strict if true function exit if failure, if false function go to exit normal
     * @return boolean
     */
    private function readRules($filename,$strict=false){
        if(!file_exists($filename)) return false;
        $state=true;
        $rules=simplexml_load_file($filename);
        foreach($rules as $rule){
            if(isset($rule['reffered'])){
                $file=$rule['reffered']->__toString();
                if(preg_match('/__([A-Z0-9]+)__/',$file)){
                        $tmp=preg_replace ('/(.*)__([A-Z0-9]+)__(.*)/', '\\2;',$file );
                        $tmp=explode(';',$tmp);
                        foreach($tmp as $t){
                            if($t=='') continue;
                            $defines=get_defined_constants();
                            if(defined($t)){
                                $file=str_replace('__'.$t.'__', $defines[$t], $file);
                            }elseif(defined('__'.$t.'__')){
                                $file=str_replace('__'.$t.'__', $defines['__'.$t.'__'], $file);
                            }
                        }
                }
                if($strict){
                    if(!$this->readRules($file,$strict)) return false;
                }else{
                    if(!$this->readRules($file)) $state=false;
                }
                continue;
            }
            $tmp=array();
            $childs=$rule->children();
            foreach($childs as $child){
                $tmp[$child->getName().':'.$child['key']->__toString()]=$child->__toString();
            }
            $tmp['path']=$rule['path']->__toString();
            $tmp['default:_controller']=explode(':',$tmp['default:_controller']);
            $tmp['__app']=$tmp['default:_controller'][0];
            $tmp['__action']=$tmp['default:_controller'][1];
            unset($tmp['default:_controller']);
            $this->rules[$rule['id']->__toString()]=$tmp;
        }
        return $state;
    }
    /**
     * add vars to add all links
     * @param string $name name of var
     * @param mixed $value value of var
     */
    public function addAdditionalVar($name,$value){
        $this->additional[$name]=$value;
    }
    /**
     * generate link from specifited array with vars for specified rule
     * @param string $rule name of rule
     * @param array $array array where name of vars is indexs and values is values
     * @return string|bool generated link or false if failure
     */
    public function generateURL($rule,$array){
        if(!isset($this->rules[$rule])) return false;
        if ($this->rewrite) {//ładny adres
	    $URL = $this->rules[$rule]['path'];
	    foreach ($array as $key => $value) {//zamiana zmiennych we wzorze
		if (isset($this->rules[$rule]['pattern:' . $key])) {
		    if (!preg_match('/^' . addcslashes($this->rules[$rule]['pattern:' . $key], '/') . '$/', $value))
			return false;
		    $URL = str_replace('{' . $key . '}', $value, $URL);
		    unset($array[$key]);
		}
	    }
	    $array+=$this->additional;
	    if (count($array) > 0) {//dopisywanie do adresu zmiennych spoza wzorca
		$URL.='?';
                foreach ($array as $name => $value) {
                    $URL.=$name.'='.urldecode($value).'&';
                }
                $URL = substr($URL, 0, strlen($URL) - 1);
	    }
            return $this->host().substr($URL, 1, strlen($URL));
        } else {//standarowy obraz
	    $URL=$this->host().'?'.'_app='.$this->rules[$rule]['__app'].'&_action='.$this->rules[$rule]['__action'];
            $array+=$this->additional;
	    foreach ($this->rules[$rule] as $key => $value) {//zamiana zmiennych we wzorze
		if (strstr($key, 'default:') !== false && !isset($array[explode(':', $key)[1]])) {
		    $array[explode(':', $key)[1]] = $value;
		}
	    }
	    foreach ($array as $name => $value) {
                $URL.='&' . $name . '=' . urldecode($value);
	    }
            return substr($URL, 0, strlen($URL));
	}
    }
    /**
     * return website URL
     * @return string website folder URL
     */
    public static function host() {
	$link = pathinfo($_SERVER['SCRIPT_NAME']);
        if($link['dirname'] == '/') $link['dirname'] = NULL;
        if($link['dirname'] == '\\') $link['dirname'] = NULL;
        if($_SERVER['REQUEST_SCHEME']=='https')
            return 'https://' . $_SERVER['HTTP_HOST'] . $link['dirname'] . '/';
	else
	    return 'http://' . $_SERVER['HTTP_HOST'] . $link['dirname'] . '/';
    }
}

?>
