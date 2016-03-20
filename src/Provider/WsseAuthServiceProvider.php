<?php

namespace Guigzz\Wsse\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guigzz\Wsse\WsseProvider;
use Guigzz\Wsse\WsseListener;

class WsseAuthServiceProvider implements ServiceProviderInterface {
    
    private static $DEFAULT_SECURITY_DIR = '/../../cache/security';
    private static $DEFAULT_VALID_TIME_WINDOW = 300; // in seconds
    
    public function register(Application $app) {
        // Define custom service provider for WSSE auth
        // For more info:
        // http://symfony.com/doc/current/cookbook/security/custom_authentication_provider.html
        // http://silex.sensiolabs.org/doc/providers/security.html
        $app['security.authentication_listener.factory.wsse'] = $app->protect(function ($name, $options) use ($app) {
            // define the authentication provider object
            $app['security.authentication_provider.' . $name . '.wsse'] = $app->share(function () use ($app) {
                $securityDir = $app['wsse.security_dir'] ? $app['wsse.security_dir'] : __DIR__ . self::$DEFAULT_SECURITY_DIR;
                $timeWindow = $app['wsse.valid_time_window'] ? $app['wsse.valid_time_window'] : self::$DEFAULT_VALID_TIME_WINDOW;
                return new WsseProvider($app['wsse.user'], $securityDir, $timeWindow);
            });

            // define the authentication listener object
            $app['security.authentication_listener.' . $name . '.wsse'] = $app->share(function () use ($app) {
                return new WsseListener($app['security.token_storage'], $app['security.authentication_manager']);
            });

            return array(
                // the authentication provider id
                'security.authentication_provider.' . $name . '.wsse',
                // the authentication listener id
                'security.authentication_listener.' . $name . '.wsse',
                // the entry point id
                null,
                // the position of the listener in the stack
                'pre_auth'
            );
        });
    }
    
    public function boot(Application $app)
    {
        
    }
}

