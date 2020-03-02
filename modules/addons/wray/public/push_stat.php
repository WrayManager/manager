<?php
require __DIR__."/../../../../init.php";

use WHMCS\Module\Addon\Wray\Models\Server;
use WHMCS\Database\Capsule;

$token = $_POST['token'];

$results = localAPI("DecryptPassword", [
    'password2' => $token,
]);

if(Server::find($results['password'])){
    $stat = $_POST['stat'];
    $stat = json_decode(html_entity_decode($stat), true);
    try {
        foreach($stat['users'] as $key => $u){
            $s = new \WHMCS\Module\Addon\Wray\Models\Stat();
            $s->upload = $u['upload'];
            $s->download = $u['download'];
            $s->for_id = \WHMCS\Module\Addon\Wray\Models\User::where("uuid",$key)->value('id');
            $s->for_type = \WHMCS\Module\Addon\Wray\Models\User::class;
            $s->save();
            $user = \WHMCS\Module\Addon\Wray\Models\User::where("uuid",$key)->first();
            if($user){
                $user->upload += $u['upload'];
                $user->download += $u['download'];
                $user->save();
            }
        }
        $s = new \WHMCS\Module\Addon\Wray\Models\Stat();
        $s->upload = $stat['proxy']['upload'];
        $s->download = $stat['proxy']['download'];
        $s->for_id = $results['password'];
        $s->for_type = Server::class;
        //$s->for = Server::find($results['password']);
        $s->save();
    } catch (\Exception $e){
        logModuleCall('wray', __FUNCTION__, $e->getTraceAsString(), $e->getMessage(), $e->getMessage());
    }
    echo "succeed!";
}else{
    echo "Access Denied!";

}