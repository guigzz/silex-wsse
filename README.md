# Silex WSSE authentication

This library provides an implementation of WSSE authentication based on the [Symfony2 documentation](http://symfony.com/doc/current/cookbook/security/custom_authentication_provider.html).

## Installation

#### Composer

```json
"require": {
	"guigzz/silex-wsse": "~0.1.0"
}
```

## Usage

* Register the Guigzz\Wsse\WsseAuthServiceProvider
* Pass you own User provider to the Wsse provider
* Use it in your security firewall config
* You are done!

A basic config example would look like this:

```php
$app->register(new Guigzz\Wsse\WsseAuthServiceProvider(), array(
    'wsse.security_dir'         => __DIR__ . '/../cache/security',
    'wsse.valid_time_window'    => 300,
    'wsse.user'                 => $app->share(function ($app) { return $app['dao.user']; })
));
```

And use it in your security firewall like this:

```php
$app['security.firewalls'] = array(
    'api'   => array(
        'pattern'   => '^/api/',
        'stateless' => true,
        'wsse'      => true,
    )
);
```
#### Configuration

* 'wsse.security_dir' (optional): where to store auth cache infos (default to your-app-root-dir/cache/security)
* 'wsse.valid_time_window' (optional): time in seconds of a WSSE validation window (default to 300s). For more infos, please read the Symfony2 documentation about Wsse.
* 'wsse.user' (mandatory): your app's user provider, so as to authenticate incoming requests.

## License

This project is under the [MIT License](https://opensource.org/licenses/MIT).
