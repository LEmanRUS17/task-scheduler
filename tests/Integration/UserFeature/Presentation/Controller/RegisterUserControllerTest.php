<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserFeature\Presentation\Controller;

use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RegisterUserControllerTest extends WebTestCase
{
    public function testRegisterReturns201OnSuccess(): void
    {
        $client = static::createClient();

        $mock = $this->createMock(UserServiceInterface::class);
        $mock->expects($this->once())->method('register');
        static::getContainer()->set(UserServiceInterface::class, $mock);

        $client->request('POST', '/auth/register', content: json_encode([
            'email' => 'user@example.com',
            'plainPassword' => 'Password1',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('success', $body['variant']);
    }

    public function testRegisterReturns422WhenEmailIsInvalid(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/register', content: json_encode([
            'email' => 'not-an-email',
            'plainPassword' => 'Password1',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterReturns422WhenPasswordIsTooShort(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/register', content: json_encode([
            'email' => 'user@example.com',
            'plainPassword' => '123',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterReturns409WhenEmailAlreadyExists(): void
    {
        $client = static::createClient();

        $mock = $this->createMock(UserServiceInterface::class);
        $mock->method('register')->willThrowException(
            new \DomainException('User user@example.com already exists'),
        );
        static::getContainer()->set(UserServiceInterface::class, $mock);

        $client->request('POST', '/auth/register', content: json_encode([
            'email' => 'user@example.com',
            'plainPassword' => 'Password1',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('danger', $body['variant']);
    }
}
