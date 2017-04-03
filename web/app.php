<?php

require __DIR__.'/../vendor/autoload.php';

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\ExecutableFinder;
use Imagine\Image\Point;
use Imagine\Image\Box;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = new SilexApplication();
$app['debug'] = getenv("DEBUG") == 1;

if (class_exists('\Gmagick')) {
    $app['imagine.driver'] = 'Gmagick';
} elseif (class_exists('\Imagick')) {
    $app['imagine.driver'] = 'Imagick';
} else {
    $app['imagine.driver'] = 'Gd';
}
$classname = sprintf('Imagine\%s\Imagine', $app['imagine.driver']);

$app['imagine'] = new $classname;

$app['screenshot'] = $app->protect(function($url, &$width = 800, &$height = 600, $timeout = 1, $format = 'png') {

    if (!$width) {
        $width = 800;
    }
    $width = max(250, $width);
    $width = min(1920, $width);
    if (!$height) {
        $height = 600;
    }
    $height = max(250, $height);
    $height = min(1920, $height);
    $pars = parse_url($url);
    if (!isset($pars['scheme']) || !in_array($pars['scheme'], ['http', 'https'])) {
        throw new NotFoundHttpException('Wrong schema');
    }

    $finder = new ExecutableFinder();
    $wkHtmlBinary = $finder->find('wkhtmltoimage');

    if (empty($wkHtmlBinary)) {
        $wkHtmlBinary = __DIR__.'/../vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64';
    }

    $arguments = [$wkHtmlBinary];
    if ($width) {
        $arguments[] = '--width';
        $arguments[] = $width;
    }
    if ($height) {
        $arguments[] = '--height';
        $arguments[] = $height;
    }

    if (getenv('ENABLE_PLUGINS') == 1) {
        $arguments[] = '--enable-plugins';
    } else {
        $arguments[] = '--disable-plugins';
    }

    $arguments[] = '--use-xserver';

    $arguments[] = '--javascript-delay';
    $arguments[] = intval($timeout).'000';

//    $arguments[] = '--stop-slow-scripts';
    $arguments[] = '--no-stop-slow-scripts';

    $arguments[] = '--load-error-handling';
    $arguments[] = 'ignore';

    $arguments[] = '--load-media-error-handling';
    $arguments[] = 'skip';

    $arguments[] = '--disable-smart-width';

    $arguments[] = '--custom-header-propagation';
    $arguments[] = '--custom-header';
    $arguments[] = 'User-Agent';
    $arguments[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';


    $arguments[] = $url;

    // For future cache
    $file = __DIR__.'/../cache/'.md5(implode('_', [$url, $width, $height])).'.'.$format;

    if (file_exists($file)) {
        unlink($file);
    }

    $arguments[] = $file;

    $process = ProcessBuilder::create($arguments)->getProcess();

    $processWaitTime = intval(getenv('PROCESS_WAIT_TIME'));
    if ($processWaitTime > 0) {
        $process->setTimeout(intval($timeout) + $processWaitTime);
    }

    $process->run();

    if (!file_exists($file)) {
        throw new NotFoundHttpException('Screen shot not created');
    }

    return $file;
});

if (!$app['debug']) {
    $app->error(function(HttpException $e){
        return $e->getMessage();
    });
}


$app->get('/', function(Request $request) {
    return new BinaryFileResponse(__DIR__.'/usage.html');
});


$app->get('/{mode}/{sizes}/{fwidth}/{format}/{timeout}', function(Request $request, $mode = 'resize', $sizes, $fwidth, $format, $timeout) use ($app) {
    /** @var \Imagine\Image\AbstractImagine $imagine */
    $imagine = $app['imagine'];

    $url = $request->server->get('QUERY_STRING');
    if (!$url) {
        throw new NotFoundHttpException('Empty url');
    }

    $sizes = explode('x', $sizes);
    $width = 0;
    $height = 0;
    if (count($sizes) == 1) {
        $width = $sizes[0];
    } else {
        list($width, $height) = $sizes;
    }

    $screenFile = new \SplFileInfo($app['screenshot']($url, $width, $height, substr($timeout, 1), $format));

    $file = $screenFile->getRealPath();

    $image = $imagine->open($file);

    if ($width > $fwidth) {
        switch ($mode) {
            case 'resize':
                $image->resize($image->getSize()->widen($fwidth));
                break;
            case 'corner':
                $image->crop(new Point(0, 0), new Box($fwidth, $fwidth));
                break;
        }

    }

    $raw = $image->get($format);

    $response = new \Symfony\Component\HttpFoundation\Response($raw);
    $response->headers->set('Content-Type', 'image/'.$format);

    return $response;
})
    ->value('timeout', 't5')
    ->value('mode', 'resize')
    ->assert('mode', 'corner|resize')
    ->assert('timeout', 't\d+')
    ->assert('sizes', '\d+|\d+x\d+')
    ->assert('width', '\d+')
    ->assert('format', 'jpg|png');

$app->run();