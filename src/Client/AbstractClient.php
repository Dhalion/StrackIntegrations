<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use GuzzleHttp\Client;
use SimpleXMLElement;
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

    protected function post(string $endpoint, string $soapAction, string $envelope, array $jsonParams): SimpleXMLElement
    {
        $response = $this->client->post($this->apiConfig->getApiDomain() . $endpoint, [
           'headers' => [
               'Authorization' => 'Bearer ' . $this->getAccessToken(),
               'SOAPAction' => $soapAction,
               'Content-Type' => 'text/xml',
               'Accept' => 'text/xml'
           ],
            'body' => $this->getRequestBody($envelope, $jsonParams)
        ]);

        return new SimpleXMLElement($response->getBody()->getContents());
    }

    private function getAccessToken(): string
    {
        return $this->cache->get(self::CACHE_TOKEN_KEY, function (ItemInterface $item) {
            $response = $this->client->post($this->apiConfig->getAccessTokenUri(), [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'scope' => $this->apiConfig->getScope(),
                    'client_secret' => $this->apiConfig->getClientSecret(),
                    'client_id' => $this->apiConfig->getClientId()
                ]
            ]);

            $arrayResponse = json_decode($response->getBody()->getContents(), true);

            $item->expiresAfter($arrayResponse['expires_in'] -1);

            return $arrayResponse['access_token'];
        });
    }

    private function getRequestBody(string $envelope, array $params): string
    {
        return str_replace(self::TEMPLATE_PLACEHOLDER, json_encode($params), $envelope);
    }
}
