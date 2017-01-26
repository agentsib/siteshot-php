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

    $arguments[] = '--enable-plugins';
    $arguments[] = '--use-xserver';

    $arguments[] = '--javascript-delay';
    $arguments[] = intval($timeout);

    $arguments[] = '--load-error-handling';
    $arguments[] = 'ignore';

    $arguments[] = $url;

    // For future cache
    $file = __DIR__.'/../cache/'.md5(implode('_', [$url, $width, $height])).'.'.$format;

    $arguments[] = $file;

    $process = ProcessBuilder::create($arguments)->getProcess();


    $process->run();

    if (!file_exists($file)) {
        throw new NotFoundHttpException('Create screenshot exception');
    }

    return $file;
});



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
    ->value('timeout', 't1')
    ->value('mode', 'resize')
    ->assert('mode', 'corner|resize')
    ->assert('timeout', 't\d+')
    ->assert('sizes', '\d+|\d+x\d+')
    ->assert('width', '\d+')
    ->assert('format', 'jpg|png');

$app->run();