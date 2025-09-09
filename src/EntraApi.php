<?php
/**
 * 微软Entra应用接口，经典如graph
 * https://learn.microsoft.com/zh-cn/graph/
 */
namespace Zatxm\MicrosoftEntra;

use Zatxm\YRequest\Curl;
use Zatxm\YRequest\CurlErr;

class EntraApi
{
    // 通信api地址
    private $graphApiEndpoint = 'https://graph.microsoft.com/v1.0';
    // access token
    private $accessToken = '';
    // curl通信
    private $curl = null;

    public function __construct($accessToken = '', $graphApiEndpoint = '')
    {
        if ($graphApiEndpoint) {
            $this->graphApiEndpoint = rtrim($graphApiEndpoint, '/');
        }
        $this->accessToken = $accessToken;
    }

    /**
     * 设置access token
     * @param string $accessToken access token
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * 通信获取数据
     * @param  string $path   资源path
     * @param  array  $params 参数
     * @param  string $method 请求方式
     * @return array
     */
    public function go($path, $params = [], $method = 'GET')
    {
        if ($this->curl === null) {
            $this->curl = Curl::boot();
        }
        $url = $this->graphApiEndpoint . '/' . ltrim($path, '/');
        $headers = [
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type'  => 'application/json'
        ];
        $this->curl->method($method)->header($headers);
        switch ($method) {
            case 'GET':
                if ($params) {
                    $url .= '?' . (is_array($params) ? http_build_query($params, '', '&', PHP_QUERY_RFC3986) : $params);
                }
                break;
            default:
                if ($params) {
                    $this->curl->params(is_array($params) ? json_encode($params) : $params);
                }
                break;
        }
        $res = $this->curl->url($url)->go();
        if (CurlErr::is($res)) {
            return ['error'=>$res->code, 'error_description'=>$res->message];
        }
        return ['status'=>$res['response']['code'], 'data'=>$res['response']['data']];
    }
}
