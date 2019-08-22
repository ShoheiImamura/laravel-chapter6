---
theme: "white"
transition: "default"
progress: true
slideNumber: true
loop: true
backgroundTransition: 'zoom'



---

# 6章

## 認証と認可

---

## はじめに

- 本資料は、Weeyble Laravel 輪読会用の資料です
- 対応箇所は、6章の後半(6-4, 6-5)部分のみです
- 権利関係で問題がございましたら、ご指摘ください
- このスライドは reveal.js で閲覧することを前提に作成しています
  - 参考：[非エンジニアのためのお手軽reveal.js入門](https://jyun76.github.io/revealjs-vscode/)

--

## 発表者自己紹介

- 今村昌平と申します。
- Web業務システム受託の会社で勤務(1年半)
- 個人でWebアプリ作成中（プロダクト利用シーンの紹介&FAQ）
    - プロトタイプ https://torch-group.gitlab.io/torch-official

--

## 本日の概要


- 6-4 OAuthクライアントによる認証・認可　　　　　　　
- 6-5 認可処理　　　　　

---

### 6-4 OAuthクライアントによる認証・認可

- 外部サービスに認証を委託し、アプリケーション側ではユーザ情報だけを管理する仕組みを「OAuth認証」という
- [一番分かりやすい OAuth の説明](https://qiita.com/TakahikoKawasaki/items/e37caf50776e00e733be)

--

### Socialite

- laravel のパッケージ
- FaceBook, twitter, LinkedIn, Google 等のOauth認証ドライバを用意

### Socialiteのインストール

    composer require laravel/socialite

- config/services.php に設定キーを記述する

---

## Github OAuth 認証

- GithubのOAuth認証を例として実装を確認

![](https://user-images.githubusercontent.com/39234750/63458428-c1a8aa80-c48d-11e9-90af-de42a289c963.png)
![](https://user-images.githubusercontent.com/39234750/63458526-e7ce4a80-c48d-11e9-9fb0-e4fe5266847a.png)
![](https://user-images.githubusercontent.com/39234750/63458587-059baf80-c48e-11e9-9e0d-2cc7492ae3d8.png)
![](https://user-images.githubusercontent.com/39234750/63458657-25cb6e80-c48e-11e9-80a0-b5c75999c910.png)

--

### Githubを利用する例

[amazon](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/config/services.php#L38-L41)

--

### 外部サービス認証ページへのリダイレクト

- URLは２つある
    1. サービス → 外部サービスの認証ページ(URL)
    2. 認証ページ → サービス(URL)
- Socialite で用意されているメソッド（driver, with）を用いる

```php
use Laravel\Socialite\Contracts\Factory;

final class RegisterAction extends Controller
{
    public function __invoke(Factory $factory):
    {
        // driver メソッドで 'github' を指定する
        return $factory->driver('github')->redirect();
        // with メソッドも利用可能
    }
}
```

--

## 外部サービスからコールバック

- Socilalite 経由でユーザ情報を取得できる

```php

use Laravel\Socialite\Contracts\Factory;

final class CallbackAction extends Controller
{
    public function __invoke(
        Factory $factory,
        AuthManager $authManager
    ){
        // コールバック時に Socialiteのメソッドを介してユーザ情報を取得
        $user = $factory->driver('github')->user();
    }
}
```

--

### 動作拡張

- Socialite パッケージのOauth認証ドライバの動作拡張オプション
  - 通信内容のログ出力
  - アクセス権のスコープ追加
  - ユーザ情報アクセス時にパラメータ追加
  - ステートレス

--

### 通信内容をログとして出力

```php
use GuzzleHttp\Middleware;
final class CallbackAction extends Controller
{
    public function __invoke(
        Factor $factory,
        AuthManager $authManager,
        LoggerInterface $log
    ) {
        $driver = $factory->driver('github');
        $user = $driver->setHttpClient(
            // 略
        )->user();
    }
}
```

--

### アクセス権のスコープ追加

- scopes メソッドでアクセス権を追加
- setScopesメソッドでアクセス権を再設定

---

### OAuth ドライバの追加

- Socialite Providersに用意されていない外部サービスでも、独自の認証ドライバを追加可能
- amazon のOAuth 認証ドライバを追加する方法で説明

--

## ドライバ追加の流れ

1. アプリケーション登録
    - Amazon へアプリケーション情報を登録
    - Client ID と Client Secret を発行
    - 発行情報を config/servers.php へ登録
2. Amazon OAuth 認証ドライバの実装
    - 認証ドライバを実装
    - 認証ドライバをサービスプロバイダに登録
    - 認証ドライバを利用

- ソースコード
  - [リスト6.2.3.6：config/auth.phpへの追記](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/config/auth.php)
  - [リスト6.4.4.3：Amazon OAuth 認証ドライバ実装例](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Foundation/Socialite/AmazonProvider.php)
  - [リスト6.4.4.4：Socialiteを拡張してドライバを追加](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Providers/SocialiteServiceProvider.php)
  - [リスト6.4.4.5：amazonドライバの利用例](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Http/Controllers/Register/RegisterAction.php)

---

## 6-5 認可処理

- アプリケーションを利用するユーザに対して、リソースや機能に利用制限を設けて制御する仕組み

--

### 6-5-1 認可処理を理解する

- Laravel では２種類の認可処理を利用できる
  - Gate (１つの認可処理に名前をつけて利用の可否を決定)
  - Policy (複数の認可処理を記述する)
- App\Providers\AuthServiceProvider クラスに記述
- 認可処理インターフェイスは Illuminate\auth\Access\Gate インターフェイスの実装

---

### 6-5-2 認可処理

- 認可処理を認証処理から切り出すことで、クラスの巨大化を防ぐ
- define メソッドで認可処理を定義する
  - 第一引数：名前（文字列）
  - 第二引数：認可処理（クロージャ）
- 第２引数には「クラス名@メソッド名」「クラス名」のような記述も可能

```php
// Gate の実装例
use Illuminate\Contracts\auth\Access\Gate as GateContract;
class AuthServiceProvider extends ServiceProvider
{
    public function boot(GateContract $gate)
    {
        $this->registerPolicies();
        // define メソッドで、認可処理と名前を紐付ける
        // 第１引数は文字列、第２引数はクロージャ
        $gate->define('user-access', function(User $user, $id){
            return intval($user->getAuthIdentifier()) === intval($id);
        });
        // または \Gate::define('user-access', ... )
    }
}
```
```php
// クロージャの第一引数は Illuminate\Contracts\Auth\Authenticatable
// インターフェイスを実装したクラスのインスタンス
// 第２引数は 認可処理を利用する際の allow メソッド check メソッドの引数
$gate->define('user-access', function(User $user, $id){
    return intval($user->getAuthIdentifier()) === intval($id);
});

// 略

// Gate のdefine メソッドで記述した処理を実行する
if($this->gate->allow('user-access', $id)) {
    // 実行が許可される場合に実行
}
```

--

### 一つの認可処理を一つのクラスとして実装する例

- 認可処理定義(define)時に、第２引数にクラスのインスタンスを指定する
- [リスト6.5.2.3](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Gate/UserAccess.php)

```php
public function boot(GateContract $gate)
{
    $this->registerPolicies();
    $gate->define('user-access', new UserAccess());
}
```

--

### before メソッドを利用した認可処理ロギング

- 認可処理を実行する前に動作させたい処理がある場合に利用する
- 「アクセスログの保管」等
- [リスト6.5.2.5](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Providers/AuthServiceProvider.php)

--

### ポリシー

- リソースに対する認可処理をまとめて記述する仕組み
- artisan コマンドを利用して作成する

```shell
$ php artisan make:policy ContentPolicy
```

- Eloquent モデルが記述されたメソッドを含むポリシークラスの作成

```shell
$ php artisan make:policy ContentPolicy --model=Content
```

```php
use App\User;
use App\Content;

class ContentPolicy
{
    public function view(User $user, Content $content){}

    public function create(User $user, Content $content){}

    public function update(User $user, Content $content){}

    public function delete(User $user, Content $content){}

    public function restore(User $user, Content $content){}

    public function forceDelete(User $user, Content $content){}
}
```

--

### 作成したポリシークラスとEloquent を紐付ける

```php
protected $policy = [
    \App\content::class => \app\Policies\contentPolicy::class,
];
```

- [AuthServiceProvider.php](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Providers/AuthServiceProvider.php)

--

### Eloquent モデル経由の認可処理実行例

- [RetrieveAction.php](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Http/Controllers/User/RetrieveAction.php)

--

### Eloquent モデルを利用しない場合

- stdClass を用いる

---

## 6-5-3 Bladeテンプレートによる認可処理

- 描画内容の操作が可能
    - @can や @cannot ディレクティブを利用

```blade
@can('edit, $content)
    // コンテンツ編集のためのボタンなど表示
@elsecan('create', App\Content::class)
    // コンテンツ作成のための描画が行われる
@endcan
```

- [リスト6.5.3.2：Bladeテンプレート例](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/resources/views/hello.blade.php)

## View Composer

- blade テンプレートから、処理をテンプレート外出しできる
- View Composer と テンプレートを サービスプロバイダで紐付ける
- [リスト6.5.3.3：認可を伴うプレゼンテーションロジック実装例](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Foundation/ViewComposer/PolicyComposer.php)
- [リスト6.5.3.4：View Composerの登録例](https://github.com/ShoheiImamura/laravel-chapter6/blob/master/chapter06/app/Providers/AppServiceProvider.php)