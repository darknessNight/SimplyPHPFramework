<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of chelper
 *
 * @author Pawel_000
 */

namespace CMS_Gallery {
class chelper {
    //put your code here
	private $kernel;

	function __construct(&$kernel) {
	    $this->kernel = &$kernel;
	}

	function AddImg($array) {
	    $array['_id'] = new \MongoId();
	    $this->kernel->db->gallery->insert($array);
	    return $array['_id'];
	}

	function saveImage($src, $desc, $name, $ext, $watermark) {
	    $name = $desc . $name;
	    move_uploaded_file($src, $name . '.' . $ext);
	    $image = null;
	    switch (strtoupper($ext)) {
		case 'PNG':$image = imagecreatefrompng($name . '.' . $ext);
		    break;
		case 'JPG':case "JPEG":$image = imagecreatefromjpeg($name . '.' . $ext);
		    break;
	    }
	    if ($image === FALSE)
		return '';
	    $imgWidth = imagesx($image);
	    $imgHeight = imagesy($image);
	    $textSize = sqrt(pow($imgWidth, 2) + pow($imgHeight, 2)) / 2 / strlen($watermark);
	    if (imagettftext($image, $textSize, -45, $imgWidth / 4, $imgHeight / 4, imagecolorexactalpha($image, 127, 127, 0, 50), dirname(__FILE__) . '/antibiotech.ttf', $watermark) === FALSE)
		return '';

	    switch (strtoupper($ext)) {
		case 'PNG':if (!imagepng($image, $name . '_watermark', 100))
			return '';
		    if (!imagepng(imagescale($image, 200), $name . '_small', 100))
			return '';
		    break;
		case 'JPG':case "JPEG":if (!imagejpeg($image, $name . '_watermark', 100))
			return '';
		    if (!imagejpeg(imagescale($image, 200), $name . '_small', 100))
			return '';
		    break;
	    }
	    return $name . '_small';
	}

	public function paginateList($elNum, $current, $elAll = 0) {
	    $rule = $this->kernel->rewrite->current;
	    if ($elNum < 0)
		return false;
	    if ($elAll <= 0)
		$elAll = $this->kernel->db->gallery->find()->count();
	    $pagesNum = ceil($elAll / $elNum); //podzielenie na ilość stron
	    //if($pagesNum<2) return false;//jeśli mniej niż dwie strony nie wyświetlaj

	    $str = '';
	    if ($current > 1) {//tworzenie znaku poprzednia strona "<<" oraz pierwsza "1"
		$str.='<a href="' .
			$this->kernel->rewrite->generateURL($rule, ['page' => ($current - 1)])
			. '">&laquo;</a> ';
		$str.='<a href="' . $this->kernel->rewrite->generateURL($rule, array('page' => 1)) . '">1</a> ';
	    }

	    if ($current - 5 > 1)
		$str.='... '; //jeśli więcej niż 5 stron poprzedzających wyświetl przerwę
	    for ($i = $current - 5; $i < $current; $i++) {//dodawanie 5 stron przed obecną
		if ($i < 2)
		    continue;
		if ($i > $pagesNum)
		    break;
		$str.='<a href="' . $this->kernel->rewrite->generateURL($rule, ['page' => $i]) . '">' . $i . '</a> ';
	    }

	    $str.= '[ ' . $current . ' ] '; //obecna strona

	    for ($i = $current + 1; $i < $current + 6; $i++) {//utworzenie 5 następnych stron
		if ($i == $pagesNum || $i > $pagesNum)
		    break;
		$str.='<a href="' . $this->kernel->rewrite->generateURL($rule, array('page' => $i)) . '">' . $i . '</a> ';
	    }
	    if ($current + 6 < $pagesNum)
		$str.='... ';
	    if ($current < $pagesNum) {//znak następna strona ">>" i ostatnia
		$str.='<a href="' . $this->kernel->rewrite->generateURL($rule, array('page' => $pagesNum)) . '">' . $pagesNum . '</a> ';
		$str.='<a href="' . $this->kernel->rewrite->generateURL($rule, array('page' => ($current + 1))) . '">&raquo;</a> ';
	    }
	    return $str;
	}

	public function prepareSearch($array) {
	    $search = [];
	    //sprawdzenia zawężenia poszukiwania
	    if (isset($array['name']) && is_string($array['name']) && !empty($array['name']) && $array['name'] != 'undefined')
		$search+=['name' => new \MongoRegex('/' . $array['name'] . '/i')];
	    if (isset($array['author']) && is_string($array['author']) && !empty($array['author']) && $array['author'] != 'undefined')
		$search+=['author' => new \MongoRegex('/' . $array['author'] . '/i')];
	    return $search;
	}

	public function getHints($params, $list = array()) {//pobiera wskazówki wyświetlania
	    if (is_array($params)) {
		$params = $this->createParams($params, $list);
		$pages = $this->getImgs($params, ['_id' => 1, 'name' => 1, 'author' => 1, 'private' => 1]);
		$names = array();
		$authors = array();
		foreach ($pages as $page) {
		    $names[] = @$page['name'];
		    $authors[] = @$page['author'];
		}
		return['name' => $names, 'author' => $authors];
	    } else {
		return ['name' => '', 'author' => ''];
	    }
	}

	public function getImgs($params, $cols, $list = array()) {
	    if (empty($list))
		$list = array();
	    $params = $this->createParams($params, $list);
	    return $this->kernel->db->gallery->find($params, $cols);
	}

	private function createParams($params, $list) {
	    global $_APPS;
	    $params['private'] = 0;
	    if (!empty($list) && is_array($list)) {
		$newPars['$or'] = array();
		foreach ($list as $el) {
		    if (!is_string($el))
			continue;
		    $params['_id'] = new \MongoId($el);
		    $newPars['$or'][] = $params;
		    if (strlen($_APPS['CMS_Users']->getActiveUserId()) > 0) {
			$pars = $params;
			$pars['author'] = $_APPS['CMS_Users']->userName;
			$pars['private'] = 1;
			$newPars['$or'][] = $pars;
		    }
		}
		$params = $newPars;
	    } else {
		if (strlen($_APPS['CMS_Users']->getActiveUserId()) > 0) {
		    $newPars = $params;
		    $newPars['author'] = $_APPS['CMS_Users']->userName;
		    $newPars['private'] = 1;
		    $params = ['$or' => [$params, $newPars]];
		}
	    }
	    return $params;
	}

    }
}
?>
