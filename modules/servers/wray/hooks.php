<?php
/**
 * WHMCS SDK Sample Provisioning Module Hooks File
 *
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * This allows you to execute your own code in addition to, or sometimes even
 * instead of that which WHMCS executes by default.
 *
 * WHMCS recommends as good practice that all named hook functions are prefixed
 * with the keyword "hook", followed by your module name, followed by the action
 * of the hook function. This helps prevent naming conflicts with other addons
 * and modules.
 *
 * For every hook function you create, you must also register it with WHMCS.
 * There are two ways of registering hooks, both are demonstrated below.
 *
 * @see https://developers.whmcs.com/hooks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

add_hook('DailyCronJob', 1, function($vars) {
    foreach(\WHMCS\Module\Addon\Wray\Models\User::with("product")->get() as  $user){
        if($user->enabled && time() > $user->will_reset_on->timestamp){
            $user->transfer = $user->product->transfer * [
                    'gb' => 1024*1024*1024,
                    'tb' => 1024*1024*1024*1024
                ][$user->product->unit];
            $will_reset_on = $user->will_reset_on;
            $will_reset_on->addMonths([
                'month' => 1,
                'quarter' => 3,
                'year' => 12
            ][$user->product->cycle]);
            $user->will_reset_on = $will_reset_on;
            $user->upload = 0;
            $user->download = 0;
            $user->save();
        }
    }
});

