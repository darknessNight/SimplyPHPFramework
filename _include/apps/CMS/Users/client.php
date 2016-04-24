<?php

require_once 'HTML/QuickForm2.php';
require_once 'HTML/QuickForm2/Renderer.php';
require_once 'chelper.php';

class CMS_Users_client {

    public $userName = '';
    private $userId = '';
    private $retMess = "";
    private $chelper;

    /**
     *
     * @var kernel
     */
    private $kernel = null;

    public function getActiveUserId() {
	return $this->userId;
    }

    function __construct(&$kernel) {
        $this->kernel = &$kernel;
	$this->chelper = new CMS_Users\chelper($kernel);

	if (isset($this->kernel->session->cmsUSERS_userId) && isset($this->kernel->session->cmsUSERS_userName) && isset($this->kernel->session->cmsUSERS_retMess)) {
	    $this->userId = $this->kernel->session->cmsUSERS_userId;
	$this->userName = $this->kernel->session->cmsUSERS_userName;
	$this->retMess = $this->kernel->session->cmsUSERS_retMess;
	}

	$this->createLoginForm();
    }

    function __destruct() {
	@$this->kernel->session->cmsUSERS_userId = $this->userId;
	@$this->kernel->session->cmsUSERS_userName = $this->userName;
	@$this->kernel->session->cmsUSERS_retMess = $this->retMess;
    }

    function LoginAction($array) {
	$data = $this->chelper->Login($_POST);
	if (!empty($data) && is_string($_POST['name']) && strlen($_POST['name']) > 0 && is_string($_POST['encr']) && strlen($_POST['encr']) > 0) {
	    $this->userName = $data['name'];
	    $this->userId = $data['_id'];
	}
	else
	    $this->retMess = "Incorrect data";
	echo $_SERVER['HTTP_REFERER'];
	header("Location: " . $_SERVER['HTTP_REFERER']);
    }

    function LogoutAction($array) {
	$this->userId = "";
	$this->userName = "";
	$this->kernel->session->destroy();
	echo $_SERVER['HTTP_REFERER'];
	header("Location: " . $_SERVER['HTTP_REFERER']);
    }

    function ProfileAction() {
	$this->kernel->view->setVar("CONTENT", "Your login: " . $this->userName);
	$this->kernel->view->setVar("CONTENT_TITLE", "Your profile");
    }

    function usProfileAction() {
	$this->kernel->view->setVar("CONTENT", "FUNCTION NON AVAILABLE");
    }

    function RegisterAction() {
	if (isset($_POST['rname']) && isset($_POST['rmail']) && isset($_POST['rencr']) && is_string($_POST['rname']) && is_string($_POST['rmail']) && is_string($_POST['rencr'])) {
	    $_POST['rname'] = strip_tags($_POST['rname']);
	    $_POST['rmail'] = strip_tags($_POST['rmail']);
	    $data = $this->chelper->Register($_POST);
	    if ($data != '')
		$this->retMess = $data;
	    else {
		$this->kernel->view->setVar('CONTENT', 'Successfully registered: ' . $_POST['rname']);
		$this->kernel->view->setVar("CONTENT_TITLE", 'REGISTER FORM');
		return;
	    }
	}
	$this->createRegisterForm();
    }

    private function createRegisterForm() {
	$renderer = HTML_QuickForm2_Renderer::factory('default')
		->setOption([
		    'group_hiddens' => true,
		    'group_errors' => true,
		    'required_note' => '<strong>Note:</strong> Required fields are marked with an asterisk (<em>*</em>).'
		])
		->setTemplateForClass(
		'HTML_QuickForm2_Element', '<span class="label<qf:required> required</qf:required>">{label}</span>' .
		'{element}<br/>'
	);

	//formularz sortowania
	$form = new HTML_QuickForm2("sortForm", 'post', [
	    'action' => $this->kernel->rewrite->generateURL('users:register', [])]);

	$form->addElement('text', 'rname', ['placeholder' => 'Login', 'required' => 'required'])->setLabel('Login: ');
	$form->addElement('text', 'rmail', ['placeholder' => 'example@example.com', 'required' => 'required'])->setLabel('E-mail: ');
	$form->addElement('password', 'rencr', ['placeholder' => "Password", 'required' => 'required'])->setLabel('Password: ');
	$form->addElement('password', 'rencrR', ['placeholder' => "Password", 'required' => 'required'])->setLabel('Repeat password: ');
	$form->addElement('submit', 'send', ['value' => 'Register']);

	$this->kernel->view->setVar('CONTENT', '<div class="form">' . ($this->retMess != '' ? '<div class="mess">' . $this->retMess . '</div>' : '') . $form->render($renderer) . '</div>');
	$this->kernel->view->setVar("CONTENT_TITLE", 'REGISTER FORM');
	$this->retMess = '';
    }

    private function createLoginForm() {
	//sposób wyświetlania formularza
	if (strlen($this->userId) == 0) {
	    $renderer = HTML_QuickForm2_Renderer::factory('default')
		    ->setOption([
		    'group_hiddens' => true,
		    'group_errors' => true,
		    'required_note' => '<strong>Note:</strong> Required fields are marked with an asterisk (<em>*</em>).'
		])
		->setTemplateForClass(
		'HTML_QuickForm2_Element', '<span class="label<qf:required> required</qf:required>">{label}</span>' .
		'{element}<br/>'
	);

	//formularz sortowania
	$form = new HTML_QuickForm2("loginForm", 'post', [
		'action' => $this->kernel->rewrite->generateURL('users:login', [])]);

	$form->addElement('text', 'name', ['placeholder' => 'Login', 'required' => 'required'])->setLabel('Login: ');
	    $form->addElement('password', 'encr', ['placeholder' => "Password", 'required' => 'required'])->setLabel('Password: ');
	    $form->addElement('submit', 'send', ['value' => 'Login']);

	$this->kernel->view->setVar('LOGINFORM', ($this->retMess != '' ? '<div class="mess">' . $this->retMess . '</div>' : '') . $form->render($renderer) . '<div class="links"><a href="' . $this->kernel->rewrite->generateUrl('users:register', []) . '">Register</a></div>');
	} else {
	    $this->kernel->view->setVar('LOGINFORM', 'Hello, ' . $this->userName . '<br/><a href="' . $this->kernel->rewrite->generateUrl('users:profile', []) . '">Profile</a><br/><a href="' . $this->kernel->rewrite->generateUrl('users:logout', []) . '">Logout</a>');
	}
	$this->retMess = '';
    }

public function checkNameExists($name){
    if (is_string($name) && strlen(trim($name)) > 0)
	    return $this->chelper->checkNameExists($name);
	else
	    return false;
    }

}
?>
