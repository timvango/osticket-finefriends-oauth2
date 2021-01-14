<?php

return array(
    'id' =>             'finefriends:oauth2', # notrans
    'version' =>        '0.2',
    'name' =>           /* trans */ 'FineFriends Oauth2 Authentication',
    'author' =>         'Pablo González, Tim van Gompel',
    'description' =>    /* trans */ 'Provides a configurable authentication backend
        for authenticating staff and clients using an OAUTH2 from FineFriends.',
    'url' =>            'https://github.com/senzil/osticket-google-oauth2-plugin',
    'plugin' =>         'authentication.php:OauthAuthPlugin',
    'requires' => array(
        "ohmy/auth" => array(
            "version" => "*",
            "map" => array(
                "ohmy/auth/src" => 'lib',
            )
        ),
    ),
);

?>
