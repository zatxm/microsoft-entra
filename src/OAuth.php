<?php
/**
 * 微软身份平台和OAuth 2.0授权代码流
 * https://learn.microsoft.com/zh-cn/azure/active-directory/develop/v2-oauth2-auth-code-flow
 */
namespace Zatxm\MicrosoftEntra;

use Zatxm\YRequest\Curl;
use Zatxm\YRequest\CurlErr;

class OAuth
{
    // 基本配置
    private $option = [
        'clientId' => '', //应用程序ID
        'clientSecret' => '', //应用程序秘钥
        'scope'        => '', //权限内容，如openid profile offline_access user.read
        'oauthRedirectUri' => '', //配置的回调地址
        // 鉴权接口地址
        'oauthAuthority'   => 'https://login.microsoftonline.com/common',
        'oauthAuthorizeEndpoint' => '/oauth2/v2.0/authorize',
        'oauthTokenEndpoint'     => '/oauth2/v2.0/token'
    ];
    // 保存state、code_verifier、code_challenge
    private $tag = [];

    public function __construct(array $option = [])
    {
        $this->option = array_merge($this->option, $option);
        if (
            empty($this->option['clientId']) ||
            empty($this->option['clientSecret']) ||
            empty($this->option['scope']) ||
            empty($this->option['oauthRedirectUri']) ||
            empty($this->option['oauthAuthority']) ||
            empty($this->option['oauthAuthorizeEndpoint']) ||
            empty($this->option['oauthTokenEndpoint'])
        ) {
            throw new \Exception('option config error');
        }
    }

    /**
     * 获取state、code_verifier、code_challenge
     * @return array
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * 请求授权代码url
     * @return string
     */
    public function getAuthorizationUrl()
    {
        $state = $this->generateCodeVerifier();
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);
        $this->tag = ['state'=>$state, 'codeVerifier'=>$codeVerifier, 'codeChallenge'=>$codeChallenge];
        $query = [
            'client_id' => $this->option['clientId'],
            'response_type' => 'code',
            'redirect_uri'  => $this->option['oauthRedirectUri'],
            'response_mode' => 'query',
            'scope'         => $this->option['scope'],
            'state'         => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256'
        ];
        return $this->option['oauthAuthority'] . $this->option['oauthAuthorizeEndpoint'] . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 获取access token
     * @param  string $authorizationCode 微软回调返回码
     * @param  string $codeVerifier      code_verifier
     * @return array
     */
    public function getAccessToken($authorizationCode, $codeVerifier)
    {
        $url = $this->option['oauthAuthority'] . $this->option['oauthTokenEndpoint'];
        // $headers = ['Content-Type'=>'application/x-www-form-urlencoded'];
        $params = [
            'client_id' => $this->option['clientId'],
            'code'      => $authorizationCode,
            'redirect_uri' => $this->option['oauthRedirectUri'],
            'grant_type'   => 'authorization_code',
            'code_verifier' => $codeVerifier,
            'client_secret' => $this->option['clientSecret']
        ];
        $res = Curl::boot()
            ->url($url)
            ->method('POST')
            // ->header($headers)
            ->params($params)
            ->go();
        if (CurlErr::is($res)) {
            return ['error'=>$res->code, 'error_description'=>$res->message];
        }
        return json_decode($res['response']['data'], true);
    }

    /**
     * 刷新获取获取access token
     * @param  string $refreshToken refresh_token
     * @return array
     */
    public function getAccessTokenRefresh($refreshToken)
    {
        $url = $this->option['oauthAuthority'] . $this->option['oauthTokenEndpoint'];
        // $headers = ['Content-Type'=>'application/x-www-form-urlencoded'];
        $params = [
            'client_id' => $this->option['clientId'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_secret' => $this->option['clientSecret']
        ];
        $res = Curl::boot()
            ->url($url)
            ->method('POST')
            // ->header($headers)
            ->params($params)
            ->go();
        if (CurlErr::is($res)) {
            return ['error'=>$res->code, 'error_description'=>$res->message];
        }
        return json_decode($res['response']['data'], true);
    }

    /**
     * 生成code_verifier
     * @param  integer $length 长度
     * @return string
     */
    protected function generateCodeVerifier($length = 64)
    {
        return substr(strtr(base64_encode(random_bytes($length)), '+/', '-_'), 0, $length);
    }

    /**
     * 生成code_challenge
     * @param  string $codeVerifier code_verifier
     * @param  string $method       签名方式S256和plain
     * @return string
     */
    protected function generateCodeChallenge($codeVerifier, $method = 'S256')
    {
        if ($method == 'S256') {
            return trim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        }
        return $codeVerifier;
    }
}
