<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MomoService
{
    private string $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
    private string $partnerCode = "MOMOBKUN20180529";
    private string $accessKey = "klm05TvNBzhg7h7j";
    private string $secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function createPayment(float $amount, string $orderId, string $returnUrl, string $notifyUrl): array
    {
        $orderInfo = "Thanh toán qua mã QR MoMo";
        $requestId = time();
        $requestType = "captureWallet";
        $extraData = "";

        // Tạo chữ ký (signature)
        $rawHash = "accessKey={$this->accessKey}&amount={$amount}&extraData={$extraData}"
            . "&ipnUrl={$notifyUrl}&orderId={$orderId}&orderInfo={$orderInfo}"
            . "&partnerCode={$this->partnerCode}&redirectUrl={$returnUrl}"
            . "&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);

        // Dữ liệu gửi đi
        $data = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => "Test",
            'storeId' => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        // Gửi yêu cầu đến API MoMo
        $response = $this->httpClient->request('POST', $this->endpoint, [
            'json' => $data
        ]);
        // dump($response->toArray());
        // die();

        return $response->toArray();
    }
}
