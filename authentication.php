<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class OauthAuthPlugin extends Plugin {
    var $config_class = "OauthPluginConfig";

    function bootstrap() {
        $config = $this->getConfig();

        $finefriends = $config->get('g-enabled');
        if (in_array($finefriends, array('all', 'staff'))) {
            require_once('finefriends.php');
            StaffAuthenticationBackend::register(
                new FineFriendsStaffAuthBackend($this->getConfig()));
        }
        if (in_array($finefriends, array('all', 'client'))) {
            require_once('finefriends.php');
            UserAuthenticationBackend::register(
                new FineFriendsClientAuthBackend($this->getConfig()));
        }
    }
}

require_once(INCLUDE_DIR.'UniversalClassLoader.php');
use Symfony\Component\ClassLoader\UniversalClassLoader_osTicket;
$loader = new UniversalClassLoader_osTicket();
$loader->registerNamespaceFallbacks(array(
    dirname(__file__).'/lib'));
$loader->register();
