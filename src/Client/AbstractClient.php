<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use GuzzleHttp\Client;
use StrackIntegrations\Config\ApiConfig;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly abstract class AbstractClient
{
    const CACHE_TOKEN_KEY = 'strack_integrations.access_token';
    const TEMPLATE_PLACEHOLDER = '%%%template_json%%%';

    private Client $client;

    public function __construct(
        protected ApiConfig $apiConfig,
        private CacheInterface $cache
    ) {
        $this->client = new Client();
    }

    public function post(string $endpoint, string $soapAction, array $jsonParams): array
    {
        $response = $this->client->post($this->apiConfig->getApiDomain() . $endpoint, [
           'headers' => [
               'Authorization' => 'Bearer ' . $this->getAccessToken(),
               'SOAPAction' => $soapAction,
               'Context-Type' => 'text/xml'
           ],
            'body' => $this->getRequestBody($jsonParams)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getAccessToken(): string
    {
        return $this->cache->get(self::CACHE_TOKEN_KEY, function (ItemInterface $item) {
            $response = $this->client->post($this->apiConfig->getAccessTokenUri(), [
                'grant_type' => 'client_credentials',
                'scope' => $this->apiConfig->getScope(),
                'client_secret' => $this->apiConfig->getClientSecret(),
                'client_id' => $this->apiConfig->getClientId()
            ]);

            $arrayResponse = json_decode($response->getBody()->getContents(), true);

            $item->expiresAfter($arrayResponse['expires_in'] -1);

            return $arrayResponse['access_token'];
        });
    }

    private function getRequestBody(array $params): string
    {
        return str_replace(self::TEMPLATE_PLACEHOLDER, json_encode($params), $this->getEnvelopeTemplate());
    }

    private function getEnvelopeTemplate(): string
    {
        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:lic="urn:microsoft-dynamics-schemas/codeunit/SWWebServices">
    <soapenv:Header></soapenv:Header>
    <soapenv:Body>
        <lic:GetSalesPrice>
            <lic:parameter>%%%template_json%%%</lic:parameter>
        </lic:GetSalesPrice>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }
}
