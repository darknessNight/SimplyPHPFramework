<?php

$mongo = new MongoClient("mongodb://localhost:27017", [
    "username" => "wai_web",
    "password" => "w@i_w3b",
    "db" => "wai"
	]);

$db = $mongo->selectDB("wai");

if ($db->apps->find(['name' => 'CMS_pages'])->count() <= 0)
    $db->apps->insert([
    'name' => 'CMS_pages',
    'active' => '1',
    'required' => '0',
    'home' => '1'
]);

if ($db->apps->find(['name' => 'CMS_settings'])->count() <= 0)
    $db->apps->insert([
    'name' => 'CMS_settings',
    'active' => '1',
    'required' => '1',
    'home' => '0'
]);

if ($db->settings->find(['name' => 'logo'])->count() <= 0)
    $db->settings->insert([
    'name' => 'logo',
    'value' => './_themes/logo.png'
]);

if ($db->settings->find(['name' => 'theme'])->count() <= 0)
    $db->settings->insert([
    'name' => 'theme',
    'value' => 'defaultBlack'
]);

if ($db->settings->find(['name' => 'title'])->count() <= 0)
    $db->settings->insert([
    'name' => 'title',
    'value' => 'WAI Projekt 2'
]);

if ($db->pages->find(['name' => 'HomePage'])->count() <= 0)
    $db->pages->insert([
	'name' => 'HomePage',
	'active' => '1',
	'lang' => 'pl_PL',
	'home' => '1',
        'content' => 'Strona główna',
	'keywords' => 'null',
	'description' => 'null',
	'author' => 0,
	'createdDate' => date("Y-m-d H:i:s")
    ]);

if ($db->pages->find(['name' => 'Strona testowa'])->count() <= 0)
    $db->pages->insert([
	'name' => 'Strona testowa',
	'active' => '1',
	'lang' => 'pl_PL',
	'home' => '1',
	'content' => 'Lorem ipsum dolor sit amet enim. Etiam ullamcorper. Suspendisse a pellentesque dui, non felis. Maecenas malesuada elit lectus felis, malesuada ultricies. Curabitur et ligula. Ut molestie a, ultricies porta urna. Vestibulum commodo volutpat a, convallis ac, laoreet enim. Phasellus fermentum in, dolor. Pellentesque facilisis. Nulla imperdiet sit amet magna. Vestibulum dapibus, mauris nec malesuada fames ac turpis velit, rhoncus eu, luctus et interdum adipiscing wisi. Aliquam erat ac ipsum. Integer aliquam purus. Quisque lorem tortor fringilla sed, vestibulum id, eleifend justo vel bibendum sapien massa ac turpis faucibus orci luctus non, consectetuer lobortis quis, varius in, purus. Integer ultrices posuere cubilia Curae, Nulla ipsum dolor lacus, suscipit adipiscing. Cum sociis natoque penatibus et ultrices volutpat. Nullam wisi ultricies a, gravida vitae, dapibus risus ante sodales lectus blandit eu, tempor diam pede cursus vitae, ultricies eu, faucibus quis, porttitor eros cursus lectus, pellentesque eget, bibendum a, gravida ullamcorper quam. Nullam viverra consectetuer. Quisque cursus et, porttitor risus. Aliquam sem. In hendrerit nulla quam nunc, accumsan congue. Lorem ipsum primis in nibh vel risus. Sed vel lectus. Ut sagittis, ipsum dolor quam.',
	'keywords' => 'null',
	'description' => 'null',
	'author' => 0,
	'createdDate' => date("Y-m-d H:i:s")
    ]);

if ($db->users->find(['name' => 'admin'])->count() <= 0)
    $db->users->insert(['name' => 'admin', 'pass' => password_hash('admin', PASSWORD_DEFAULT)]);


echo 'Zakończyłem ' . date("Y-m-d H:i:s");
?>
