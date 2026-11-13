<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use PAYJPV2\Api\CheckoutSessionsApi;
use PAYJPV2\Api\PaymentRefundsApi;
use PAYJPV2\ApiException;
use PAYJPV2\Configuration;
use PAYJPV2\Model\CheckoutSessionCreateRequest;
use PAYJPV2\Model\CheckoutSessionMode;
use PAYJPV2\Model\CheckoutSessionPaymentMethodOptionsCardRequest;
use PAYJPV2\Model\CheckoutSessionPaymentMethodOptionsRequest;
use PAYJPV2\Model\CheckoutSessionStatus;
use PAYJPV2\Model\Currency;
use PAYJPV2\Model\LineItemRequest;
use PAYJPV2\Model\Locale;
use PAYJPV2\Model\PaymentRefundCreateRequest;
use PAYJPV2\Model\PriceDataRequest;
use PAYJPV2\Model\ProductDataRequest;
use PAYJPV2\Model\RequestThreeDSecure;

class PayjpService
{
    private Configuration $config;
    private Client $client;

    public function __construct()
    {
        $this->config = Configuration::getDefaultConfiguration()
            ->setAccessToken(config('services.payjp.secret_key', ''));
        $this->client = new Client();
    }

    /**
     * Checkout Session を作成し、PAY.JP ホスト型決済ページの URL を返す
     * カード入力・3DS 認証は PAY.JP 側で完結するため、サーバーがカード情報を扱う必要がない
     * @return array{success: bool, session_id: ?string, checkout_url: ?string, error: ?string}
     */
    public function createCheckoutSession(int $amount, string $productName, string $successUrl, string $cancelUrl): array
    {
        try {
            $api = new CheckoutSessionsApi($this->client, $this->config);

            $productData   = new ProductDataRequest(['name' => $productName]);
            $priceData     = new PriceDataRequest(['currency' => Currency::JPY, 'unitAmount' => $amount, 'productData' => $productData]);
            $lineItem      = new LineItemRequest(['priceData' => $priceData, 'quantity' => 1]);
            $cardOptions   = new CheckoutSessionPaymentMethodOptionsCardRequest(['requestThreeDSecure' => RequestThreeDSecure::ANY]);
            $methodOptions = new CheckoutSessionPaymentMethodOptionsRequest(['card' => $cardOptions]);

            $session = $api->createCheckoutSession(new CheckoutSessionCreateRequest([
                'mode'                 => CheckoutSessionMode::PAYMENT,
                'lineItems'            => [$lineItem],
                'successUrl'           => $successUrl,
                'cancelUrl'            => $cancelUrl,
                'paymentMethodOptions' => $methodOptions,
                'locale'               => Locale::JA,
            ]));

            return ['success' => true, 'session_id' => $session->getId(), 'checkout_url' => $session->getUrl(), 'error' => null];
        } catch (ApiException $e) {
            $rawBody = (string) $e->getResponseBody();
            Log::warning('PAY.JP v2 createCheckoutSession error', ['status' => $e->getCode(), 'body' => $rawBody]);
            $body    = json_decode($rawBody, true);
            $message = $body['error']['message'] ?? $body['detail'] ?? '決済処理中にエラーが発生しました。';
            return ['success' => false, 'session_id' => null, 'checkout_url' => null, 'error' => $message];
        } catch (\Throwable $e) {
            Log::error('PAY.JP v2 createCheckoutSession error', ['class' => get_class($e), 'message' => $e->getMessage()]);
            return ['success' => false, 'session_id' => null, 'checkout_url' => null, 'error' => '決済処理中にエラーが発生しました。しばらくしてから再試行してください。'];
        }
    }

    /**
     * Checkout Session のステータスと PaymentFlow ID を取得する（success_url コールバックで使用）
     * @return array{success: bool, status: ?string, flow_id: ?string, error: ?string}
     */
    public function getCheckoutSession(string $sessionId): array
    {
        try {
            $api     = new CheckoutSessionsApi($this->client, $this->config);
            $session = $api->getCheckoutSession($sessionId);
            return [
                'success' => true,
                'status'  => $session->getStatus()?->value,
                'flow_id' => $session->getPaymentFlowId(),
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('PAY.JP v2 getCheckoutSession error', ['session_id' => $sessionId, 'message' => $e->getMessage()]);
            return ['success' => false, 'status' => null, 'flow_id' => null, 'error' => 'チェックアウトセッションの確認に失敗しました。'];
        }
    }

    /**
     * PaymentFlow を返金する（取引キャンセル時）
     * @return array{success: bool, error: ?string}
     */
    public function refundFlow(string $flowId, int $amount): array
    {
        try {
            $api = new PaymentRefundsApi($this->client, $this->config);
            $api->createPaymentRefund(new PaymentRefundCreateRequest([
                'paymentFlowId' => $flowId,
                'amount'        => $amount,
            ]));
            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('PAY.JP v2 refundFlow error', ['flow_id' => $flowId, 'message' => $e->getMessage()]);
            return ['success' => false, 'error' => '返金処理中にエラーが発生しました。'];
        }
    }
}
