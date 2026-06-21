<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserFeature\Presentation\Controller;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class LogoutTest extends WebTestCase
{
    public function testReturnsBadRequestWhenTokenIsMissing(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/logout', server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsOkWhenTokenIsAlreadyInvalid(): void
    {
        $client = static::createClient();

        $manager = $this->createMock(RefreshTokenManagerInterface::class);
        $manager->method('get')->willReturn(null);
        $manager->expects($this->never())->method('delete');
        static::getContainer()->set(RefreshTokenManagerInterface::class, $manager);

        $client->request('POST', '/auth/logout', content: json_encode([
            'refresh_token' => 'unknown-token',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testInvalidatesRefreshTokenOnLogout(): void
    {
        $client = static::createClient();

        $existingToken = $this->createStub(RefreshTokenInterface::class);
        $existingToken->method('getRefreshToken')->willReturn('valid-token');

        $manager = $this->createMock(RefreshTokenManagerInterface::class);
        $manager->method('get')->willReturn($existingToken);
        $manager->expects($this->once())->method('delete')->with($existingToken);
        static::getContainer()->set(RefreshTokenManagerInterface::class, $manager);

        $client->request('POST', '/auth/logout', content: json_encode([
            'refresh_token' => 'valid-token',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
