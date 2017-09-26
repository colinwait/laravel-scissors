<?php


namespace Colinwait\LaravelPockets;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Exception\BadResponseException;

class Client
{
    private $client;

    private $multipart_params;

    private $method;

    private $url;

    private $form_params;

    private $headers;

    private $query;

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

    public function setFormParams($name, $value)
    {
        $this->form_params[$name] = $value;
    }

    public function setHeaders($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function setQuery($name, $value)
    {
        $this->query[$name] = $value;
    }

    public function request()
    {
        $method = strtolower($this->method);
        try {
            $response = $this->client->$method($this->url, [
                'multipart'   => $this->multipart_params,
                'form_params' => $this->form_params,
                'query'       => $this->query,
                'headers'     => $this->headers,
            ]);
            $result   = json_decode($response->getBody(), 1);
        } catch (BadResponseException $e) {
            logger($e->getMessage());
            $response = $e->getResponse();
            $result   = json_decode($response->getBody()->getContents(), 1);
        }

        if (is_null($result)) {
            return ['error' => 'Request failed'];
        }

        if (isset($result['error']) && $result['error']) {
            return ['error' => $result['error']];
        }

        return $result;
    }
}