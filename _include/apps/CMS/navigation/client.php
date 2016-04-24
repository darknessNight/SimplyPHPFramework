<?php
/**
 *
 * @author Paweł Przytarski <pawelmrocznyp@gmail.com>
 * @copyright (c) 2013, Paweł Przytarski
 * @version 0.1.4
 */
class cms_navigation_client {
    /**
     *
     * @var kernel
     */
    private $kernel=null;

    function __construct(&$kernel) {
        $this->kernel=&$kernel;
    }

    function __destruct() {
	$navs = $this->loadNavs();
	foreach ($navs as $nav) {
	    $str = '<ul class="navigation">';
	    $this->createElement($str, $nav['elements']);
	    $this->kernel->view->setVar('NAV_' . $nav['name'], $str . '</ul>');
	}
    }

    private function loadNavs() {
	return $this->kernel->db->navigation->find();
    }

    private function createElement(&$str, $nav) {
	foreach ($nav as $el) {
	    $str.='<li><a href="';
	    if (isset($el['app']) && isset($el['action'])) {
		$str.=$this->kernel->rewrite->generateURL($el['app'] . ':' . $el['action'], (isset($el['vars']) && $el['vars']->count() > 0 ? $el['vars'] : []));
	    }
	    else
		$str.=@$el['url'];
	    $str.='">' . @$el['name'] . '</a>';
	    if (isset($el['els'])) {
		$str.='<ul class="subnav">';
		$this->createElement($str, $el);
		$str.='</ul>';
	    }
	    $str.='</li>';
	}
    }

}
?>
