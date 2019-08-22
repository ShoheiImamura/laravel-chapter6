<?php
declare(strict_types=1);

namespace App\Foundation\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use function strval;
use function GuzzleHttp\json_decode;

// Socialite の Laravel\Socialite\Two\AbstractProviderクラスを継承して利用する
final class AmazonProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = [
        'profile'
    ];

    /**
     * 以下４メソッドを実装する必要がある
     * getAuthUrl  OAuth認証を提供しているサービスの認証を提供するURLを文字列で記述
     * getTokenUrl OAuth認証を提供しているサービスのトークンを取得するURLを文字列で記述
     * getUserByToken 取得したトークンを利用して、ユーザ情報を取得するメソッド　取得したユーザー情報を配列で返却する
     * getUserToObject getUserByToken で取得した配列を Laravel\Socilite\Two\Userインスタンスに変換して返却する
     */
    protected function getAuthUrl($state): string
    {
        // OAuth認証を行うURLを記述する
        return $this->buildAuthUrlFromBase('https://www.amazon.com/ap/oa', $state);
    }

    protected function getTokenUrl(): string
    {
        // トークンを取得するURLを記述する
        return 'https://api.amazon.com/auth/o2/token';
    }

    protected function getUserByToken($token): array
    {
        // ユーザ情報を取得する
        $response = $this->getHttpClient()
            ->get('https://api.amazon.com/user/profile', [
                'headers' => [
                    'x-amz-access-token' => $token,
                ]
            ]);
        return json_decode(strval($response->getBody()), true);
    }

    protected function mapUserToObject(array $user): User
    {
        // 結果を Laravel\Socialite\Two\Userインスタンスに渡して返却
        return (new User())->setRaw($user)->map([
            'id'       => $user['user_id'],
            'nickname' => $user['name'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => '',
        ]);
    }

    protected function getTokenFields($code): array
    {
        return parent::getTokenFields($code) + [
                'grant_type' => 'authorization_code'
            ];
    }
}
