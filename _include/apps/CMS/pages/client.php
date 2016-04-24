<?php

require_once 'HTML/QuickForm2/Renderer.php';
require_once 'chelper.php';

/**
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2013, Paweł Przytarski
 * @version 0.1.4
 */
class cms_pages_client{

    const elsPerPage = 10;

    private $helper;
    private $lang;

    /**
     *
     * @var kernel
     */
    private $kernel=null;

    public function __construct(&$kernel) {
        $this->kernel = &$kernel;
	if (!include('lang/lang_' . $this->kernel->config->userLang . '.php'))
	    if (!include('lang/lang_pl_pl.php')) {
		$lang = array();
	    }
	$this->lang = $lang;
	$this->helper = new cms_pages_chelper($kernel);
    }

    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * Load homepage
     * @param params $array
     */
    public function indexAction(array $array = NULL) {
	$pages = $this->helper->getSinglePage('home', '1');

	$page = NULL;
	//wyszukiwanie odpowiedniej wersji językowej
	foreach ($pages as $p) {
	    if ($page == NULL)
		$page = $p;
	    if($this->kernel->config->userLang==$p['lang']) {
		$page = $p;
		break;
	    }
	    if ($this->kernel->config->lang == $p['lang'])
		$page = $p;
	}

	if ($page == NULL) {//jeśli nie wczytano żadnej strony
	    $this->kernel->setResponseCode(404);
	    return;
	}

	//dodanie informacji na stronę
	$this->kernel->view->setVar('TITLE', $page['name']);
        $this->kernel->view->setVar('DESCRIPTION', $page['description']);
        $this->kernel->view->setVar('KEYWORDS', $page['keywords']);
        $this->kernel->view->setVar('CONTENT_TITLE', $page['name']);
	$this->kernel->view->setVar('CONTENT', $page['content']);
    }
    //-----------------------------------------------------------------------------------------------------------------------

    public function listAction(array $array) {
	if (!isset($array['page']) || !is_string($array['page']))
	    $array['page'] = 1;
	//przyporządkowywanie wartości
	switch (@$array['sort']) {
	    case 'createdDate':
	    case 'author':
	    case 'name':break;
	    default: $array['sort'] = 'createdDate';
	}
	switch (@$array['sortType']) {
	    case 'desc':$array['sortType'] = -1;
		break;
	    case 'asc':
	    default: $array['sortType'] = 1;
	}

	//przygotowanie tablicy z zapytaniem
	$search = $this->helper->prepareSearch($array);

	if (empty($search))
	    $search = null;
	$ret = $this->getPageList($array['page'], $search, [$array['sort'] => $array['sortType']]); //pobranie danych

	if (isset($array['AJAX'])) {//wybór sposobu przezentacji danych
	    $this->kernel->view->notView();
	    header("Content-Type: application/json");
	    echo json_encode(['table' => $ret[0], 'pag' => $ret[1], 'hints' => $this->helper->getHints($search)]);
	} else {
	    $this->kernel->view->setVar('CONTENT', $ret[0]);
	    if (!empty($ret[1]))
		$this->kernel->view->setVar('PAGINATION', $ret[1]);
	    $this->createListForms($array);
	}
    }
    //-----------------------------------------------------------------------------------------------------------------------

    /**
     * Load single page
     * @param params $array
     */
    public function pageAction($array) {
	$pageData = null;
	if (is_string($array['id']))
	    $pageData = $this->helper->getSinglePage('_id', $array['id']);
	if (!is_null($pageData) && $pageData->count() > 0) {
	    $pageData = $pageData->getNext();
	    $this->kernel->view->setVar('KEYWORDS', @$pageData['keywords']);
	    $this->kernel->view->setVar('DESCRIPTION', @$pageData['description']);
	    $this->kernel->view->setVar('TITLE', @$pageData['name']);
	    $this->kernel->view->setVar('CONTENT_TITLE', @$pageData['name']);
	    $this->kernel->view->setVar('CONTENT', @$pageData['content']);
	}else{
	    $this->kernel->setResponseCode(404);
	}
    }
    //-----------------------------------------------------------------------------------------------------------------------

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
	$form = new HTML_QuickForm2("sortForm", 'get', [
	    'action' => $this->kernel->rewrite->generateURL('pages:list', $array)]);

