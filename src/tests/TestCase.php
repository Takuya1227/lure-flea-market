<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テスト環境ではブラウザを介さないためCSRFトークンが存在しない。
        // Laravel 11では ValidateCsrfToken（VerifyCsrfTokenのエイリアス）が使用されており、
        // docker-compose.ymlのAPP_ENV=localがphpunit.xmlのtesting設定を上書きするため
        // 明示的に無効化する。
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
