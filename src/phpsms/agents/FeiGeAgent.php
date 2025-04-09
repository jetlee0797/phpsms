<?php

namespace Toplan\PhpSms;

/**
 * Class SendCloudAgent
 *
 * @property string $apikey
 * @property string $secret
 * @property string $smsKey
 */
class FeiGeAgent extends Agent implements TemplateSms, VoiceCode
{
    private $status = [
        0 => '短信请求发送成功',
        1 => '登录授权有误',
        15 => '余额不足，请及时充值',
        100 => '内部错误',
        10001 => '参数为空',
        10002 => '参数有误',
        10003 => '发送号码个数限制',
        10004 => '暂无状态报告ID',
        10005 => '暂无上行',
        10006 => '暂无配置通道',
        10007 => '发送频率限制',
        10008 => '超流速限制',
        10009 => '取号失败'
    ];
    public function sendTemplateSms($to, $tempId, array $data)
    {
        $sign_id = config('phpsms.agents.FeiGe.sign_id',0);
        $msg_type = config('phpsms.agents.FeiGe.msg_type',1);

        $sendUrl = 'https://api.4321.sh/sms/template';
        if($msg_type == 2){ //国际短信
            $tempId = config('phpsms.agents.FeiGe.inter_temp_id',0);
            $apikey = config('phpsms.agents.FeiGe.inter_apikey','');
            $secret = config('phpsms.agents.FeiGe.inter_secret','');
            $sendUrl = 'https://api.4321.sh/inter/send';
            $to = str_replace('+','',$to);
        }else{
            $apikey = $this->apikey;
            $secret = $this->secret;
        }
        // dd($sign_id);
        $params = [
            'content'       => $this->getTempDataString($data),
            'mobile'     => $to,
            'sign_id'    => $sign_id,
            'template_id' => $tempId,
            'apikey' => $apikey,
            'secret' => $secret,
        ];
        if($msg_type == 2){
            unset($params['sign_id']);
        }
        // dd($params);
        // dd($params);
        $this->request($sendUrl, $params);
    }

    public function sendVoiceCode($to, $code)
    {
        $params = [
            'phone' => $to,
            'code'  => $code,
        ];
        $this->request('http://api.sendcloud.net/smsapi/sendVoice', $params);
    }

    protected function request($sendUrl, array $params)
    {
        $result = $this->curlPost($sendUrl, $params);
        // dd($result);
        $this->setResult($result);
    }


    protected function setResult($result)
    {
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            if (isset($result['code'])) {
                $code = intval($result['code']);
                if($code !== 0){
                    $this->result(Agent::SUCCESS, false);
                    \Log::info('短信发送失败:'.json_encode($result, JSON_UNESCAPED_UNICODE));
                }else{
                    $this->result(Agent::SUCCESS, true);
                }
                $this->result(Agent::CODE, $code);
                \Log::info('短信发送失败:' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }

    protected function getTempDataString(array $data)
    {
        return implode('||',array_map('strval', $data));
    }
}