	$selSort = $form->addSelect('sort');
	$selSort->addOption($this->lang['createdDate'], 'createdDate');
	$selSort->addOption($this->lang['author'], 'author');
	$selSort->addOption($this->lang['name'], 'name');
	$selSort->setLabel($this->lang['sortBy'] . ':');

	$form->addElement('radio', 'sortType', ['value' => 'desc'], ['content' => $this->lang['desc']]);
	$form->addElement('radio', 'sortType', ['value' => 'asc', 'checked' => 'checked'], ['content' => $this->lang['asc']]);
	$form->addElement('submit', '', ['value' => $this->lang['sort']]);


	//formularz wyszukiwania
	$form2 = new HTML_QuickForm2("searchForm", 'get', [
	    'action' => $this->kernel->rewrite->generateURL('pages:list', $array)]);
	$form2->addElement('text', 'name', ['id' => 'searchName'], ['label' => $this->lang['name'] . ':']);
	$form2->addElement('text', 'createdDate', ['id' => 'searchDate'], ['label' => $this->lang['createdDate'] . ':']);
	$form2->addElement('text', 'author', ['id' => 'searchAuthor'], ['label' => $this->lang['author'] . ':']);
	$form2->addElement('submit', '', ['value' => $this->lang['search']]);

	$this->kernel->view->addScript(null, kernel::host() . APPS . 'CMS/pages/Additional/AJAX.js');

	$this->kernel->view->setVar('CONTENT', '<div class="hiddenMenu leftMenu">' . $this->lang['sorting']
		. '<div class="hide">' . $form->render($renderer) . '</div></div>'
		. '<div class="hiddenMenu rightMenu">' . $this->lang['searching']
		. '<div class="hide">' . $form2->render($renderer) . '</div></div>'
		. $this->kernel->view->getVar('CONTENT'));
    }

    //-----------------------------------------------------------------------------------------------------------------------
    private function getPageList($current, array $params = null, array $sort = null) {
	//wyszukanie potrzebnych stron
	if (is_array($params)) {
	    $params+=["active" => "1"];
	} else {
	    $params = ["active" => "1"];
	}

	$pages = $this->helper->getPages($params, ['_id' => 1, 'name' => 1, 'createdDate' => 1, 'author' => 1]);
	$pagesCount = $pages->count();

	if (empty($sort)) {
	    $sort = ['createdDate' => -1];
	}

	//ograniczenie listy wyników i sortowanie
	$pages->sort($sort);
	$pages->skip(($current - 1) * $this::elsPerPage);
	$pages->limit($this::elsPerPage);

	$table = new HTML_Table(['class' => 'standard', 'id' => 'listTable']);
	$table->addRow(['Nazwa', 'Autor', 'Data utworzenia'], null, 'th');

	foreach ($pages as $page) {
	    $author = "Nieznany";
	    try {
		$author = $this->helper->getAuthor($page['author']);
		if (isset($author['name']))
		    $author = $author['name'];
		else
		    $author = "Nieznany";
	    } catch (Exception $e) {
		$author = "Nieznany";
	    }

	    $pageName = '<a href="'
		    . $this->kernel->rewrite->generateURL('pages:page', ['id' => $page['_id'], 'name' => $page['name']]) . '">'
		    . $page['name'] . '</a>';
	    $table->addRow([$pageName, $author, (isset($page['createdDate']) ? $page['createdDate'] : 'Nieznana')]);
	}

	$pag = $this->helper->paginateList($this::elsPerPage, $current, $pagesCount);

	return [$table->toHtml(), $pag];
    }

    //-----------------------------------------------------------------------------------------------------------------------
    public function userDelete($id){
        return true;
    }

    //-----------------------------------------------------------------------------------------------------------------------
    public function userCreate($id){
        return true;
    }

    //-----------------------------------------------------------------------------------------------------------------------
    public function sitemap(){
        return false;
    }

    //-----------------------------------------------------------------------------------------------------------------------
}
?>