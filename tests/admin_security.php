<?php

// Admin security helper regression test. Compatible with PHP 5.6+.
define('VERSION', '2.1.1');
define('CSRF_TOKEN', 'glsectoken');

$menuSecurityToken = 'token-value';
$menuSecurityCheck = true;

function SEC_createToken()
{
    global $menuSecurityToken;
    return $menuSecurityToken;
}

function SEC_checkToken()
{
    global $menuSecurityCheck;
    return $menuSecurityCheck;
}

function DB_escapeString($value)
{
    return str_replace("'", "''", $value);
}

require_once dirname(__DIR__) . '/admin_security.php';

function menu_security_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

menu_security_assert(MENU_adminModeMutates('save') === true, 'save must be treated as mutation');
menu_security_assert(MENU_adminModeMutates('delete') === true, 'delete must be treated as mutation');
menu_security_assert(MENU_adminModeMutates('move') === true, 'move must be treated as mutation');
menu_security_assert(MENU_adminModeMutates('menu') === false, 'menu display must not be treated as mutation');
menu_security_assert(MENU_adminModeMutates('edit') === false, 'edit display must not be treated as mutation');
menu_security_assert(MENU_adminModeRequiresPost('move') === true, 'move must require POST');
menu_security_assert(MENU_adminModeRequiresPost('delete') === true, 'delete must require POST');
menu_security_assert(MENU_adminModeRequiresPost('deletemenu') === true, 'menu deletion must require POST');
menu_security_assert(MENU_adminModeRequiresPost('save') === false, 'normal save mode is already a POST form and does not need legacy-link conversion');
menu_security_assert(MENU_adminPostMutates(array('defaults' => '1')) === true, 'defaults POST must be protected');
menu_security_assert(MENU_adminPostMutates(array('orders' => 'item[]=1')) === true, 'orders POST must be protected');
menu_security_assert(MENU_adminPostMutates(array('cancel' => '1')) === false, 'cancel must not be treated as mutation');
menu_security_assert(MENU_adminRequestMutates('save', array()) === true, 'routed mutation detection failed');
menu_security_assert(MENU_adminRequestMutates('', array('orders' => 'x')) === true, 'unrouted mutation detection failed');
menu_security_assert(MENU_adminCheckToken() === true, 'native token check was not delegated');
menu_security_assert(MENU_adminCreateToken() === 'token-value', 'native token creation was not delegated');
menu_security_assert(MENU_adminTokenName() === 'glsectoken', 'Geeklog CSRF token field name was not preserved');
menu_security_assert(strpos(MENU_adminTokenInput('abc'), 'name="glsectoken"') !== false, 'token field name missing');
menu_security_assert(strpos(MENU_adminTokenInput('abc'), 'value="abc"') !== false, 'token field value missing');
menu_security_assert(MENU_adminId('12') === 12, 'positive id normalization failed');
menu_security_assert(MENU_adminId('-4') === 0, 'negative id must be rejected');
menu_security_assert(MENU_adminId('abc') === 0, 'non numeric id must be rejected');
menu_security_assert(MENU_adminDbEscape("O'Reilly") === "O''Reilly", 'database escaping was not delegated');

$_SERVER['PHP_SELF'] = '/admin/plugins/menu/index.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
menu_security_assert(MENU_adminIsControllerRequest() === true, 'Menu admin controller detection failed');
menu_security_assert(MENU_adminRequestMethod() === 'POST', 'request method detection failed');
$_POST = array('mode' => 'save');
$_GET = array();
menu_security_assert(MENU_adminCurrentMode() === 'save', 'POST mode detection failed');

class MenuSecurityScriptsStub
{
    public $libraries = array();
    public $scripts = array();

    public function setJavaScriptLibrary($name)
    {
        $this->libraries[] = $name;
    }

    public function setJavaScript($script, $footer = false)
    {
        $this->scripts[] = $script;
    }
}

$_SCRIPTS = new MenuSecurityScriptsStub();
MENU_adminRegisterTokenBridge();
menu_security_assert(in_array('jquery', $_SCRIPTS->libraries, true), 'token bridge must request jQuery');
menu_security_assert(count($_SCRIPTS->scripts) === 1, 'token bridge script was not registered');
menu_security_assert(strpos($_SCRIPTS->scripts[0], 'glsectoken') !== false, 'token bridge field name missing');
menu_security_assert(strpos($_SCRIPTS->scripts[0], 'token-value') !== false, 'token bridge value missing');
menu_security_assert(strpos($_SCRIPTS->scripts[0], 'ajaxPrefilter') !== false, 'AJAX token bridge missing');
menu_security_assert(strpos($_SCRIPTS->scripts[0], 'submitMutation') !== false, 'POST mutation submission bridge missing');
menu_security_assert(strpos($_SCRIPTS->scripts[0], 'move|delete|deletemenu') !== false, 'destructive mutation mode matcher missing');
menu_security_assert(strpos($_SCRIPTS->scripts[0], "method: 'post'") !== false, 'destructive mutation bridge must submit POST');
menu_security_assert(strpos($_SCRIPTS->scripts[0], "removeAttr('onclick')") !== false, 'legacy inline delete handler must be replaced safely');

$menuSecurityCheck = false;
menu_security_assert(MENU_adminCheckToken() === false, 'failed token must remain failed');

$_SERVER['PHP_SELF'] = '/tests/admin_security.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = array();
$_GET = array();

echo "Admin security helper tests passed" . PHP_EOL;
