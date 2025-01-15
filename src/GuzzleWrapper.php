<?php
declare(strict_types=1);

namespace OpenAgenda\Wrapper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class GuzzleWrapper extends HttpWrapper
{
    /**
     * @var \GuzzleHttp\Client|\Psr\Http\Client\ClientInterface
     */
    protected $http;

    /**
     * {@inheritDoc}
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(array $params = [])
    {
        $this->http = new Client($params);
    }

    /**
     * Set client in wrapper. Useful for unit tests.
     *
     * @param \GuzzleHttp\Client $client Guzzle client
     * @return void
     */
    public function setClient(Client $client): void
    {
        $this->http = $client;
    }

    /**
     * Prepare request options.
     *
     * @param array $options Request options.
     * @param array $data Request data.
     * @return array
     */
    public function prepareOptions(array $options, array $data = []): array
    {
        $options['allow_redirects'] = false;
        $options['headers']['Accept'] = 'application/json';
        $options['headers']['User-Agent'] = HttpWrapperInterface::USER_AGENT;

        if ($data) {
            // Has resource (file)
            $resources = array_filter($data, function ($value) {
                return is_resource($value);
            });

            if ($resources) {
                $options['multipart'] = [];
                foreach ($data as $key => $value) {
                    $options['multipart'][] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }
            } else {
                $options['json'] = $data;
            }
        }

        return $options;
    }

    /**
     * Build uri.
     *
     * @param \Psr\Http\Message\UriInterface|string $url Url as string or UriInterface
     * @return \Psr\Http\Message\UriInterface|\GuzzleHttp\Psr7\Uri
     */
    public function buildUri($url): UriInterface
    {
        $uri = $url;
        if (is_string($url)) {
            $uri = new Uri($url);
        }

        return $uri;
    }

    /**
     * Call guzzle request and handle exceptions.
     *
     * @param string $method Request method
     * @param \GuzzleHttp\Psr7\Uri $uri Request URI
     * @param array $params Request params
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \OpenAgenda\Wrapper\HttpWrapperException
     */
    protected function _request(string $method, Uri $uri, array $params): ResponseInterface
    {
        $method = strtoupper($method);
        try {
            return $this->http->request($method, (string)$uri, $params);
        } catch (GuzzleException $e) {
            $message = sprintf('Wrapper %s request failed. %s', $method, $e->getMessage());
            $new = new HttpWrapperException($message, $e->getCode(), $e);
            $request = null;
            $response = null;
            if ($e instanceof ConnectException) {
                $request = $e->getRequest();
            } elseif ($e instanceof RequestException) {
                $request = $e->getRequest();
                $response = $e->getResponse();
            }

            if ($request) {
                $new->setRequest($request);
            }
            if ($response) {
                $new->setResponse($response);
            }

            throw $new;
        }
    }

    /**
     * @inheritDoc
     */
    public function head($uri, array $params = []): ResponseInterface
    {
        $uri = $this->buildUri($uri);
        $params = $this->prepareOptions($params);

        return $this->_request('HEAD', $uri, $params);
    }

    /**
     * @inheritDoc
     */
    public function get($uri, array $params = []): ResponseInterface
    {
        $uri = $this->buildUri($uri);
        $params = $this->prepareOptions($params);

        return $this->_request('GET', $uri, $params);
    }

    /**
     * @inheritDoc
     */
    public function post($uri, array $data, array $params = []): ResponseInterface
    {
        $uri = $this->buildUri($uri);
        $params = $this->prepareOptions($params, $data);

        return $this->_request('POST', $uri, $params);
    }

    /**
     * @inheritDoc
     */
    public function patch($uri, array $data, array $params = []): ResponseInterface
    {
        $uri = $this->buildUri($uri);
        $params = $this->prepareOptions($params, $data);

        return $this->_request('PATCH', $uri, $params);
    }

    /**
     * @inheritDoc
     */
    public function delete($uri, array $params = []): ResponseInterface
    {
        $uri = $this->buildUri($uri);
        $params = $this->prepareOptions($params);

        return $this->_request('DELETE', $uri, $params);
    }
}
