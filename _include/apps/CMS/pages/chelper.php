<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of cms_pages_chelper
 *
 * @author Pawel_000
 */
class cms_pages_chelper {

    const elsPerPage = 10;

    /**
     *
     * @var kernel
     */
    private $kernel;

    public function __construct(&$kernel) {
	$this->kernel = $kernel;
    }

    public function getSinglePage($var, $value) {
	$page = null;
	try {
		$page = $this->kernel->db->pages->find([$var => ($var == '_id' ? new MongoId($value) : $value)]);
	    } catch (Exception $e) {
		return array();
	}
	return $page;
    }

//-----------------------------------------------------------------------------------------------------------------------
    public function prepareSearch($array) {
	$search = [];
	//sprawdzenia zawężenia poszukiwania
	if (isset($array['name']) && is_string($array['name']) && !empty($array['name']) && $array['name'] != 'undefined')
	    $search+=['name' => new MongoRegex('/' . $array['name'] . '/i')];
	if (isset($array['createdDate']) && is_string($array['createdDate']) && !empty($array['createdDate']) && $array['createdDate'] != 'undefined')
	    $search+=['createdDate' => new MongoRegex('/' . $array['createdDate'] . '/i')];
	if (isset($array['author']) && is_string($array['author']) && !empty($array['author']) && $array['author'] != 'undefined') {
	    //w przypadku określenia autora należy użyć w warunku dyrektywy OR (bo nie ma JOIN w mongodb ;_;)
	    $param = array();
	    $authors = $this->kernel->db->users->find(['name' => new MongoRegex('/' . $array['author'] . '/i')], ['_id' => 1]);
	    foreach ($authors as $author) {
		$param = ['$or' => array()];
		$param['$or'][] = ['author' => $author['_id']];
	    }
	    $search+=$param;
	}
	return $search;
    }

    //-----------------------------------------------------------------------------------------------------------------------
    public function getHints($params) {//pobiera wskazówki wyświetlania
	if (is_array($params)) {
	    $pages = $this->kernel->db->pages->find($params, ['_id' => 0, 'name' => 1, 'createdDate' => 1, 'author' => 1]);
	    $names = array();
	    $date = array();
	    $authors = array();
	    foreach ($pages as $page) {
		$names[] = @$page['name'];
		$date[] = @$page['createdDate'];
		try {
		    $author = $this->kernel->db->users->findOne(['_id' => new MongoId(@$page['author'])]);
		    $authors[] = $author['name'];
		} catch (Exception $e) {

		}
	    }
	    return['name' => $names, 'createdDate' => $date, 'author' => $authors];
	} else {
	    return ['name' => '', 'createdDate' => '', 'author' => ''];
	}
    }
    //-----------------------------------------------------------------------------------------------------------------------
    public function getPageCount() {
	return $this->kernel->db->pages->find()->count(); //pobranie ilości rekordów w bazie
    }
    //-----------------------------------------------------------------------------------------------------------------------
    public function getPages($params, $cols) {
	return $pages = $this->kernel->db->pages->find($params, $cols);
    }

    //-----------------------------------------------------------------------------------------------------------------------
    public function getAuthor($id) {
	return $this->kernel->db->users->findOne(['_id' => new MongoId($id)]);
    }

    //-----------------------------------------------------------------------------------------------------------------------
    public function paginateList($elNum, $current, $elAll = 0) {
	if ($elNum < 0)
	    return false;
	if ($elAll <= 0)
	    $elAll = $this->getPageCount();
	$pagesNum = ceil($elAll / $elNum); //podzielenie na ilość stron
	//if($pagesNum<2) return false;//jeśli mniej niż dwie strony nie wyświetlaj

	$str = '';
	if ($current > 1) {//tworzenie znaku poprzednia strona "<<" oraz pierwsza "1"
	    $str.='<a href="' .
		    $this->kernel->rewrite->generateURL('pages:list', ['page' => ($current - 1)])
		    . '">&laquo;</a> ';
	    $str.='<a href="' . $this->kernel->rewrite->generateURL('pages:list', array('page' => 1)) . '">1</a> ';
	}

	if ($current - 5 > 1)
	    $str.='... '; //jeśli więcej niż 5 stron poprzedzających wyświetl przerwę
	for ($i = $current - 5; $i < $current; $i++) {//dodawanie 5 stron przed obecną
	    if ($i < 2)
		continue;
	    if ($i > $pagesNum)
		break;
	    $str.='<a href="' . $this->kernel->rewrite->generateURL('pages:list', ['page' => $i]) . '">' . $i . '</a> ';
	}

	$str.= '[ ' . $current . ' ] '; //obecna strona

	for ($i = $current + 1; $i < $current + 6; $i++) {//utworzenie 5 następnych stron
	    if ($i == $pagesNum || $i > $pagesNum)
		break;
	    $str.='<a href="' . $this->kernel->rewrite->generateURL('pages:list', array('page' => $i)) . '">' . $i . '</a> ';
	}
	if ($current + 6 < $pagesNum)
	    $str.='... ';
	if ($current < $pagesNum) {//znak następna strona ">>" i ostatnia
	    $str.='<a href="' . $this->kernel->rewrite->generateURL('pages:list', array('page' => $pagesNum)) . '">' . $pagesNum . '</a> ';
	    $str.='<a href="' . $this->kernel->rewrite->generateURL('pages:list', array('page' => ($current + 1))) . '">&raquo;</a> ';
	}
	return $str;
    }

}

?>
