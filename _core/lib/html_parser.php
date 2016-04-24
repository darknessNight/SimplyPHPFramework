<?php
/**
 * HTML parser
 */

/**
 * This class is responsible for inserting the data passed from PHP to HTML Template
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2014, Paweł Przytarski
 * @version 0.3.4
 */
class htmlParser{
    /**
     * begin template tags
     * @var string
     */
    protected $tagOpen = '{';

    /**
     * end template tags
     * @var string
     */
    protected $tagClose = '}';

    /**
     * begin template safe tags
     * @var string
     */
    protected $safeTagOpen = '{{';

    /**
     * end template safe tags
     * @var string
     */
    protected $safeTagClose = '}}';

    /**
     * begin template tags comments
     * @var string
     */
    protected $commentTagOpen = '{*';

    /**
     * end template tags comments
     * @var string
     */
    protected $commentTagClose = '*}';

    /**
     * theme dir
     * @var string
     */
    protected $dir;

    /**
     * current theme name
     * @var string
     */
    protected $theme;

    /**
     * content of HTML theme
     * @var string
     */
    protected $content = '';

    /**
     * if true HTML was displayed
     * @var boolean
     */
    protected $view = false;

    /**
     * array with variables to insert into theme
     * @var array
     */
    protected $vars = array();

    //-----------------------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * load theme file
     * @param string $dir path to themes dir
     * @param string $theme name of theme
     * @throws Exception if path to themes dir doesn't exist or theme files doesn't exists
     */
    public function __construct($dir,$theme) {
        $this->dir = $dir;
	$this->theme=$theme;
        if(!file_exists($this->dir)) throw new Exception ('This dir doesn\'t exists');
        if(($data=file_get_contents($dir.'/'.$theme.'/theme.html'))===false) throw Exception('Theme file does\'t exists');
        $this->content = str_replace('{THEME_DIR}', kernel::host() . THEMES . $theme, $data);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * Display HTML code if can
     */
    public function __destruct() {
        if (!$this->view)
	    $this->view();
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * Display page clearing the it of additional tags
     */
    public function view(){
        header('Content-Type: text/html');

	//wstawianie zmiennych do szablonu
	foreach ($this->vars as $name => $value) {
            if(is_array($value)){
                foreach($value as $key => $val){
                    $this->addElementContinue($name, $value);
                }
            }else{
                $this->addElement($name, $value);
            }
        }

	//wyszukiwanie wszystkich if-ów
	if (preg_match_all('#\\{IF %([A-Za-z0-9\\-_]{1,})%\\}.*?({ELSE}){0,1}{/IF}#smu', $this->content, $matches)) {
	    foreach ($matches[1] as $key => $match) {
		if (!$this->issetVar($match)) {//sprawdza czy podana zmienna istnieje
		    if (strpos($matches[0][$key], '{ELSE}') !== false)//w zależności od istenia w warunku ELSE stosuje inna zamianę
			$this->content = preg_replace('#\\{IF %' . $match . '%\\}.*?{ELSE}(.*?){\\/IF}#smu', '$1', $this->content);
		    else
			$this->content = preg_replace('#\\{IF %' . $match . '%\\}.*?{\\/IF}#smu', '', $this->content);
		} else {
		    if (strpos($matches[0][$key], '{ELSE}') !== false)
			$this->content = preg_replace('#\\{IF %' . $match . '%\\}(.*?){ELSE}.*?{\\/IF}#smu', '$1', $this->content);
		}
	    }
	}

	//usuwanie nadmiarowych znaczników z kodu
	$this->content = preg_replace('/' . addcslashes($this->tagOpen, '*{}') . '(.*?)' . addcslashes($this->tagClose, '^*{}') . '/', '', $this->content);
	$this->content = preg_replace('/' . addcslashes($this->commentTagOpen, '*{}') . '(.*?)' . addcslashes($this->commentTagClose, '^*{}') . '/', '', $this->content);
	$this->content = str_replace($this->safeTagOpen, $this->tagOpen, $this->content);
	$this->content = str_replace($this->safeTagClose, $this->tagClose, $this->content);

	//usuwanie zbędnych znaków
	if (defined('DEBUG'))
	    $this->content = preg_replace('#\n^\s*$#smu', '', $this->content);
	else
	    $this->content = preg_replace('#\s{2,}#smu', '', str_replace("\n", '', $this->content));
	echo $this->content;
	$this->view = true;
    }
//----------------------------------------------------------------------------------------------------------------------------

    /**
     * add variable to insert into theme
     * @param mixed $name
     * @param mixed $value
     */
    public function setVar($name,$value){
        return $this->vars[$name]=$value;
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * get value of variable to insert into theme
     * @param mixed $name
     */
    public function getVar($name){
        if (!isset($this->vars[$name]))
	    return NULL;
	else
	    return $this->vars[$name];
    }

    //-----------------------------------------------------------------------------------------------------------------------

    public function issetVar($name){
        return isset($this->vars[$name]);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    public function __set($name,$value){
        return $this->setVar($name, $value);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    public function __get($name) {
        return $this->getVar($name);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    public function __isset($name) {
        return $this->issetVar($name);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * safe template tags in specified string and return safe string
     * @param string $str HTML code to safe
     * @return string safe string
     */
    public function safeTags($str){
        $str = str_replace($this->tagOpen, $this->safeTagOpen, $str);
	$str=str_replace($this->tagClose,$this->safeTagClose,$str);
        return $str;
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * add to HTML template code. But tag is replaced, and doesn't exists any more in HTML template
     * @param string $name tag text to replace
     * @param string $value replacement value
     */
    protected function addElement($name, $value) {
	$this->content=str_replace($this->tagOpen.$name.$this->tagClose,$value,$this->content);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * add to HTML template code. But tag isn't replaced, and exists in HTML template
     * @param string $name tag text to replace
     * @param string $value replacement value
     */
    protected function addElementContinue($name, $value) {
	$this->content=str_replace($this->tagOpen.$name.$this->tagClose,$value.$this->tagOpen.$name.$this->tagClose,$this->content);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * return code of like buttons. Small version
     * @return string code of like buttons
     */
    public function getShareButtonsSmall(){
        return '<div class="addthis_toolbox addthis_default_style "><a class="addthis_button_preferred_1"></a><a class="addthis_button_preferred_2"></a><a class="addthis_button_preferred_3"></a><a class="addthis_button_preferred_4"></a><a class="addthis_button_compact"></a><a class="addthis_counter addthis_bubble_style"></a></div><script type="text/javascript">var addthis_config = {"data_track_addressbar":true};</script><script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-51d6db460c255693"></script>';
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * return code of like buttons. Normal version
     * @return string code of like buttons
     */
    public function getShareButtons(){
        return '<div class="addthis_toolbox addthis_default_style "><a class="addthis_button_facebook_like" fb:like:layout="button_count"></a><a class="addthis_button_tweet"></a><a class="addthis_button_pinterest_pinit"></a><a class="addthis_counter addthis_pill_style"></a></div><script type="text/javascript">var addthis_config = {"data_track_addressbar":true};</script><script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-51d6db460c255693"></script>';
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * HTML code isn't display
     */
    public function notView(){
        $this->view=true;
    }

    //-----------------------------------------------------------------------------------------------------------------------
}
?>
