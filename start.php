<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\ServerSentEvents;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/vendor/autoload.php';

$sse = new Worker('http://0.0.0.0:3000');
$sse->name = 'SSE';
$sse->onWorkerStart = static function($sse)
{
    // 把进程句柄存储起来，在进程关闭的时候关闭句柄
    $sse->stats = popen('vmstat 1 -n', 'r');
    if($sse->stats)
    {
        $handle_connection = new TcpConnection($sse->stats);
        $handle_connection->onMessage = static function($handle_connection, $data) use ($sse)
        {
            foreach($sse->connections as $connection) {
                $connection->send(new ServerSentEvents(['event' => 'stats', 'data' => $data]));
            }
        };
    }
    else
    {
       echo "vmstat 1 fail\n";
    }
};

$sse->onMessage = static function(TcpConnection $connection, Request $request)
{
    if ($request->header('accept') === 'text/event-stream') {
        $connection->send(new Response(200,
                                        ['Content-Type' => 'text/event-stream',
                                                'Access-Control-Allow-Origin' => '*']));
        return;
    };
    
    $connection->close('bye');
};

$sse->onWorkerStop = static function($sse)
{
    pclose($sse->stats);
};

// WebServer，用来给浏览器吐html js css
$web = new Worker("http://0.0.0.0:55555");
// WebServer数量
$web->count = 2;

$web->name = 'web';

define('WEBROOT', __DIR__ . '/Web');

$web->onMessage = static function (TcpConnection $connection, Request $request) {
    $path = $request->path();
    if ($path === '/') {
        $connection->send(exec_php_file(WEBROOT.'/index.php'));
        return;
    }
    $file = realpath(WEBROOT. $path);
    if (false === $file) {
        $connection->send(new Response(404, array(), '<h3>404 Not Found</h3>'));
        return;
    }
    // Security check! Very important!!!
    if (!str_starts_with($file, WEBROOT)) {
        $connection->send(new Response(400));
        return;
    }
    if (\pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $connection->send(exec_php_file($file));
        return;
    }

    $if_modified_since = $request->header('if-modified-since');
    if (!empty($if_modified_since)) {
        // Check 304.
        $info = \stat($file);
        $modified_time = $info ? \date('D, d M Y H:i:s', $info['mtime']) . ' ' . \date_default_timezone_get() : '';
        if ($modified_time === $if_modified_since) {
            $connection->send(new Response(304));
            return;
        }
    }
    $connection->send((new Response())->withFile($file));
};

function exec_php_file($file) {
    \ob_start();
    // Try to include php file.
    try {
        include $file;
    } catch (\Exception $e) {
        echo $e;
    }
    return \ob_get_clean();
}


if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
