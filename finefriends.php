<?php

use ohmy\Auth2;

/*auth-oauth: FineFriends: Allow domain whitelisting #122*/
function isEmailAllowed($email, $domains_string) {
    $email_domain = end(explode('@', $email, 2));
    $domains = explode(',', $domains_string);
    foreach ($domains as $domain) {
        if (strcasecmp($email_domain, trim($domain)) === 0) {
            return TRUE;
        }
    }
    return strlen(trim($domains_string)) === 0;
}

class FineFriendsAuth {
    var $config;
    var $access_token;

    function __construct($config) {
        $this->config = $config;
    }

    function triggerAuth() {
        /*https://github.com/osTicket/osTicket-plugins/issues/52*/
        global $ost;
        $self = $this;
        return Auth2::legs(3)
            ->set('id', $this->config->get('g-client-id'))
            ->set('secret', $this->config->get('g-client-secret'))
            /*https://github.com/osTicket/osTicket-plugins/issues/52*/
            ->set('redirect', rtrim($ost->getConfig()->getURL(), '/') . '/api/auth/ext')
            ->set('scope', 'profile email')

            ->authorize('https://finefriends.social/api/v4/auth')
            ->access('https://finefriends.social/api/v4/access_token')

            ->finally(function($data) use ($self) {
                $self->access_token = $data['access_token'];
            });
    }
}

class FinefriendsStaffAuthBackend extends ExternalStaffAuthenticationBackend {
    static $id = "finefriends";
    static $name = "FineFriends";

    static $sign_in_image_url = '';
    static $service_name = "FineFriends";

    var $config;

    function __construct($config) {
        $this->config = $config;
        FinefriendsStaffAuthBackend::$sign_in_image_url = $this->config->get('g-agents-button-url');
        $this->finefriends = new FinefriendsAuth($config);
    }

    function signOn() {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['email'])) {
            /*auth-oauth: Finefriends: Allow domain whitelisting #122*/
            if (!isEmailAllowed($_SESSION[':oauth']['email'], $this->config->get(
                'g-allowed-domains-agents')))
                $_SESSION['_staff']['auth']['msg'] = 'Login with this email address is not permitted';
            else if (($staff = StaffSession::lookup(array('email' => $_SESSION[':oauth']['email'])))
                && $staff->getId()
            ) {
                if (!$staff instanceof StaffSession) {
                    // osTicket <= v1.9.7 or so
                    $staff = new StaffSession($user->getId());
                }
                return $staff;
            }
            else
                $_SESSION['_staff']['auth']['msg'] = 'Have your administrator create a local account';
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }


    function triggerAuth() {
        parent::triggerAuth();
        $finefriends = $this->finefriends->triggerAuth();
        var_dump($this->finefriends->access_token);
        $finefriends->GET(
            "https://finefriends.social/api/v4/user_info?access_token="
                . $this->finefriends->access_token)
            ->then(function($response) {
                require_once INCLUDE_DIR . 'class.json.php';
                if ($json = JsonDataParser::decode($response->text)) {
                    $_SESSION[':oauth']['email'] = $json['email'];
                    $_SESSION[':oauth']['name'] = $json['name'];
                }
                Http::redirect(ROOT_PATH . 'scp');
            }
        );
    }
}

class FinefriendsClientAuthBackend extends ExternalUserAuthenticationBackend {
    static $id = "finefriends.client";
    static $name = "Finefriends";

    static $sign_in_image_url = '';
    static $service_name = "Finefriends";

    function __construct($config) {
        $this->config = $config;
        FinefriendsClientAuthBackend::$sign_in_image_url = $this->config->get('g-clients-button-url');
        $this->finefriends = new FinefriendsAuth($config);
    }

    function supportsInteractiveAuthentication() {
        return false;
    }

    function signOn() {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['email'])) {
            /*auth-oauth: Finefriends: Allow domain whitelisting #122*/
            if (!isEmailAllowed($_SESSION[':oauth']['email'], $this->config->get(
                'g-allowed-domains-clients')))
                $errors['err'] = 'Login with this email address is not permitted';
            else if (($acct = ClientAccount::lookupByUsername($_SESSION[':oauth']['email']))
                    && $acct->getId()
                    && ($client = new ClientSession(new EndUser($acct->getUser()))))
                return $client;

            elseif (isset($_SESSION[':oauth']['name'])) {
                // TODO: Prepare ClientCreateRequest
                $info = array(
                    'email' => $_SESSION[':oauth']['email'],
                    'name' => $_SESSION[':oauth']['name'],
                );
                return new ClientCreateRequest($this, $info['email'], $info);
            }
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }

    function triggerAuth() {
        require_once INCLUDE_DIR . 'class.json.php';
        parent::triggerAuth();
        $finefriends = $this->finefriends->triggerAuth();
        $token = $this->finefriends->access_token;
        $finefriends->GET(
            "https://finefriends.social/api/v4/user_info?access_token="
                . $token)
            ->then(function($response) {
                require_once INCLUDE_DIR . 'class.json.php';
                if ($json = JsonDataParser::decode($response->text)) {
                    $_SESSION[':oauth']['email'] = $json['email'];
                    $_SESSION[':oauth']['name'] = $json['name'];
                }
                Http::redirect(ROOT_PATH . 'tickets.php');
            }
        );
    }
}


