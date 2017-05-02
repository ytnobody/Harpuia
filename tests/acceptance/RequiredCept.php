<?php 
$I = new AcceptanceTester($scenario);
$url = '/required.php';

$I->wantTo('Simple Get Request with Required Fields and Simple Http Response');
$I->sendGet($url);
$I->seeResponseMatchesJsonType([
    'status'  => 'integer:regex(/^400$/)', 
    'message' => 'string:regex(/^bad request$/)',
    'error'   => 'string:regex(/^parameter \'name\' is required/)'
]);
$I->sendGet($url, array('name' => 'ytnobody'));
$I->seeResponseMatchesJsonType([
    'status'  => 'integer:regex(/^400$/)', 
    'message' => 'string:regex(/^bad request$/)',
    'error'   => 'string:regex(/^parameter \'age\' is required/)'
]);
$I->sendGet($url, array('name' => 'ytnobody', 'age' => 'secret'));
$I->seeResponseMatchesJsonType([
    'status'  => 'integer:regex(/^400$/)', 
    'message' => 'string:regex(/^bad request$/)',
    'error'   => 'string:regex(/^parameter \'age\' is invalid/)'
]);
$I->sendGet($url, array('name' => 'ytnobody', 'age' => 36));
$I->seeResponseMatchesJsonType([
    'status'  => 'integer:regex(/^200$/)',
    'message' => 'string:regex(/^Hi\. I\'m ytnobody, 36 years old\.$/)'
]);