<?php
require __DIR__."/../../../../init.php";

use WHMCS\Module\Addon\Wray\Models\Server;
use WHMCS\Database\Capsule;

$token = $_POST['token'];

$results = localAPI("DecryptPassword", [
    'password2' => $token,
    ]);
if(Server::find($results['password'])){
    echo json_encode(array_merge(Server::find($results['password'])->toArray(),[
        'license' => Capsule::table("tbladdonmodules")->where("module","wray")->where("setting","license")->value("value")
    ]));
}else{
    echo "Access Denied!";
}