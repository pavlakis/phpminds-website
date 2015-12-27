<?php
// DIC configuration

$container = $app->getContainer();
$injector = new PHPMinds\Service\InjectorService($container);

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->withRedirect('/404');
    };
};

/* ---------- Configs ------------ */

$container['PHPMinds\Config\EventsConfig'] = function ($c) {

    return new PHPMinds\Config\EventsConfig($c->get('settings')['events']);

};


$container['meetup.config'] = function ($c) {
    $meetup = $c->get('settings')['meetups'];

    return new PHPMinds\Config\MeetupConfig([
        'apiKey'        => $meetup['apiKey'],
        'baseUrl'       => $meetup['baseUrl'],
        'groupUrlName'  => $meetup['PHPMinds']['group_urlname'],
        'publishStatus' => $meetup['publish_status']
    ]);

};

$container['joindin.config'] = function ($c) {
    $joindin = $c->get('settings')['joindin'];

    return new PHPMinds\Config\JoindinConfig([
        'apiKey'            => $joindin['key'],
        'baseUrl'           => $joindin['baseUrl'],
        'frontendBaseUrl'   => $joindin['frontendBaseUrl'],
        'callback'          => $joindin['callback'],
        'username'          => $joindin['username']
    ]);
};

$container['meetup.event'] = function ($c) {
    return new \PHPMinds\Model\MeetupEvent($c->get('meetup.config'));
};

$container['joindin.event'] = function ($c) {

    return new \PHPMinds\Model\JoindinEvent(
        $c->get('joindin.config'), $c->get('PHPMinds\Repository\FileRepository')
    );
};

$container['parsedown'] = function($c)
{
    return new Parsedown();
};

$container['service.joindin'] = function ($c) {
    return new \PHPMinds\Service\JoindinService($c->get('http.client'), $c->get('joindin.event'));
};

$container['service.meetup'] = function ($c) {
    return new \PHPMinds\Service\MeetupService($c->get('http.client'), $c->get('meetup.event'));
};


$container['PHPMinds\Service\ContentService'] = function ($c) {
    $content = $c->get('settings')['content-folder'];
    return new \PHPMinds\Service\ContentService($c->get('parsedown'),$content['location']);
};

$container['PHPMinds\Service\EventsService'] = function ($c) {
    return new \PHPMinds\Service\EventsService(
        $c->get('service.meetup'),
        $c->get('service.joindin'),
        $c->get('PHPMinds\Model\Event\EventManager')
    );
};


$container['http.client'] = function ($c) {
    return new \GuzzleHttp\Client();
};

$container['Slim\HttpCache\CacheProvider'] = function () {
    return new \Slim\HttpCache\CacheProvider();
};

$container ['db'] = function ($c) {
    $db = $c->get('settings')['db'];

    return new \PHPMinds\Model\Db (
        'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['username'], $db['password']
    );
};

// Repositories

$container['PHPMinds\Repository\FileRepository'] = function ($c) {
    return new \PHPMinds\Repository\FileRepository(
        $c->get('settings')['file_store']['path']
    );
};

$container['PHPMinds\Repository\UsersRepository'] = function ($c) {
    return new PHPMinds\Repository\UsersRepository($c->get('db'));
};

$container['PHPMinds\Repository\SpeakersRepository'] = function ($c) {
    return new \PHPMinds\Repository\SpeakersRepository($c->get('db'));
};

$container['PHPMinds\Repository\EventsRepository'] = function ($c) {
    return new \PHPMinds\Repository\EventsRepository($c->get('db'));
};

$container['PHPMinds\Repository\SupportersRepository'] = function ($c) {
    return new \PHPMinds\Repository\SupportersRepository($c->get('db'));
};

// Managers

$container['PHPMinds\Model\Event\EventManager'] = function ($c) {
    return new PHPMinds\Model\Event\EventManager(
        $c->get('PHPMinds\Repository\EventsRepository'),
        $c->get('PHPMinds\Repository\SpeakersRepository'),
        $c->get('PHPMinds\Repository\SupportersRepository')
    );
};

$container['PHPMinds\Middleware\AuthCheck'] = function ($c) {
    return new PHPMinds\Middleware\AuthCheck($_SESSION, 'auth', $c->get('settings')['auth-routes']);

};

$container['Slim\Csrf\Guard'] = function ($c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf_status", false);
        return $next($request, $response);
    });
    return $guard;
};

$container['PHPMinds\Model\Auth'] = function ($c) {
    return new PHPMinds\Model\Auth(
        $c->get('PHPMinds\Repository\UsersRepository')
    );
};

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// Twig
$container['Slim\Views\Twig'] = function ($c) {
    $settings = $c->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

    // Add extensions
    $view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());

    return $view;
};

// Flash messages
$container['Slim\Flash\Messages'] = function ($c) {
    return new \Slim\Flash\Messages;
};

// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// monolog
$container['Psr\Log\LoggerInterface'] = function ($c) {
    $settings = $c->get('settings');
    $logger = new \Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));
    return $logger;
};

// -----------------------------------------------------------------------------
// Action factories
// -----------------------------------------------------------------------------




$injector->add('PHPMinds\Action\NotFoundAction');
$injector->add('PHPMinds\Action\HomeAction');
$injector->add('PHPMinds\Action\LoginAction');
$injector->add('PHPMinds\Action\LogoutAction');
$injector->add('PHPMinds\Action\AdminDashboardAction');
$injector->add('PHPMinds\Action\EventDetailsAction');
$injector->add('PHPMinds\Action\CreateSpeakerAction');
$injector->add('PHPMinds\Action\CreateEventAction');
$injector->add('PHPMinds\Action\CallbackAction');
$injector->add('PHPMinds\Action\EventStatusAction');
