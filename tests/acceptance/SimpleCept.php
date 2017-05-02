<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Simple Get Request and Simple JSON Response');
$I->sendGet('/simple.php');
$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);
$I->seeResponseMatchesJsonType([
    'status'  => 'integer', 
    'message' => 'string:regex(/^Hello\!$/)'
]);

