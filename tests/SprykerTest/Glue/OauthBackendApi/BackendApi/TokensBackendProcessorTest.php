<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Glue\OauthBackendApi\BackendApi;

use ApiPlatform\Metadata\Post;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Api\Backend\TokensBackendResource;
use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\GlueAuthenticationResponseTransfer;
use Generated\Shared\Transfer\OauthErrorTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Glue\OauthBackendApi\Api\Backend\Processor\TokensBackendProcessor;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group TokensBackendProcessorTest
 * Add your own group annotations below this line
 */
class TokensBackendProcessorTest extends Unit
{
    protected const string USERNAME = 'admin@spryker.com';

    protected const string PASSWORD = 'change123';

    protected const string ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9';

    protected const string REFRESH_TOKEN = 'def50200a1b2c3d4e5f6789012345678901234567890abcdef';

    protected const string TOKEN_TYPE = 'Bearer';

    protected const int EXPIRES_IN = 3600;

    protected const string GRANT_TYPE_PASSWORD = 'password';

    protected const string GLUE_BACKEND_API_APPLICATION = 'GLUE_BACKEND_API_APPLICATION';

    protected const string OAUTH_ERROR_TYPE = 'invalid_credentials';

    protected const string OAUTH_ERROR_MESSAGE = 'Invalid credentials provided.';

    public function testGivenValidCredentialsWhenProcessPostThenReturnsResourceWithIssuedToken(): void
    {
        // Arrange
        $processor = new TokensBackendProcessor(
            $this->createAuthenticationFacadeStub($this->createValidOauthResponseTransfer()),
        );

        // Act
        $result = $processor->process($this->createResource(), $this->createPostOperation());

        // Assert
        $this->assertInstanceOf(TokensBackendResource::class, $result);
        $this->assertSame(static::ACCESS_TOKEN, $result->accessToken);
        $this->assertSame(static::TOKEN_TYPE, $result->tokenType);
        $this->assertSame(static::EXPIRES_IN, $result->expiresIn);
        $this->assertSame(static::REFRESH_TOKEN, $result->refreshToken);
    }

    public function testGivenValidCredentialsWhenProcessPostThenAuthenticatesWithBackendApiApplicationContext(): void
    {
        // Arrange
        $authenticationRequestTransfers = [];
        $authenticationFacade = $this->createAuthenticationFacadeStub(
            $this->createValidOauthResponseTransfer(),
            $authenticationRequestTransfers,
        );

        // Act
        (new TokensBackendProcessor($authenticationFacade))
            ->process($this->createResource(), $this->createPostOperation());

        // Assert
        $this->assertCount(1, $authenticationRequestTransfers);
        $glueAuthenticationRequestTransfer = $authenticationRequestTransfers[0];
        $this->assertSame(
            static::GLUE_BACKEND_API_APPLICATION,
            $glueAuthenticationRequestTransfer->getRequestContextOrFail()->getRequestApplication(),
        );

        $oauthRequestTransfer = $glueAuthenticationRequestTransfer->getOauthRequestOrFail();
        $this->assertSame(static::GRANT_TYPE_PASSWORD, $oauthRequestTransfer->getGrantType());
        $this->assertSame(static::USERNAME, $oauthRequestTransfer->getUsername());
        $this->assertSame(static::PASSWORD, $oauthRequestTransfer->getPassword());
    }

    public function testGivenInvalidCredentialsWhenProcessPostThenThrowsGlueApiExceptionWithUnauthorizedStatus(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setIsValid(false)
            ->setError(
                (new OauthErrorTransfer())
                    ->setErrorType(static::OAUTH_ERROR_TYPE)
                    ->setMessage(static::OAUTH_ERROR_MESSAGE),
            );

        $processor = new TokensBackendProcessor($this->createAuthenticationFacadeStub($oauthResponseTransfer));

        // Act + Assert
        try {
            $processor->process($this->createResource(), $this->createPostOperation());
            $this->fail('Expected GlueApiException to be thrown.');
        } catch (GlueApiException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());
            $this->assertSame(static::OAUTH_ERROR_TYPE, $exception->getErrorCode());
            $this->assertSame(static::OAUTH_ERROR_MESSAGE, $exception->getMessage());
        }
    }

    public function testGivenInvalidCredentialsWithoutErrorDetailsWhenProcessPostThenThrowsGlueApiExceptionWithDefaults(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())->setIsValid(false);
        $processor = new TokensBackendProcessor($this->createAuthenticationFacadeStub($oauthResponseTransfer));

        // Act + Assert
        try {
            $processor->process($this->createResource(), $this->createPostOperation());
            $this->fail('Expected GlueApiException to be thrown.');
        } catch (GlueApiException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());
            $this->assertSame('001', $exception->getErrorCode());
            $this->assertSame('Failed to authenticate user.', $exception->getMessage());
        }
    }

    /**
     * @param array<\Generated\Shared\Transfer\GlueAuthenticationRequestTransfer> $authenticationRequestTransfers
     */
    protected function createAuthenticationFacadeStub(
        OauthResponseTransfer $oauthResponseTransfer,
        array &$authenticationRequestTransfers = [],
    ): AuthenticationFacadeInterface {
        return Stub::makeEmpty(AuthenticationFacadeInterface::class, [
            'authenticate' => function (
                GlueAuthenticationRequestTransfer $glueAuthenticationRequestTransfer
            ) use (
                $oauthResponseTransfer,
                &$authenticationRequestTransfers,
            ): GlueAuthenticationResponseTransfer {
                $authenticationRequestTransfers[] = $glueAuthenticationRequestTransfer;

                return (new GlueAuthenticationResponseTransfer())->setOauthResponse($oauthResponseTransfer);
            },
        ]);
    }

    protected function createValidOauthResponseTransfer(): OauthResponseTransfer
    {
        return (new OauthResponseTransfer())
            ->setIsValid(true)
            ->setAccessToken(static::ACCESS_TOKEN)
            ->setTokenType(static::TOKEN_TYPE)
            ->setExpiresIn(static::EXPIRES_IN)
            ->setRefreshToken(static::REFRESH_TOKEN);
    }

    protected function createResource(): TokensBackendResource
    {
        $resource = new TokensBackendResource();
        $resource->username = static::USERNAME;
        $resource->password = static::PASSWORD;

        return $resource;
    }

    protected function createPostOperation(): Post
    {
        return new Post(class: TokensBackendResource::class);
    }
}
