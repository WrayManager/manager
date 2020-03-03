<?php
/**
 * WHMCS SDK Sample Provisioning Module
 *
 * Provisioning Modules, also referred to as Product or Server Modules, allow
 * you to create modules that allow for the provisioning and management of
 * products and services in WHMCS.
 *
 * This sample file demonstrates how a provisioning module for WHMCS should be
 * structured and exercises all supported functionality.
 *
 * Provisioning Modules are stored in the /modules/servers/ directory. The
 * module name you choose must be unique, and should be all lowercase,
 * containing only letters & numbers, always starting with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "wray" and therefore all
 * functions begin "wray_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _ConfigOptions
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/provisioning-modules/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Ramsey\Uuid\Uuid;
use WHMCS\Module\Addon\Wray\Models\User;
use WHMCS\Module\Addon\Wray\Models\Server;
use WHMCS\Module\Addon\Wray\Models\Product;

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array
 */
function wray_MetaData()
{
    return array(
        'DisplayName' => 'Wray',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => false, // Set true if module requires a server to work
    );
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configurationwo options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * You can specify up to 24 parameters, with field types:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each and their possible configuration parameters are provided in
 * this sample function.
 *
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 *
 * @return array
 */
function wray_ConfigOptions()
{
    $desp = <<<HTML
<a href="" id="ConfigureOthers">点我配置其他</a>
<script >
function getQueryString(name) {    var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');    var r = window.location.search.substr(1).match(reg);    if (r != null) {        return unescape(r[2]);    }    return null;}
$(function(){
    $("#ConfigureOthers").attr("href","addonmodules.php?module=wray&action=edit_product&product_id=" + getQueryString("id"))
})
</script>
HTML;
    return array(
        // a text field type allows for single line text input
        '流量' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '1024'
        ),
        '单位' => array(
            'Type' => 'dropdown',
            'Options' => array(
                'gb' => 'Gb',
                'tb' => 'Tb',
            )
        ),
        '流量重置周期' => array(
            'Type' => 'dropdown',
            'Options' => array(
                'month' => '月',
                'quarter' => '季',
                'year' => '年',
            )
        ),
        '配置其他' => array(
            'Type' => 'text',
            'Size' => '0',
            'Default' => '详细编辑点击这里.',
            'Description' => $desp
        ),
    );
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function wray_CreateAccount(array $params)
{
    try {
        $user = new User();
        if (User::where("service_id", $params['serviceid'])->first()) {
            $user = User::where("service_id", $params['serviceid'])->first();
        }
        $user->product_id = $params['pid'];
        $user->service_id = $params['serviceid'];
        $user->transfer = $params['configoption1'] * [
                'gb' => 1024*1024*1024,
                'tb' => 1024*1024*1024*1024
            ][$params['configoption2']];
        $will_reset_on = \Carbon\Carbon::now();
        $will_reset_on->addMonths([
            'month' => 1,
            'quarter' => 3,
            'year' => 12
        ][$params['configoption3']]);
        $user->will_reset_on = $will_reset_on;
        $user->upload = 0;
        $user->download = 0;
        $user->enabled = 1;
        $user->uuid = Uuid::uuid4();
        $user->save();
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage() . $e->getTraceAsString();
    }
    return 'success';
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function wray_SuspendAccount(array $params)
{
    try {
        $user = \WHMCS\Module\Addon\Wray\Models\User::where("service_id", $params['serviceid'])->first();
        if ($user) {
            $user->enabled = 0;
            $user->save();
        } else {
            throw new \Exception("User not created!");
        }
    } catch (Exception $e) {
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function wray_UnsuspendAccount(array $params)
{
    try {
        $user = \WHMCS\Module\Addon\Wray\Models\User::where("service_id", $params['serviceid'])->first();
        if ($user) {
            $user->enabled = 1;
            $user->save();
        } else {
            throw new \Exception("User not created!");
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function wray_TerminateAccount(array $params)
{
    try {
        $user = \WHMCS\Module\Addon\Wray\Models\User::where("service_id", $params['serviceid'])->first();
        if ($user) {
            $user->delete();
        } else {
            throw new \Exception("User not created!");
        }
    } catch (Exception $e) {
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Change the password for an instance of a product/service.
 *
 * Called when a password change is requested. This can occur either due to a
 * client requesting it via the client area or an admin requesting it from the
 * admin side.
 *
 * This option is only available to client end users when the product is in an
 * active status.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function wray_ChangePassword(array $params)
{
    try {
        $user = \WHMCS\Module\Addon\Wray\Models\User::where("service_id", $params['serviceid'])->first();
        if ($user) {
            $user->uuid = Uuid::uuid4();
            $user->save();
        } else {
            throw new \Exception("User not created!");
        }
    } catch (Exception $e) {
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
    return 'success';
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.
 *
 * This same function is called for upgrades and downgrades of both
 * products and configurable options.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function wray_ChangePackage(array $params)
{
    try {
        $user = new User();
        if (User::where("service_id", $params['serviceid'])->first()) {
            $user = User::where("service_id", $params['serviceid'])->first();
        }
        $user->product_id = $params['pid'];
        $user->service_id = $params['serviceid'];
        $user->transfer = $params['configoption1'] * [
                'gb' => 1024*1024*1024,
                'tb' => 1024*1024*1024*1024
            ][$params['configoption2']];
        $will_reset_on = \Carbon\Carbon::now();
        $will_reset_on->addMonths([
            'month' => 1,
            'quarter' => 3,
            'year' => 12
        ][$params['configoption3']]);
        $user->will_reset_on = $will_reset_on;
        $user->upload = 0;
        $user->download = 0;
        $user->enabled = 1;
        $user->save();
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}
/**
 * Additional actions an admin user can invoke.
 *
 * Define additional actions that an admin user can perform for an
 * instance of a product/service.
 *
 * @see wray_buttonOneFunction()
 *
 * @return array
 */
function wray_AdminCustomButtonArray()
{
    return array(
        "重置流量" => "resetTransfer"
    );
}

function wray_ClientAreaCustomButtonArray()
{
    return [
        "重置订阅" => "changeUUID"
    ];
}

function wray_resetTraffic(array $params)
{
    try {
        $user = new User();
        if (User::where("service_id", $params['serviceid'])->first()) {
            $user = User::where("service_id", $params['serviceid'])->first();
        }
        $user->product_id = $params['pid'];
        $user->service_id = $params['serviceid'];
        $user->transfer = $params['configoption1'] * [
                'gb' => 1024*1024*1024,
                'tb' => 1024*1024*1024*1024
            ][$params['configoption2']];
        $will_reset_on = \Carbon\Carbon::now();
        $will_reset_on->addMonths([
            'month' => 1,
            'quarter' => 3,
            'year' => 12
        ][$params['configoption3']]);
        $user->will_reset_on = $will_reset_on;
        $user->upload = 0;
        $user->download = 0;
        $user->enabled = 1;
        $user->uuid = Uuid::uuid4();
        $user->save();
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function wray_changeUUID(array $params)
{

    try {
        $user = \WHMCS\Module\Addon\Wray\Models\User::where("service_id", $params['serviceid'])->first();
        if ($user) {
            $user->uuid = Uuid::uuid4();
            $user->save();
        } else {
            throw new \Exception("User not created!");
        }
    } catch (Exception $e) {
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
    return 'success';
}

function wray_AdminServicesTabFields(array $params)
{
    try {
        $user = new User();
        if (User::where("service_id", $params['serviceid'])->first()) {
            $user = User::where("service_id", $params['serviceid'])->first();
        }
        return array(
            "ID" => $user->id,
            'UUID' => $user->uuid,
            "流量" => $user->friendly_transfer,
            "下载" => $user->friendly_download,
            "上传" => $user->friendly_upload,
            "启用" => $user->friendly_enabled,
            /*
            'Something Editable' => '<input type="hidden" name="wray_original_uniquefieldname" '
                . 'value="' . htmlspecialchars($response['textvalue']) . '" />'
                . '<input type="text" name="wray_uniquefieldname"'
                . 'value="' . htmlspecialchars($response['textvalue']) . '" />',
             */
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'wray',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, simply return no additional fields to display.
    }

    return array();
}

/**
 * Execute actions upon save of an instance of a product/service.
 *
 * Use to perform any required actions upon the submission of the admin area
 * product management form.
 *
 * It can also be used in conjunction with the AdminServicesTabFields function
 * to handle values submitted in any custom fields which is demonstrated here.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see wray_AdminServicesTabFields()
 */
function wray_AdminServicesTabFieldsSave(array $params)
{
    /*
    // Fetch form submission variables.
    $originalFieldValue = isset($_REQUEST['wray_original_uniquefieldname'])
        ? $_REQUEST['wray_original_uniquefieldname']
        : '';

    $newFieldValue = isset($_REQUEST['wray_uniquefieldname'])
        ? $_REQUEST['wray_uniquefieldname']
        : '';

    // Look for a change in value to avoid making unnecessary service calls.
    if ($originalFieldValue != $newFieldValue) {
        try {
            // Call the service's function, using the values provided by WHMCS
            // in `$params`.
        } catch (Exception $e) {
            // Record the error in WHMCS's module log.
            logModuleCall(
                'wray',
                __FUNCTION__,
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );

            // Otherwise, error conditions are not supported in this operation.
        }
    }
    */
}

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function wray_ClientArea(array $params)
{
    $action = isset($_REQUEST['ModuleAction']) ? $_REQUEST['ModuleAction'] : '';

    $dispatcher = new \WHMCS\Module\Server\Wray\Dispatcher();
    return $dispatcher->dispatch($action, $params);
}
