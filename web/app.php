<?php

require __DIR__.'/../vendor/autoload.php';

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = new SilexApplication();
$app['debug'] = 1;

if (class_exists('\Gmagick')) {
    $app['imagine.driver'] = 'Gmagick';
} elseif (class_exists('\Imagick')) {
    $app['imagine.driver'] = 'Imagick';
} else {
    $app['imagine.driver'] = 'Gd';
}
$classname = sprintf('Imagine\%s\Imagine', $app['imagine.driver']);

$app['imagine'] = new $classname;

$app->get('/', function(Request $request) {
    return 'Hi!';
});
/*
*/
$app->get('/{sizes}/{fwidth}/{format}/', function(Request $request, $sizes, $fwidth, $format) use ($app) {
    /** @var \Imagine\Image\AbstractImagine $imagine */
    $imagine = $app['imagine'];

    $wkHtmlBinary = __DIR__.'/../vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64';

    $url = $request->server->get('QUERY_STRING');
    if (!$url) {
        throw new NotFoundHttpException('Empty url');
    }

    $pars = parse_url($url);
    if (!isset($pars['scheme']) || !in_array($pars['scheme'], ['http', 'https'])) {
        return new NotFoundHttpException('Wrong schema');
    }


    $sizes = explode('x', $sizes);
    $width = 0;
    $height = 0;
    if (count($sizes) == 1) {
        $width = $sizes[0];
    } else {
        list($width, $height) = $sizes;
    }

    $width = min($width, 1980);
    $height = min($height, 1980);

    $width = max($width, 1024);

    $arguments = [$wkHtmlBinary];
    if ($width) {
        $arguments[] = '--width';
        $arguments[] = $width;
    }
    if ($height) {
        $arguments[] = '--height';
        $arguments[] = $height;
    }

    $arguments[] = $url;

    $file = __DIR__.'/../files/'.md5($url.implode('x', $sizes).$fwidth).'.'.$format;

    $arguments[] = $file;

    if (file_exists($file)) {
        return new BinaryFileResponse($file);
    }

    $process = ProcessBuilder::create($arguments)->getProcess();

    $ret = $process->run();

    if (!file_exists($file)) {
        throw new NotFoundHttpException(' Error! ' );
    }

    if ($width > $fwidth) {
        $image = $imagine->open($file);
        $image->resize($image->getSize()->widen($fwidth))->save();
    }



    return new BinaryFileResponse($file);
})
    ->assert('sizes', '\d+|\d+x\d+')
    ->assert('width', '\d+')
    ->assert('format', 'jpg|png');

$app->run();