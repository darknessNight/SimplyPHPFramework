<?php
/**
 * File containting config class
 */

/**
 * Global debug activate
 */
define('DEBUG', '1');

/**
 * Class containing the elementary data needed to run the project
 */
class config{
    /**
     * default languages of site
     * @var string
     */
    public $lang = 'pl_pl';

    /**
     * user launguage
     * @var string
     */
    public $userLang=null;
    /**
     * default languages of admin panel
     * @var string
     */
    public $adminlang='pl_pl';
    /**
     * default editor of html text
     * @var string
     */
    public $editor = 'ckeditor';

    /**
     * Time session expire
     * @var integer
     */
    public $sessiontime=180;
    /**
     * database's table prefix
     * @var string
     */
    public $table='';
    /**
     * default Theme used on site
     * @var string
     */
    public $defaultTheme='default';
    /**
     * active a debug mode
     * @var boolean
     */
    public $debug = false;

    /**
     * database server
     * @var string
     */
    public $dbserver = 'localhost';

    /**
     * database port
     * @var string
     */
    public $dbport = '27017';

    /**
     * database user name
     * @var string
     */
    public $dbuser = 'wai_web';

    /**
     * database user password
     * @var string
     */
    public $dbpass = 'w@i_w3b';

    /**
     * database name
     * @var string
     */
    public $dbname = 'wai';

    /**
     * error reporting mode
     * @var integer
     */
    public $errorReporting=E_ALL;
    /**
     * mail sender type
     * @var string
     */
    public $mailer='smtp';
    /**
     * phpmailer reverse address
     * @var string
     */
    public $mailfrom = 'pawing4@wp.pl';
    /**
     * phpmailer from name
     * @var string
     */
    public $mailfromname = 'MyCMS';
    /**
     * phpmailer mail authorization type
     * @var bool
     */
    public $mailauth = true;
    /**
     * phpmailer mail user name
     * @var string
     */
    public $mailuser = '';
    /**
     * phpmailer mail user password
     * @var string
     */
    public $mailpass = '';
    /**
     * phpmailer mail server
     * @var string
     */
    public $mailhost = 'smtp.wp.pl';
    /**
     * phpmailer mail port
     * @var integer
     */
    public $mailport = 465;
    /**
     * type of mail secure
     * @var string
     */
    public $mailSecure='ssl';
    /**
     * disabled site type
     * @var boolean
     */
    public $offline = false;
    /**
     * admin logs path
     * @var string
     */
    public $adminlogs='./_logs/admin.log';
    /**
     * error logs path
     * @var string
     */
    public $errorlogs='./_logs/error.log';
    /**
     * path to temporary files
     * @var path
     */
    public $tmpPath='./tmp';
    /**
     * is cookie using to session
     * @var boolean
     */
    public $cookieSession=true;
    /**
     * path to routing file
     */
    public $routingFile;

    /**
     * in KB
     * @var integer
     */
    public $fileMaxSize = 1024;

    /**
     * media path
     */
    public $mediaPath = 'images/';
    public $mediaTypesAllow = array();
    public $smartAddress = true;

    public function __construct() {
        $this->routingFile = CONFIG . 'routing.xml';
	$this->mediaTypesAllow[] = 'image/jpeg';
	$this->mediaTypesAllow[] = 'image/png';
    }
}
?>
