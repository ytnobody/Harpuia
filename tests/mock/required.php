<?php
require_once __DIR__. "/../../Harpuia/AzureWebApps.php";

$h = new \Harpuia\AzureWebApps();
$h->required('name', 'age');
$v = $h->validate(array(
    'name' => '/^.+$/',
    'age'  => '/^[0-9]+$/'
));
$h->res(array(
    "message" => sprintf("Hi. I'm %s, %d years old.", $v->name, $v->age)
));
