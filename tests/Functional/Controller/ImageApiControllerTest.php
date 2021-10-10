<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ImageApiControllerTest extends WebTestCase
{

    public function testLoginUnauthorized()
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginFailed()
    {
        $client = static::createClient();
        $client->request('GET', '/login', [
            'username' => 'user',
            'password' => 'wrongPassword',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogin()
    {
        $client = static::createClient();
        $client->request('GET', '/login', [
            'username' => 'user',
            'password' => 'password',
        ]);
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), TRUE);
        $this->assertArrayHasKey('token', $response);
        $this->assertNotEmpty($response['token']);

        return $response['token'];
    }

    public function testPostImageUnauthorized()
    {
        $client = static::createClient();
        $client->request('POST', '/image', [
            'image' => 'someimage',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }


    public function testPostImageFailed()
    {
        $client = static::createClient();
        $client->request('POST', '/image', [
            'image' => 'someimage',
        ], [], ['HTTP_X-AUTH-TOKEN' => 'wrongtoken']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @depends testLogin
     */
    public function testPostImage(string $token)
    {
        $client = static::createClient();
        $client->request('POST', '/image', [
            'image' => base64_encode(file_get_contents(dirname(__DIR__) . '/../cat.jpg')),
        ], [], ['HTTP_X-AUTH-TOKEN' => $token]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), TRUE);

        $this->assertArrayHasKey('url', $response);
        $this->assertNotEmpty($response['url']);

        return $response['url'];
    }

    /**
     * @depends testLogin
     */
    public function testPostImageWrongType(string $token)
    {
        $client = static::createClient();
        $client->request('POST', '/image', [
            'image' => base64_encode(file_get_contents(dirname(__DIR__) . '/../cat.png')),
        ], [], ['HTTP_X-AUTH-TOKEN' => $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @depends testLogin
     */
    public function testPostImageTooSmall(string $token)
    {
        $client = static::createClient();
        $client->request('POST', '/image', [
            'image' => base64_encode(file_get_contents(dirname(__DIR__) . '/../cat_small.jpg')),
        ], [], ['HTTP_X-AUTH-TOKEN' => $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @depends testPostImage
     */
    public function testGetImageUnauthorized(string $url)
    {
        $client = static::createClient();
        $path = parse_url($url)['path'];
        $client->request('GET', $path);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @depends testPostImage
     */
    public function testGetImageFailed(string $url)
    {
        $client = static::createClient();
        $path = parse_url($url)['path'];
        $client->request('GET', $path, [], [], ['HTTP_X-AUTH-TOKEN' => 'wrongtoken']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @depends testLogin
     */
    public function testGetImageNotFound(string $token)
    {
        $client = static::createClient();

        $client->request('GET', '/image/wrongimageid', [], [], ['HTTP_X-AUTH-TOKEN' => $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @depends testLogin
     * @depends testPostImage
     */
    public function testGetImage(string $token, string $url)
    {
        $client = static::createClient();
        $path = parse_url($url)['path'];
        $client->request('GET', $path, [], [], ['HTTP_X-AUTH-TOKEN' => $token]);

        $this->assertResponseIsSuccessful();

        $expected = file_get_contents(dirname(__DIR__) . '/../cat.jpg');
        $image = file_get_contents($client->getResponse()->getFile());
        $this->assertEquals($expected, $image);
    }

}
