<?php

namespace WildWolf;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReCaptcha\ReCaptcha;
use Slim\Views\PhpRenderer;

class ServicesProvider
{
    public function register(\ArrayAccess $container)
    {
        $container['view'] = new PhpRenderer(__DIR__ . '/../templates');

        $container['accountkit'] = function (ContainerInterface $container) {
            return self::accountKit($container);
        };

        $container['recaptcha'] = function (ContainerInterface $container) {
            return self::reCaptcha($container);
        };

        $container['notFoundHandler'] = function (ContainerInterface $container) {
            return function (ServerRequestInterface $request, ResponseInterface $response) use ($container) {
                return $container->get('view')->render($response, '404.phtml')->withStatus(404);
            };
        };

        $container['notAllowedHandler'] = function (ContainerInterface $container) {
            return function (ServerRequestInterface $request, ResponseInterface $response) use ($container) {
                return $response->withHeader('Location', '/')->withStatus(302);
            };
        };

        $error_handler = function (ContainerInterface $container) {
            return function (ServerRequestInterface $request, ResponseInterface $response, \Throwable $error) use ($container) {
                error_log($error);
                return $container->get('view')->render($response, '500.phtml')->withStatus(500);
            };
        };

        $container['phpErrorHandler'] = $error_handler;
        $container['errorHandler']    = $error_handler;
    }

    public static function accountKit(ContainerInterface $container) : AccountKit
    {
        $settings = $container->get('settings');
        $ak       = $settings['accountkit'];

        $accountkit = new AccountKit($ak['appid'], $ak['secret']);
        $accountkit->setApiVersion($ak['apiver']);
        return $accountkit;
    }

    public static function reCaptcha(ContainerInterface $container) : ReCaptcha
    {
        $settings  = $container->get('settings');
        $rc        = $container['settings']['recaptcha'];
        $recaptcha = new ReCaptcha($rc['secret']);
        return $recaptcha;
    }
}