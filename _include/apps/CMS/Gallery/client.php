<?php

include_once 'HTML/QuickForm2/Renderer.php';
require_once 'chelper.php';

/**
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2013, Paweł Przytarski
 * @version 0.1.4
 */
class CMS_Gallery_client {

    const elsPerPage = 5;

    private $chelper;
    private $retMess;
    private $checkedImgs = array();

    /**
     *
     * @var kernel
     */
    private $kernel=null;

    function __construct(&$kernel) {
        $this->kernel = &$kernel;
	$this->chelper = new CMS_Gallery\chelper($kernel);
	$this->retMess = $this->kernel->session->cmsGALLERY_retMess;
	if (is_array($this->kernel->session->cmsGALLERY_checkedImgs))
	    $this->checkedImgs = $this->kernel->session->cmsGALLERY_checkedImgs;
	else
	    $this->checkedImgs = array();
    }

    function __destruct() {
	@$this->kernel->session->cmsGALLERY_retMess = $this->retMess;
	@$this->kernel->session->cmsGALLERY_checkedImgs = $this->checkedImgs;
    }

    function listAction($array) {
	if (isset($_POST['onlyChecked']))
	    $array['onlyChecked'] = true;

	//dodawanie/usuwanie z listy zaznaczonych
	if (isset($_POST['checkImgs']) && is_array($_POST['checkImgs'])) {
	    if (!isset($array['onlyChecked'])) {
		foreach ($_POST['checkImgs'] as $el) {
		    $ret = array_search($el, $this->checkedImgs);
		    if ($ret === false) {
			$this->checkedImgs[] = $el;
		    }
		}
	    } else {
		foreach ($_POST['checkImgs'] as $el) {
		    $ret = array_search($el, $this->checkedImgs);
		    if ($ret !== false) {
			$this->checkedImgs[$ret] = null;
			unset($this->checkedImgs[$ret]);
		    }
		}
	    }
	}

	//warunki sortowania odczytane z adresu
	if (!isset($array['page']) || !is_string($array['page']))
	    $array['page'] = 1;

	//przypisanie wartości z sessji, jeśli nie ma podanych
	if (!isset($array['sort']))
	    $array['sort'] = @$this->kernel->session->CMS_Gallery_sortSearch['sort'];
	if (!isset($array['sortType']))
	    $array['sortType'] = @$this->kernel->session->CMS_Gallery_sortSearch['sortType'];
	if (!isset($array['name']))
	    $array['name'] = @$this->kernel->session->CMS_Gallery_sortSearch['name'];
	if (!isset($array['author']))
	    $array['author'] = @$this->kernel->session->CMS_Gallery_sortSearch['author'];

	//przyporządkowywanie wartości
	switch (@$array['sort']) {
	    case 'author':
	    case 'name':break;
	    default: $array['sort'] = 'name';
	}
	switch (@$array['sortType']) {
	    case 'desc':case -1:$array['sortType'] = -1;
		break;
	    case 'asc':case 1:
	    default: $array['sortType'] = 1;
	}

	//przygotowanie tablicy z zapytaniem
	$search = $this->chelper->prepareSearch($array);

	if (empty($search))
	    $search = null;
	if (isset($array['nosearch']))
	    $ret = $this->getImgList($array['page'], [], ['name' => 1], isset($array['onlyChecked']), !isset($array['onlyChecked'])); //pobranie danych
	else {
	    $ret = $this->getImgList($array['page'], $search, [$array['sort'] => $array['sortType']], isset($array['onlyChecked']), !isset($array['onlyChecked']));
	    if (isset($array['onlyChecked']) && empty($this->checkedImgs)) {
		$ret[0] = '<p>No elements</p>';
		$ret[1] = '[1]';
	    }
	}

	//wybór sposobu przezentacji danych
	if (isset($array['AJAX'])) {
	    $this->kernel->view->notView();
	    header("Content-Type: application/json");
	    echo json_encode(['table' => $ret[0], 'pag' => $ret[1], 'hints' => $this->chelper->getHints($search, (isset($array['onlyChecked']) ? $this->checkedImgs : array()))]);
	} else {
	    $this->kernel->view->setVar('CONTENT', $ret[0]);
	    $this->kernel->view->setVar('LEFT_COLUMN', '<div>{NAV_GALLERY}</div>');
	    if (!empty($ret[1]))
		$this->kernel->view->setVar('PAGINATION', $ret[1]);
	    if(!isset($array['nosearch']))
		$this->createListForms($array);
	}

	//zapamiętanie wartości do sesji
	unset($array['nosearch']);
	unset($array['onlyChecked']);
	$this->kernel->session->CMS_Gallery_sortSearch = $array;
    }

