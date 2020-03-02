<?php
require __DIR__."/../../../../init.php";

use WHMCS\Module\Addon\Wray\Models\Server;
use WHMCS\Database\Capsule;

$token = $_POST['token'];

$results = localAPI("DecryptPassword", [
    'password2' => $token,
]);

if(Server::find($results['password'])){
    $load = $_POST['load'];
    $load = json_decode(html_entity_decode($load), true);
    $server = Server::find($results['password']);
    $server->mem_available = $load['mem']['available'];
    $server->mem_total = $load['mem']['total'];
    $server->load = round($load['load']['load1'],2) . " " . round($load['load']['load5'] ,2) . " " . round($load['load']['load15'],2);
    $server->save();
    echo "succeed!";
}else{
    echo "Access Denied!";

}