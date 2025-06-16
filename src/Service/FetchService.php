<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FetchService
{

    public function __construct( private ContainerBagInterface $params, private HttpClientInterface $client )//private ContainerBagInterface $params, private HttpClientInterface $client )
    {
    }

    public function fetchTest($body): string//Transaction|Emv3DS|Challenge|string|array
    {

        $response = $this->client->request(
            'POST',
            $this->params->get('app')['url']['trata'],
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Content-length' => strlen($body)
                ],
                'body' => $body
            ]
        );

        if ($response->getStatusCode() == 200)
        {

            return $response->getContent();
            //return $init ? $this->responseInit( $response->getContent()) : $this->responseTransaction($response->getContent() );

        } else {

            return '{"error":'. $response->getContent() .'}';

        }

    }
    public function fetch($body, bool $init = false): string//Transaction|Emv3DS|Challenge|string|array
    {

        $response = $this->client->request(
            'POST',
            $init ? $this->params->get('app')['url']['inicia'] : $this->params->get('app')['url']['trata'],
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Content-length' => strlen($body)
                ],
                'body' => $body
            ]
        );

        if ($response->getStatusCode() == 200)
        {

            return $response->getContent();
            //return $init ? $this->responseInit( $response->getContent()) : $this->responseTransaction($response->getContent() );

        } else {

            return '{"error":'. $response->getContent() .'}';

        }

    }
}