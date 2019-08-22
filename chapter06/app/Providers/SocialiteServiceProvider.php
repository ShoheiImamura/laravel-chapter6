<?php
declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Socialite\AmazonProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteManager;

/**
 * Class SocialiteServiceProvider
 */
class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * @param Factory|SocialiteManager $factory
     */
    public function boot(Factory $factory)// Socialite のサービス・プロバイダは遅延登録される
    {
        // 認証ドライバの追加には、extendメソッドを利用する
        // 第１引数は「認証ドライバ名」、第２引数は「クロージャ」を利用する
        $factory->extend('amazon', function(Application $app) use ($factory) {
            return $factory->buildProvider(
                AmazonProvider::class,
                $app['config']['services.amazon']
            );
        });
    }
}
