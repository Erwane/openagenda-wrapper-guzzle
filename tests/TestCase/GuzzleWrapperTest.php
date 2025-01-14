<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 */
declare(strict_types=1);

namespace OpenAgenda\Wrapper\Test\TestCase;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use OpenAgenda\Wrapper\GuzzleWrapper;
use OpenAgenda\Wrapper\HttpWrapperException;
use OpenAgenda\Wrapper\HttpWrapperInterface;
use PHPUnit\Framework\TestCase;

/**
 * @uses   \OpenAgenda\Wrapper\GuzzleWrapper
 * @covers \OpenAgenda\Wrapper\GuzzleWrapper
 */
class GuzzleWrapperTest extends TestCase
{
    /**
     * @var (\GuzzleHttp\Client&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $psr18Client;

    /**
     * @var \GuzzleHttp\Psr7\Request
     */
    protected $request;

    /**
     * @var \League\Uri\Uri
     */
    protected $uri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->psr18Client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['request'])
            ->getMock();

        $this->request = new Request('GET', 'https://example.com');
        $this->uri = new Uri('https://example.com');
    }

    public static function dataPrepareOptions(): array
    {
        $resource = fopen(__FILE__, 'r');

        return [
            [
                ['headers' => ['x-foo' => 'bar']],
                [],
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                        'x-foo' => 'bar',
                    ],
                    'allow_redirects' => false,
                ],
            ],
            [
                [],
                ['key' => 'value', 'other' => 23],
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                    ],
                    'allow_redirects' => false,
                    'json' => ['key' => 'value', 'other' => 23],
                ],
            ],
            [
                [],
                ['key' => 'value', 'image' => $resource],
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                    ],
                    'allow_redirects' => false,
                    'multipart' => [
                        [
                            'name' => 'key',
                            'contents' => 'value',
                        ],
                        [
                            'name' => 'image',
                            'contents' => $resource,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataPrepareOptions
     */
    public function testPrepareOptions($options, $data, $expected)
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $results = $wrapper->prepareOptions($options, $data);

        $this->assertEquals($expected, $results);
    }

    public function testBuildUriFromUrl(): void
    {
        $wrapper = new GuzzleWrapper();
        $uri = $wrapper->buildUri('https://example.com');

        $this->assertInstanceOf(Uri::class, $uri);
    }

    public function testSendRequest()
    {
        $http = $this->getMockBuilder(Client::class)
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $http->expects($this->once())
            ->method('sendRequest')
            ->with($this->request);

        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($http);
        $wrapper->sendRequest($this->request);
    }

    public function testConnectException(): void
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $request = new Request('GET', (string)$this->uri);

        $this->psr18Client->expects($this->once())
            ->method('request')
            ->willThrowException(new ConnectException('error', $request));

        try {
            $wrapper->get($this->uri);
        } catch (HttpWrapperException $e) {
            $this->assertEquals('Wrapper GET request failed. error', $e->getMessage());
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
            $this->assertSame($request, $e->getRequest());
            $this->assertNull($e->getResponse());
        }
    }

    public function testRequestException(): void
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $request = new Request('GET', (string)$this->uri);
        $response = new Response(200, [], '');

        $this->psr18Client->expects($this->once())
            ->method('request')
            ->willThrowException(new RequestException('error', $request, $response));

        try {
            $wrapper->get($this->uri);
        } catch (HttpWrapperException $e) {
            $this->assertEquals('Wrapper GET request failed. error', $e->getMessage());
            $this->assertInstanceOf(RequestException::class, $e->getPrevious());
            $this->assertSame($request, $e->getRequest());
            $this->assertSame($response, $e->getResponse());
        }
    }

    public static function dataExceptions(): array
    {
        return [
            ['head'],
            ['get'],
            ['post'],
            ['patch'],
            ['delete'],
        ];
    }

    /**
     * @dataProvider dataExceptions
     */
    public function testCallExceptions($method): void
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $this->psr18Client->expects($this->once())
            ->method('request')
            ->willThrowException(new TransferException('error'));

        try {
            $wrapper->{$method}($this->uri, []);
        } catch (HttpWrapperException $e) {
            $this->assertEquals(sprintf('Wrapper %s request failed. error', strtoupper($method)), $e->getMessage());
            $this->assertInstanceOf(TransferException::class, $e->getPrevious());
            $this->assertNull($e->getRequest());
            $this->assertNull($e->getResponse());
        }
    }

    public function testExceptionContainGuzzleException(): void
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $request = new Request('GET', (string)$this->uri);
        $this->psr18Client->expects($this->once())
            ->method('request')
            ->willThrowException(new RequestException('error', $request));

        try {
            $wrapper->get($this->uri);
        } catch (HttpWrapperException $exception) {
            $this->assertInstanceOf(GuzzleException::class, $exception->getPrevious());
        }
    }

    public function testMethodHead()
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $this->psr18Client->expects($this->once())
            ->method('request')
            ->with(
                'HEAD',
                'https://example.com',
                [
                    'allow_redirects' => false,
                    'headers' => [
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                        'Accept' => 'application/json',
                    ],
                ]
            );

        $wrapper->head($this->uri);
    }

    public function testMethodGet()
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $this->psr18Client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://example.com',
                [
                    'allow_redirects' => false,
                    'headers' => [
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                        'Accept' => 'application/json',
                    ],
                ]
            );

        $wrapper->get($this->uri);
    }

    public function testMethodPost()
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);

        $this->psr18Client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://example.com',
                [
                    'allow_redirects' => false,
                    'headers' => [
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                        'Accept' => 'application/json',
                        'x-foo' => 'bar',
                    ],
                    'json' => ['foo' => 'bar'],
                ]
            );

        $wrapper->post($this->uri, ['foo' => 'bar'], ['headers' => ['x-foo' => 'bar']]);
    }

    public function testMethodPatch()
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);
        $this->psr18Client->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'https://example.com',
                [
                    'allow_redirects' => false,
                    'headers' => [
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                        'Accept' => 'application/json',
                        'x-foo' => 'bar',
                    ],
                    'json' => ['foo' => 'bar'],
                ]
            );

        $wrapper->patch($this->uri, ['foo' => 'bar'], ['headers' => ['x-foo' => 'bar']]);
    }

    public function testMethodDelete()
    {
        $wrapper = new GuzzleWrapper();
        $wrapper->setClient($this->psr18Client);
        $this->psr18Client->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'https://example.com',
                [
                    'allow_redirects' => false,
                    'headers' => [
                        'User-Agent' => HttpWrapperInterface::USER_AGENT,
                        'Accept' => 'application/json',
                        'x-foo' => 'bar',
                    ],
                ]
            );

        $wrapper->delete($this->uri, ['headers' => ['x-foo' => 'bar']]);
    }
}
