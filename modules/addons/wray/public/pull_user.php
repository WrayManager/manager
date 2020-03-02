<?php
require __DIR__."/../../../../init.php";

use WHMCS\Module\Addon\Wray\Models\Server;
use WHMCS\Database\Capsule;

$token = $_POST['token'];

$results = localAPI("DecryptPassword", [
    'password2' => $token,
]);

if(Server::find($results['password'])){
    echo json_encode(Server::with(["products.users" => function ($query){
        $query->whereRaw("transfer > (download + upload)");;
    }])->find($results['password'])->toArray());
}else{
    echo "Access Denied!";

}