    function addAction() {
	if (isset($_POST['send'])) {
	    $data = $this->addImage();
	    if (is_string($data))
		$this->retMess = $data;
		else {
		    $this->kernel->view->setVar("CONTENT", '<img src="' . $this->kernel->host() . $data['src'] . '"/><p>' . $data['name']);
		$this->kernel->view->setVar("CONTENT_TITLE", 'SUCCESSFULL ADDED');
		return;
	    }
	}
	$this->createAddImgForm();
    }

    function addImage() {
	if (isset($_POST['name']) && isset($_POST['author']) && isset($_POST['watermark']) && !empty($_FILES) && is_string($_POST['name']) && is_string($_POST['author']) && is_string($_POST['watermark']) && strlen(trim($_POST['author'])) > 0 && strlen(trim($_POST['watermark'])) > 0 && strlen(trim($_POST['name'])) > 0 && is_string ($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']) ){
		global $_APPS;
	    if (strlen($_APPS['CMS_Users']->GetActiveUserID()) <= 0 && $_APPS['CMS_Users']->checkNameExists($_POST['name']))
		return 'Name already occupied';
	    if (is_string($_FILES['file']['name']) && strlen($_FILES['file']['name']) > 0 && $_FILES['file']['size'] > 0 && $_FILES['file']['error'] == 0 && file_exists($_FILES['file']['tmp_name'])) {
		if ($_FILES['file']['size'] > $this->kernel->config->fileMaxSize * 1024)
		    return 'File too large';

		$flag = true;
		foreach ($this->kernel->config->mediaTypesAllow as $val) {
		    if ($val == $_FILES['file']['type']) {
			$flag = false;
			break;
		    }
		}
		if ($flag)
		    return 'Incorrect file type';
		$name = strip_tags($_POST['name']);
		$author = strip_tags((strlen($_APPS['CMS_Users']->userName) > 0) ? $_APPS['CMS_Users']->userName : $_POST['author']);
		$watermark = strip_tags($_POST['watermark']);
		$type = (isset($_POST['privType']) && is_string($_POST['privType']) ? $_POST['privType'] : 'pub');
		$private = ($type == 'pub' ? 0 : 1);
		if (strlen($_APPS['CMS_Users']->GetActiveUserID()) <= 0)
		    $private = 0;

		$id = $this->chelper->AddImg(['name' => $name, 'author' => $author, 'private' => $private]);

		$split = explode('.', $_FILES['file']['name']);
		$imgName = $this->chelper->saveImage($_FILES['file']['tmp_name'], $this->kernel->config->mediaPath, $id, $split[count($split) - 1], $watermark);
		if (strlen($imgName) > 0)
		    return ['name' => $name, 'src' => $imgName];
		else
		    return 'Image adding error.';
	    }
	    else
		return 'File not send';
	}
	else
	    return 'Incomplete data';
    }

    private function createAddImgForm() {
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
	    'action' => $this->kernel->rewrite->generateURL('gallery:add', []), 'enctype' => 'multipart/form-data']);

	$form->addElement('text', 'name', ['placeholder' => 'Name', 'required' => 'required'])->setLabel('Name: ');
	$form->addElement('file', 'file', ['required' => 'required', 'accept' => '.png,.jpg,.jpeg'])->setLabel('File: ');

	global $_APPS;
	if (isset($_APPS['CMS_Users']) && strlen($_APPS['CMS_Users']->userName) > 0) {
	    $form->addElement('radio', 'privType', ['value' => 'priv'], ['content' => 'Private'])->setLabel('Private type: ');
	    $form->addElement('radio', 'privType', ['value' => 'pub', 'checked' => 'checked'], ['content' => 'Public']);
	    $form->addElement('text', 'author', ['placeholder' => 'Author', 'required' => 'required', 'value' => $_APPS['CMS_Users']->userName, 'readonly' => 'readonly'])->setLabel('Author: ');
	} else {
	    $form->addElement('text', 'author', ['placeholder' => 'Author', 'required' => 'required'])->setLabel('Author: ');
	}

	$form->addElement('text', 'watermark', ['placeholder' => 'Watermark', 'required' => 'required'])->setLabel('Watermark: ');

	$form->addElement('submit', 'send', ['value' => 'Add']);

