<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 18/2/28
 * Time: 上午1:39
 */
$http = new swoole_http_server("0.0.0.0", 8811);

$http->set([
    'enable_static_handler' => true,
    'document_root' => __DIR__ . "/../public/static",
    'worker_num' => 5
]);

$http->on('WorkerStart', function (swoole_server $server, $worker_id) {
    // 定义应用目录
    define('APP_PATH', __DIR__ . '/../application/');
    // ThinkPHP 引导文件
    // 加载基础文件
    require __DIR__ . '/../thinkphp/base.php';
});

$http->on('request', function ($request, $response) use ($http) {
    $_SERVER = [];
    if (isset($request->server)) {
        foreach ($request->server as $key => $val) {
            $_SERVER[strtoupper($key)] = $val;
        }
    }

    if (isset($request->header)) {
        foreach ($request->header as $key => $val) {
            $_SERVER[strtoupper($key)] = $val;
        }
    }

    $_GET = [];
    if (isset($request->get)) {
        foreach ($request->get as $key => $val) {
            $_GET[$key] = $val;
        }
    }

    $_POST = [];
    if (isset($request->post)) {
        foreach ($request->post as $key => $val) {
            $_POST[$key] = $val;
        }
    }

    ob_start();

    try {
        // 执行程序并相应
        think\Container::get('app', [APP_PATH])->run()->send();
        $result = ob_get_contents();
        ob_end_clean();
    } catch (Exception $e) {
        // todo
    }

    $response->end($result);
});

$http->start();