<?php
/**
 * 代表的是  swoole里面后续所有task异步任务
 * Date: 18/3/27
 * Time: 上午1:20
 */

namespace app\common\lib\task;

use app\common\lib\ali\Sms;
use app\common\lib\redis\Predis;
use app\common\lib\Redis;

class Task
{

    /**
     * 异步发送 验证码
     * @param $data
     * @param $serv swoole server对象
     */
    public function sendSms($data, $serv)
    {
        try {
            $response = Sms::sendSms($data['phone'], $data['code']);
        } catch (\Exception $e) {
            // todo
            return false;
        }

        // 如果发送成功 把验证码记录到redis里面
        if ($response->Code === "OK") {
            Predis::getInstance()->set(Redis::smsKey($data['phone']), $data['code'], config('redis.out_time'));
        } else {
            return false;
        }

        return true;
    }

    /**
     * 通过task机制发送赛况实时数据给客户端
     * @param $data
     * @param $serv swoole server对象
     */
    public function pushLive($data, $serv)
    {
//        $clients = Predis::getInstance()->sMembers(config("redis.live_game_key"));

//        foreach ($clients as $fd) {
//        print_r($serv);
        foreach ($serv->ports[1]->connections as $fd) {
            if ($serv->isEstablished($fd)) {
                $serv->push($fd, json_encode($data));
            }
        }
    }

    public function chat($data, $serv)
    {
//        print_r($serv->ports[2]);
        foreach ($serv->ports[2]->connections as $fd) {
            if ($serv->isEstablished($fd)) {
                $serv->push($fd, json_encode($data));
            }
//            $_POST['http_server']->push($fd, json_encode($data));
        }
    }
}