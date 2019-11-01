<?php

use app\common\lib\ali\Sms;
use app\common\lib\Util;
use app\common\lib\task\Task;
use app\common\lib\redis\Predis;

/**
 * http 优化 基础类库
 */
class Server
{

    const HOST = "0.0.0.0";
    const HTTP_PORT = 8080;
    const LIVE_PORT = 8811;
    const CHAT_PORT = 8812;

    public $server = null;

    public function __construct()
    {
        // 获取 sMembers 的值，如果非空，清空之前 redis 服务里面的集合
        // 定义应用目录
        define('APP_PATH', __DIR__ . '/../application/');
        // ThinkPHP 引导文件
        // 加载基础文件
        require_once __DIR__ . '/../thinkphp/start.php';
//        if (Predis::getInstance()->sCard(config('redis.live_game_key'))) {
//            Predis::getInstance()->del(config('redis.live_game_key'));
//        }

        $this->server = new swoole_websocket_server(self::HOST, self::HTTP_PORT);

        $this->server->set([
            'enable_static_handler' => true,
            'document_root' => __DIR__ . "/../public/static",
            'worker_num' => 4,
            'task_worker_num' => 4,
        ]);

        $this->server->addListener(self::HOST, self::LIVE_PORT, SWOOLE_SOCK_TCP);
        $this->server->addListener(self::HOST, self::CHAT_PORT, SWOOLE_SOCK_TCP);

        $this->server->on("open", [$this, 'onOpen']);
        $this->server->on("message", [$this, 'onMessage']);
        $this->server->on("workerStart", [$this, 'onWorkerStart']);
        $this->server->on("request", [$this, 'onRequest']);
        $this->server->on("task", [$this, 'onTask']);
        $this->server->on("finish", [$this, 'onFinish']);
        $this->server->on("close", [$this, 'onClose']);

        $this->server->start();
    }

    /**
     * 监听ws连接事件
     * @param $webSocket
     * @param $request
     */
    public function onOpen($webSocket, $request)
    {
//        echo "当前服务器共有 ".count($webSocket->connections). " 个连接\n";
//        echo "当前服务器共有 ".count($webSocket->ports[0]->connections). " 个连接\n";
//        echo "当前服务器共有 ".count($webSocket->ports[1]->connections). " 个连接\n";
//        echo "当前服务器共有 ".count($webSocket->ports[2]->connections). " 个连接\n";

//        Predis::getInstance()->sAdd(config('redis.live_game_key'), $request->fd);
        print_r("连接成功，用户 id 为 {$request->fd}。" . PHP_EOL);
    }

    /**
     * 监听ws消息事件
     * @param $webSocket
     * @param $frame
     */
    public function onMessage($webSocket, $frame)
    {
//        echo "服务器发送: {$frame->data}" . PHP_EOL;
        $data = [
            'task' => 1,
            'fd' => $frame->fd,
        ];
//        $webSocket->task($data);
//        $webSocket->push($frame->fd, $frame->data);
    }

    /**
     * workerStart 回调
     * @param swoole_server $server
     * @param $worker_id
     */
    public function onWorkerStart(swoole_server $server, $worker_id)
    {
//        // 定义应用目录
//        define('APP_PATH', __DIR__ . '/../application/');
//        // ThinkPHP 引导文件
//        // 加载基础文件
//        require __DIR__ . '/../thinkphp/start.php';
    }

    /**
     * request 回调
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response)
    {
//        // 定义应用目录
//        define('APP_PATH', __DIR__ . '/../application/');
//        // ThinkPHP 引导文件
//        // 加载基础文件
//        require_once __DIR__ . '/../thinkphp/start.php';

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

        $_FILES = [];
        if (isset($request->files)) {
            foreach ($request->files as $key => $val) {
                $_FILES[$key] = $val;
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

        $_POST['http_server'] = $this->server;
//print_r($this->server);
        ob_start();

        try {
            // 执行程序并相应
            think\Container::get('app', [APP_PATH])->run()->send();
        } catch (Exception $e) {
            // todo
            echo $e->getMessage();
        }
        $result = ob_get_contents();

        ob_end_clean();

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
        $func = $data['method'];

        $taskInstance = (new Task())->$func($data['data'], $serv);

        return "任务完成"; // 告诉worker
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
     * @param $webSocket
     * @param $fd
     */
    public function onClose($webSocket, $fd)
    {
//        Predis::getInstance()->sRem(config('redis.live_game_key'), $fd);
        echo "用户: {$fd} 退出" . PHP_EOL;
    }
}

new Server();