	$this->kernel->view->setVar('CONTENT', '<div class="form">' . ($this->retMess != '' ? '<div class="mess">' . $this->retMess . '</div>' : '') . $form->render($renderer) . '</div>');
	$this->kernel->view->setVar("CONTENT_TITLE", 'ADD IMAGE FORM');
	$this->retMess = '';
    }

    private function getImgList($current, array $params = null, array $sort = null, $checked = false, $state = true) {
	//wyszukanie potrzebnych stron
	if (!is_array($params)) {
	    $params = ["private" => "0"];
	}

	$pages = $this->chelper->getImgs($params, ['_id' => 1, 'name' => 1, 'author' => 1, 'private' => 1], ($checked == TRUE ? $this->checkedImgs : array()));
	$pagesCount = $pages->count();

	if (empty($sort)) {
	    $sort = ['createdDate' => -1];
	}

	//ograniczenie listy wyników i sortowanie
	$pages->sort($sort);
	$pages->skip(($current - 1) * $this::elsPerPage);
	$pages->limit($this::elsPerPage);


	$table = new HTML_Table(['class' => 'standard']);
	$table->addRow(['Obraz', 'Nazwa', 'Autor', 'Zaznacz'], null, 'th');

	foreach ($pages as $page) {
	    $imgEl = '<a href="' . kernel::host() . $this->kernel->config->mediaPath . $page['_id'] . '_watermark">'
		    . '<img src="' . kernel::host() . $this->kernel->config->mediaPath . $page['_id'] . '_small" alt="' . $page['name'] . '"/></a>';
	    $table->addRow([$imgEl, $page['name'], $page['author'] . ($page['private'] == 1 ? '(private img)' : ''),
		'<input type="checkbox" name="checkImgs[]" value="' . $page['_id'] . '" ' . ($state && \array_search($page['_id'], $this->checkedImgs) !== FALSE ? ' checked="checked" ' : '') . '/>']);
	}

	$pag = $this->chelper->paginateList($this::elsPerPage, $current, $pagesCount);

	return ['<form method="post" action="" id="listTable">' . $table->toHtml() . '<input type="submit" value="Send"/></form>', $pag];
    }

    private function createListForms(array $array) {
	//sposób wyświetlania formularza
	$renderer = HTML_QuickForm2_Renderer::factory('default')
		->setOption([
		    'group_hiddens' => true,
		    'group_errors' => true,
		    'required_note' => '<strong>Note:</strong> Required fields are marked with an asterisk (<em>*</em>).'
		])
		->setTemplateForClass(
		'HTML_QuickForm2_Element', '<div class="label<qf:required> required</qf:required>">{label}</div>' .
		'{element}<br/>'
	);

	//formularz sortowania
	$array['page'] = 1;
	$form = new HTML_QuickForm2("sortForm", 'get', ['action' => $this->kernel->rewrite->generateURL($this->kernel->rewrite->current, [])]);

	$selSort = $form->addSelect('sort');
	$selSort->addOption('Name', 'name');
	$selSort->addOption('Author', 'author');
	$selSort->setLabel('Sort by' . ':');

	$form->addElement('radio', 'sortType', ['value' => 'desc'], ['content' => 'Desc']);
	$form->addElement('radio', 'sortType', ['value' => 'asc', 'checked' => 'checked'], ['content' => 'Asc']);
	$form->addElement('submit', '', ['value' => 'Sort']);

	//formularz wyszukiwania
	$form2 = new HTML_QuickForm2("searchForm", 'get', ['action' => $this->kernel->rewrite->generateURL($this->kernel->rewrite->current, [])]);
	$form2->addElement('text', 'name', ['id' => 'searchName', 'value' => $array['name']], ['label' => 'Name' . ':']);
	$form2->addElement('text', 'author', ['id' => 'searchAuthor', 'value' => $array['author']], ['label' => 'Author' . ':']);
	$form2->addElement('submit', '', ['value' => 'Search']);
	if (isset($array['onlyChecked']))
	    $form2->addElement('hidden', 'onlyChecked');

	$this->kernel->view->addScript(null, kernel::host() . APPS . 'CMS/gallery/Additional/AJAX.js');

	$this->kernel->view->setVar('CONTENT', '<div class="hiddenMenu leftMenu">' . 'Sorting'
		. '<div class="hide">' . $form->render($renderer) . '</div></div>'
		. '<div class="hiddenMenu rightMenu">' . 'Search'
		. '<div class="hide">' . $form2->render($renderer) . '</div></div>'
		. $this->kernel->view->getVar('CONTENT'));
    }

}
?>
