<?php

use app\common\lib\ali\Sms;
use app\common\lib\Util;
use app\common\lib\task\Task;

/**
 * http 优化 基础类库
 */
class Http
{

    CONST HOST = "0.0.0.0";
    CONST PORT = 8811;

    public $http = null;

    public function __construct()
    {
        $this->http = new swoole_http_server(self::HOST, self::PORT);

        $this->http->set([
            'enable_static_handler' => true,
            'document_root' => __DIR__ . "/../public/static",
            'worker_num' => 4,
            'task_worker_num' => 4,
        ]);

        $this->http->on("workerStart", [$this, 'onWorkerStart']);
        $this->http->on("request", [$this, 'onRequest']);
        $this->http->on("task", [$this, 'onTask']);
        $this->http->on("finish", [$this, 'onFinish']);
        $this->http->on("close", [$this, 'onClose']);

        $this->http->start();
    }

    /**
     * workerStart 回调
     * @param swoole_server $server
     * @param $worker_id
     */
    public function onWorkerStart(swoole_server $server, $worker_id)
    {
        // 定义应用目录
        define('APP_PATH', __DIR__ . '/../application/');
        // ThinkPHP 引导文件
        // 加载基础文件
        require __DIR__ . '/../thinkphp/start.php';
    }

    /**
     * request 回调
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response)
    {
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

        $_POST['http_server'] = $this->http;

        ob_start();

        try {
            // 执行程序并相应
            think\Container::get('app', [APP_PATH])->run()->send();
            $result = ob_get_contents();
            ob_end_clean();
        } catch (Exception $e) {
            // todo
            echo $e->getMessage();
        }

        $response->end($result);
    }

    /**
     * @param $serv
     * @param $taskId
     * @param $workerId
     * @param $data
     */
    public function onTask($serv, $taskId, $workerId, $data)
    {
        // 分发task任务，不同的任务走不同逻辑
        $flag = $taskInstance = (new Task())->$data['method']();

        return "on task finish"; // 告诉worker
    }

    /**
     * @param $serv
     * @param $taskId
     * @param $data
     */
    public function onFinish($serv, $taskId, $data)
    {
        echo "taskId:{$taskId}\n";
        echo "finish-data-sucess:{$data}\n";
    }

    /**
     * close
     * @param $http
     * @param $fd
     */
    public function onClose($http, $fd)
    {
        echo "clientid:{$fd}\n";
    }
}

new Http();
