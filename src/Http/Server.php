<?php

namespace Discuz\Http;

use Discuz\Api\ApiServiceProvider;

use Discuz\Cache\CacheServiceProvider;
use Discuz\Foundation\Application;
use Discuz\Http\Middleware\RequestHandler;
use Discuz\Web\WebServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use ErrorException;
use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\Stratigility\Middleware\ErrorResponseGenerator;
use Zend\Stratigility\MiddlewarePipe;

class Server
{
    /**
     * @var string
     */
    private static $reservedMemory;

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function listen()
    {
        $this->siteBoot();

        $pipe = new MiddlewarePipe();

        $pipe->pipe(new RequestHandler([
            '/api' => 'discuz.api.middleware',
            '/' => 'discuz.web.middleware'
        ], $this->app));


        $runner = new RequestHandlerRunner(
            $pipe,
            new SapiEmitter,
            [ServerRequestFactory::class, 'fromGlobals'],
            function (Throwable $e) {
                $generator = new ErrorResponseGenerator;
                return $generator($e, new ServerRequest, new Response);
            }
        );

        $runner->run();
    }

    protected function siteBoot() {

        $this->app->instance('env', 'production');
        $this->app->instance('discuz.config', $this->loadConfig());
        $this->app->instance('config', $this->getIlluminateConfig());

        $this->registerBaseEnv();
//        $this->registerCache();
        $this->registerLogger();

        $this->app->register(HttpServiceProvider::class);
        $this->app->register(FilesystemServiceProvider::class);
        $this->app->register(CacheServiceProvider::class);
        $this->app->register(ApiServiceProvider::class);
        $this->app->register(WebServiceProvider::class);
        $this->app->register(BusServiceProvider::class);
        $this->app->boot();
    }

    private function loadConfig() {
        return include $this->app->basePath('config/config.php');
    }

    private function getIlluminateConfig() {

        $config = new ConfigRepository([
            'view' => [
                'paths' => [
                    resource_path('views'),
                ],
                'compiled' => realpath(storage_path('views')),
            ]
        ]);

        return $config;
    }

//    private function registerCache() {
//
//        $driver = Arr::get($this->app->config('cache'), 'default');
//
//        $driver_name = 'cache.'.$driver.'store';
//
//        $this->app->singleton($driver_name, function () {
//            return new FileStore(new Filesystem, $this->app->storagePath().'cache');
//        });
//
//        $this->app->singleton('cache.store', function($app) {
//
//            return $app['cache']->driver(Arr::get($this->app->config('cache'), 'default'));
//        });
//    }

    private function registerLogger()
    {
        $logPath = storage_path('logs/discuss.log');
        $handler = new RotatingFileHandler($logPath, Logger::INFO);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $this->app->instance('log', new Logger($this->app->environment(), [$handler]));
        $this->app->alias('log', LoggerInterface::class);
    }

    protected function registerBaseEnv() {

        date_default_timezone_set($this->app->config('timezone', 'UTC'));

//        self::$reservedMemory = str_repeat('x', 10240);
//
//        error_reporting(E_ALL);
//
//        set_error_handler([$this, 'handleError']);
//
//        set_exception_handler([$this, 'handleException']);
//
//        register_shutdown_function([$this, 'handleShutdown']);

    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException($e)
    {

        if (! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        try {
            self::$reservedMemory = null;
            $this->getExceptionHandler()->report($e);
        } catch (Exception $e) {
            //
        }
//        if ($this->app->runningInConsole()) {
//            $this->renderForConsole($e);
//        } else {

            $this->renderHttpResponse($e);
//        }
    }

    public function handleShutdown() {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @param  array  $error
     * @param  int|null  $traceOffset
     * @return \Symfony\Component\Debug\Exception\FatalErrorException
     */
    protected function fatalExceptionFromError(array $error, $traceOffset = null)
    {
        return new FatalErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
        );
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        return new Handler;
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }


    protected function renderHttpResponse(Exception $e)
    {
        $this->getExceptionHandler()->render((ServerRequestFactory::fromGlobals()), $e);
    }
}
