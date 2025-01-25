<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests\Tests;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pugkong\Symfony\Requests\FormEncoder;
use Pugkong\Symfony\Requests\Request;
use Pugkong\Symfony\Requests\StatusCodeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\UriTemplateHttpClient;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\ChunkInterface;

use const ARRAY_FILTER_USE_KEY;

final class RequestTest extends TestCase
{
    #[DataProvider('requestProvider')]
    public function testRequest(mixed $expected, callable $fetch): void
    {
        if ($expected instanceof ExpectedException) {
            $this->expectException($expected->class);
            $this->expectExceptionMessage($expected->message);
        }

        $http = new UriTemplateHttpClient(HttpClient::create());

        $serializer = new Serializer(
            normalizers: [new ArrayDenormalizer(), new ObjectNormalizer()],
            encoders: [new JsonEncoder(), new XmlEncoder(), new FormEncoder()],
        );

        $request = Request::create($http, $serializer)
            ->base('http://localhost:8000')
            ->header('User-Agent', 'symfony-requests')
        ;

        self::assertEquals($expected, $fetch($request));
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestProvider(): array
    {
        return [
            'get json request' => [
                EchoServerResponse::json(method: 'GET', path: '/get'),
                fn (Request $request) => $request
                    ->get('/get')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'get xml request' => [
                EchoServerResponse::xml(method: 'GET', path: '/get'),
                fn (Request $request) => $request
                    ->get('/get')
                    ->header('Accept', 'application/xml')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class, 'xml'),
            ],

            'post json request' => [
                EchoServerResponse::json(
                    method: 'POST',
                    path: '/post',
                    headers: ['Content-Type' => 'application/json'],
                    body: ['name' => 'John Doe'],
                ),
                fn (Request $request) => $request
                    ->post('/post')
                    ->headers([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->body(new NameRequest(name: 'John Doe'))
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'post xml request' => [
                EchoServerResponse::xml(
                    method: 'POST',
                    path: '/post',
                    headers: ['Content-Type' => 'application/xml'],
                    body: ['request' => ['name' => 'Jane Doe']],
                ),
                fn (Request $request) => $request
                    ->post('/post')
                    ->headers([
                        'Content-Type' => 'application/xml',
                        'Accept' => 'application/xml',
                    ])
                    ->body(new NameRequest(name: 'Jane Doe'), 'xml', [XmlEncoder::ROOT_NODE_NAME => 'request'])
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class, 'xml'),
            ],

            'post form request' => [
                EchoServerResponse::json(
                    method: 'POST',
                    path: '/post',
                    headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
                    body: ['name' => 'Jane Doe'],
                ),
                fn (Request $request) => $request
                    ->post('/post')
                    ->headers([
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                    ])
                    ->body(new NameRequest(name: 'Jane Doe'), 'form')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'put json request' => [
                EchoServerResponse::json(
                    method: 'PUT',
                    path: '/put',
                    headers: ['Content-Type' => 'application/json'],
                    body: ['name' => 'John Doe'],
                ),
                fn (Request $request) => $request
                    ->put('/put')
                    ->headers([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->body(new NameRequest(name: 'John Doe'))
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'patch json request' => [
                EchoServerResponse::json(
                    method: 'PATCH',
                    path: '/patch',
                    headers: ['Content-Type' => 'application/json'],
                    body: ['name' => 'John Doe'],
                ),
                fn (Request $request) => $request
                    ->patch('/patch')
                    ->headers([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->body(new NameRequest(name: 'John Doe'))
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'delete json request' => [
                EchoServerResponse::json(
                    method: 'DELETE',
                    path: '/delete',
                    headers: ['Content-Type' => 'application/json'],
                    body: ['name' => 'John Doe'],
                ),
                fn (Request $request) => $request
                    ->delete('/delete')
                    ->headers([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->body(new NameRequest(name: 'John Doe'))
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'options request' => [
                EchoServerResponse::json(method: 'OPTIONS', path: '/options'),
                fn (Request $request) => $request
                    ->options('/options')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'query parameters' => [
                EchoServerResponse::json(method: 'GET', path: '/query', query: ['name' => 'John Doe']),
                fn (Request $request) => $request
                    ->get('/query')
                    ->header('Accept', 'application/json')
                    ->query(['name' => 'John Doe'])
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'auth basic' => [
                EchoServerResponse::json(
                    method: 'GET',
                    path: '/auth/basic',
                    headers: ['Authorization' => 'Basic '.base64_encode('user:password')],
                ),
                fn (Request $request) => $request
                    ->basic('user', 'password')
                    ->get('/auth/basic')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'auth bearer' => [
                EchoServerResponse::json(
                    method: 'GET',
                    path: '/auth/bearer',
                    headers: ['Authorization' => 'Bearer token'],
                ),
                fn (Request $request) => $request
                    ->bearer('token')
                    ->get('/auth/bearer')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'uri templates' => [
                EchoServerResponse::json(method: 'GET', path: '/users/42/comments/42.42'),
                fn (Request $request) => $request
                    ->get('/users/{userId}/{resource}/{resourceId}')
                    ->var('userId', 42)
                    ->vars(['resource' => 'comments', 'resourceId' => 42.42])
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->object(EchoServerResponse::class),
            ],

            'get status code' => [
                200,
                fn (Request $request) => $request
                    ->get('/status')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->status(),
            ],

            'get response headers' => [
                ['content-type' => ['application/json']],
                fn (Request $request) => array_filter(
                    array: $request
                        ->get('/headers')
                        ->header('Accept', 'application/json')
                        ->response()
                        ->checkStatus(200)
                        ->headers(),
                    callback: fn ($key) => 'date' !== $key && 'content-length' !== $key,
                    mode: ARRAY_FILTER_USE_KEY,
                ),
            ],

            'get response header' => [
                ['application/json'],
                fn (Request $request) => $request
                    ->get('/headers')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->header('Content-Type'),
            ],

            'get response as objects' => [
                [EchoServerResponse::json('GET', '/objects')],
                fn (Request $request) => $request
                    ->get('/objects')
                    ->headers([
                        'Accept' => 'application/json',
                        'X-Response-Shape' => 'array',
                    ])
                    ->response()
                    ->checkStatus(200)
                    ->objects(EchoServerResponse::class),
            ],

            'get response content' => [
                '{"method":"GET","path":"/content","headers":{"Accept":"application/json","Accept-Encoding":"gzip","User-Agent":"symfony-requests"}}',
                fn (Request $request) => trim(
                    $request
                        ->get('/content')
                        ->header('Accept', 'application/json')
                        ->response()
                        ->checkStatus(200)
                        ->content(),
                ),
            ],

            'stream response content' => [
                '{"method":"GET","path":"/stream","headers":{"Accept":"application/json","Accept-Encoding":"gzip","User-Agent":"symfony-requests"}}',
                fn (Request $request) => trim(array_reduce(
                    array: iterator_to_array(
                        iterator: $request
                            ->get('/stream')
                            ->header('Accept', 'application/json')
                            ->response()
                            ->checkStatus(200)
                            ->stream(),
                        preserve_keys: false,
                    ),
                    callback: fn (string $carry, ChunkInterface $chunk) => $carry.$chunk->getContent(),
                    initial: '',
                )),
            ],

            'get response as array' => [
                [
                    'method' => 'GET',
                    'path' => '/array',
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Encoding' => 'gzip',
                        'User-Agent' => 'symfony-requests',
                    ],
                ],
                fn (Request $request) => $request
                    ->get('/array')
                    ->header('Accept', 'application/json')
                    ->response()
                    ->checkStatus(200)
                    ->array(),
            ],

            'check response status code' => [
                new ExpectedException(
                    class: StatusCodeException::class,
                    message: '418 returned for GET http://localhost:8000/exception, expected 200, 201',
                ),
                fn (Request $request) => $request
                    ->get('/exception')
                    ->headers([
                        'Accept' => 'application/json',
                        'X-Status-Code' => '418',
                    ])
                    ->response()
                    ->checkStatus(200, 201),
            ],

            'missing http method call' => [
                new ExpectedException(
                    class: LogicException::class,
                    message: 'The HTTP method and path were not set',
                ),
                fn (Request $request) => $request->response(),
            ],
        ];
    }
}
