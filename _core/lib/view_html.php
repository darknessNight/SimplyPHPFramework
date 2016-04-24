<?php
/**
 * HTML view class
 */
require_once 'HTML/Table.php';
require_once 'HTML/QuickForm2.php';
require_once CORE.'lib/html_parser.php';

/**
 * This class is responsible for inserting the data passed from PHP to HTML Template
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2014, Paweł Przytarski
 * @version 0.1.4
 */
class ViewHtml extends htmlParser {

    /**
     * add to HTML template Inline CSS style
     * @param string $content CSS style
     */
    public function addStyleInline($content){
        $this->addElementContinue('STYLESHEET', '<style>'.$content.'</style>');
    }
    /**
     * add to HTML template script
     * @param string $content content of script
     * @param string $url url to script
     */
    public function addScript($content=null,$url=null){
        $this->addElementContinue('SCRIPTS', '<script' . (is_null($url) ? '' : ' src="' . $url . '"') . '>' . (is_null($content) ? '' : $content) . '</script>');
    }
    /**
     * add to HTML template title of page
     * @param string $title page title
     */
    public function addPageTitle($title){
        $this->addElementContinue('TITLEPAGE', $title);
    }
    /**
     * add to HTML template title
     * @param string $title title display on page
     */
    public function addTitle($title){//dodaje tytuł strony
        $this->addElementContinue('TITLE', $title);
    }
    /**
     * add to HTML template excluding CSS
     * @param string $link URL of CSS file
     * @throws Exception if URL is incorrect
     */
    public function addStylesheet($link){
        $this->addElementContinue('STYLESHEET', '<link rel="stylesheet" href="'.$link.'" />');
    }
    /**
     * add to HTML template Logo
     * @param string $link URL of logo image
     */
    public function addLogo($link){
        $this->addElementContinue('LOGO', '<img src="'.$link.'" alt="logo" />');
    }
    /**
     * add to HTML template content
     * @param string $value HTML code
     */
    public function addContent($value){
        $this->addElementContinue('CONTENT', $value);
    }
    /**
     * add to HTML template header specified code
     * @param string $value HTML code
     */
    public function addToHeader($value){//dodaje rózne treści do head
        $this->addElementContinue('HEADER', $value);
    }
    /**
     * add to HTML template login form
     * @param string $value Login form HTML code
     */
    public function addLoginForm($value){//dodaje rózne treści do head
        $this->addElement('LoginForm', $value);
    }
    /**
     * add to page meta tags desription tag
     * @param string $value page description
     */
    public function addDescription($value){
        $this->addElement('DESCRIPTION', $value);
    }
    /**
     * add to page meta tags keywords tag
     * @param string $value page keywords
     */
    public function addKeywords($value){
        $this->addElement('KEYWORDS', $value);
    }
}
?>
