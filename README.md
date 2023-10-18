Microsoft Entra Sample Api Request
==========

php版简单microsoft entra应用，包括oauth2登录鉴权获取token，api请求

# Usage

composer require zatxm/microsoft-entra

1、请求授权代码

```php
/**
 * 请求授权代码
 */
use Zatxm\MicrosoftEntra\OAuth;

$oauth = new OAuth([
    'clientId' => 'xxxx', //应用程序ID
    'clientSecret' => 'xxxx', //应用程序秘钥
    'scope'        => 'openid profile offline_access user.read', //权限内容
    'oauthRedirectUri' => 'http://localhost:9003', //配置的回调地址
    // 鉴权接口地址,默认为以下三个可以不传
    'oauthAuthority'   => 'https://login.microsoftonline.com/common',
    'oauthAuthorizeEndpoint' => '/oauth2/v2.0/authorize',
    'oauthTokenEndpoint'     => '/oauth2/v2.0/token'
]);
// 应该执行跳转此url，它会要求你登录、授权并跳转到回调url
// 跳转到回调url，您应该验证state，state可以通过下面获取
// 回调会返回code，一般10分钟有效，您应该用它来获取token
// 您应该可以存储code和以下的tag，因为他们可用与获取token
$authUrl = $oauth->getAuthorizationUrl();
// tag会返回如['state'=>'xx', 'codeVerifier'=>'xx', 'codeChallenge'=>'xx'];
$tag = $oauth->getTag();
```

2、获取access token

```php
/**
 * 获取access token
 */
use Zatxm\MicrosoftEntra\OAuth;

$oauth = new OAuth([
    'clientId' => 'xxxx', //应用程序ID
    'clientSecret' => 'xxxx', //应用程序秘钥
    'scope'        => 'openid profile offline_access user.read', //权限内容
    'oauthRedirectUri' => 'http://localhost:9003', //配置的回调地址
]);
// 微软回调返回的code值，有效期一般10分钟
$authorizationCode = 'xx';
// 可以通过上步的tag取得
$codeVerifier = 'xx';
$accessTokenArr = $oauth->getAccessToken($authorizationCode, $codeVerifier);
$accessToken = $accessTokenArr['access_token']; //可能有error错误码
```

3、请求entra应用api如graph

```php
/**
 * 发送邮件
 */
use Zatxm\MicrosoftEntra\EntraApi;

$accessToken = 'xx'; //上步获取的access token
$graphApiEndpoint = 'https://graph.microsoft.com/v1.0'; //接口地址，可以不传默认就是这个
$api = new EntraApi($accessToken, $graphApiEndpoint);
$api->setAccessToken('xxxx'); //可以设置新的token
$params = [
    'message' => [
        'subject' => '邮件标题',
        'body'    => [
            'contentType' => 'html',
            'content'     => '邮件内容'
        ],
        'toRecipients' => [['emailAddress'=>['address'=>'xxxx']]]
    ],
    'saveToSentItems' => true
];
$res = $api->go('/me/sendMail', $params, 'POST');
```

4、access token过期可以通过刷新token重新获取

```php
/**
 * 刷新获取access token
 */
use Zatxm\MicrosoftEntra\OAuth;

$oauth = new OAuth([
    'clientId' => 'xxxx', //应用程序ID
    'clientSecret' => 'xxxx', //应用程序秘钥
    'scope'        => 'openid profile offline_access user.read', //权限内容
    'oauthRedirectUri' => 'http://localhost:9003', //配置的回调地址
]);
$refreshToken = 'xx'; //refresh_token
$accessTokenArr = $oauth->getAccessTokenRefresh($refreshToken);
$accessToken = $accessTokenArr['access_token']; //可能有error错误码
```

# License

MIT
