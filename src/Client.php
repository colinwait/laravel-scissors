<?php


namespace Colinwait\LaravelScissors;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Exception\BadResponseException;

class Client
{
    private $client;

    private $multipart_params;

    private $method;

    private $url;

    public function __construct($method, $url)
    {
        $this->client = new Http();
        $this->method = $method;
        $this->url    = $url;
    }

    public function setMultiPartParams($name, $contents, array $params = [])
    {
        $multipart_params = [
            'name'     => $name,
            'contents' => $contents,
        ];
        foreach ($params as $key => $param) {
            $multipart_params[$key] = $param;
        }
        $this->multipart_params[] = $multipart_params;
    }

    public function request()
    {
        try {
            $response = $this->client->request($this->method, $this->url, ['multipart' => $this->multipart_params]);
            $result   = json_decode($response->getBody(), 1);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $result   = json_decode($response->getBody()->getContents(), 1);
        }

        if (isset($result['error']) && $result['error']) {
            return ['error' => $result['error']];
        }

        return $result;
    }
}