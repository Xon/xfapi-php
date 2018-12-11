<?php

namespace XFApi;

use GuzzleHttp\Client as GuzzleClient;
use XFApi\Container\AbstractContainer;
use XFApi\Container\XFContainer;
use XFApi\Exception\XFApiException;

/**
 * Class Client
 * @package XFApi
 *
 * @property XFContainer $xf
 */
class Client
{
    const LIBRARY_VERSION = '1.0.0 Alpha 1';

    protected $apiUrl;
    protected $apiKey;
    protected $apiUserId;
    protected $httpClient;

    protected $_xf;

    /**
     * Client constructor.
     *
     * @param $apiUrl
     * @param $apiKey
     * @param string|null $apiUserId
     */
    public function __construct($apiUrl, $apiKey, $apiUserId = null)
    {
        $this->setApiUrl($apiUrl);
        $this->setApiKey($apiKey);
        $this->setApiUserId($apiUserId);

        $this->setHttpClient(new GuzzleClient);
    }

    /**
     * @return GuzzleClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param GuzzleClient $httpClient
     */
    public function setHttpClient(GuzzleClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiUserId
     */
    public function setApiUserId($apiUserId)
    {
        $this->apiUserId = $apiUserId;
    }

    /**
     * @return string
     */
    public function getApiUserId()
    {
        return $this->apiUserId;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getFullUrl($endpoint, array $params = [])
    {
        if (substr($endpoint, 0, 1) !== '/') {
            $endpoint = '/' . $endpoint;
        }

        return $this->getApiUrl() . $endpoint;
    }

    public function requestGet($endpoint, array $params = [], array $headers = [])
    {
        return $this->request('GET', $endpoint, $params, [], $headers);
    }

    public function requestPost($endpoint, array $data = [], array $headers = [])
    {
        return $this->request('POST', $endpoint, [], $data, $headers);
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $params
     * @param array $data
     * @param array $headers
     * @return array
     *
     * @throws XFApiException
     */
    public function request($method, $endpoint, array $params = [], array $data = [], array $headers = [])
    {
        $headers = array_merge($headers, [
            'XF-Api-Key' => $this->getApiKey(),
            'User-Agent' => 'xfapi-php/' . self::LIBRARY_VERSION .
                ' (PHP ' . phpversion() . ')',
            'Accept-Charset' => 'utf-8',
        ]);

        if ($userId = $this->getApiUserId()) {
            $headers['XF-Api-User'] = $userId;
        }

        if (!isset($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }

        $requestOptions = [
            'http_errors' => false,
            'headers' => $headers,
        ];

        if (strtolower($method) === 'post') {
            $requestOptions['form_params'] = $data;
        }

        try {
            $request = $this->getHttpClient()->request($method, $this->getFullUrl($endpoint, $params), $requestOptions);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // this won't trigger, exceptions are disabled.
            // only have this here so phpStorm stops fussing
            // but just in case...
            throw new XFApiException($e->getMessage());
        }

        switch ($request->getStatusCode()) {
            case 200:
                return json_decode($request->getBody()->getContents(), true);
            default:
                // todo: implement exceptions for different possible error codes.
                throw new XFApiException('HTTP Error code: ' . $request->getStatusCode());
        }
    }

    public function getXf()
    {
        if (!$this->_xf) {
            $this->_xf = new XFContainer($this);
        }

        return $this->_xf;
    }

    /**
     * @param string $name
     * @return AbstractContainer
     * @throws XFApiException
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new XFApiException('Unable to find container ' . $name);
    }
}