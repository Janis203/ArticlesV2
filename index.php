<?php
require 'vendor/autoload.php';

use App\Controller\ArticleController;
use App\Controller\CommentController;
use App\Model\Article;
use App\Model\Comments;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    Environment::class => function () {
        $loader = new FilesystemLoader(__DIR__ . '/templates');
        return new Environment($loader);
    },
    LoggerInterface::class => function () {
        $logger = new Logger('article_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/storage/app.log', Logger::DEBUG));
        return $logger;
    },
    Article::class => DI\create(Article::class)->constructor(DI\get('db')),
    Comments::class => DI\create(Comments::class)->constructor(DI\get('db')),
    ArticleController::class => DI\create(ArticleController::class)
        ->constructor(DI\get(Environment::class), DI\get(LoggerInterface::class), DI\get(Article::class), DI\get(CommentController::class)),
    CommentController::class => DI\create(CommentController::class)
        ->constructor(DI\get(LoggerInterface::class), DI\get(Comments::class)),
    'db' => function () {
        return new SQLite3(__DIR__ . '/database.db');
    }
]);
try {
    $container = $containerBuilder->build();
} catch (Exception $e) {
    die('Could not build DI container');
}

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [ArticleController::class, 'list']);
    $r->addRoute('GET', '/article/{id:\d+}', [ArticleController::class, 'view']);
    $r->addRoute('GET', '/article/create', [ArticleController::class, 'create']);
    $r->addRoute('POST', '/article/store', [ArticleController::class, 'store']);
    $r->addRoute('GET', '/article/edit/{id:\d+}', [ArticleController::class, 'edit']);
    $r->addRoute('POST', '/article/update/{id:\d+}', [ArticleController::class, 'update']);
    $r->addRoute('POST', '/article/delete/{id:\d+}', [ArticleController::class, 'delete']);
    $r->addRoute('POST', '/article/like/{id:\d+}', [ArticleController::class, 'like']);
    $r->addRoute('POST', '/comment/add/{id:\d+}', [CommentController::class, 'add']);
    $r->addRoute('POST', '/comment/like/{id:\d+}/{article_id:\d+}', [CommentController::class, 'like']);
    $r->addRoute('POST', '/comment/delete/{id:\d+}/{article_id:\d+}', [CommentController::class, 'delete']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        try {
            echo $container->get(Environment::class)->render('404.twig');
        } catch (LoaderError|SyntaxError|RuntimeError|DependencyException|NotFoundException $e) {
        }
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        try {
            echo $container->get(Environment::class)->render('405.twig');
        } catch (LoaderError|SyntaxError|RuntimeError|NotFoundException|DependencyException $e) {
        }
        break;
    case FastRoute\Dispatcher::FOUND:
        [$controller, $method] = $routeInfo[1];
        $vars = $routeInfo[2];
        try {
            $container->call([$container->get($controller), $method], ['vars' => $vars]);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        break;
}