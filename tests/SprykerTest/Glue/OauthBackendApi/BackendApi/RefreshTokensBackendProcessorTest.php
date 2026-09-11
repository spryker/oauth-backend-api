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
use Generated\Api\Backend\RefreshTokensBackendResource;
use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\GlueAuthenticationResponseTransfer;
use Generated\Shared\Transfer\OauthErrorTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Glue\OauthBackendApi\Api\Backend\Processor\RefreshTokensBackendProcessor;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group RefreshTokensBackendProcessorTest
 * Add your own group annotations below this line
 */
class RefreshTokensBackendProcessorTest extends Unit
{
    protected const string ISSUED_REFRESH_TOKEN = 'def50200ffffffffffffffffffffffffffffffffffffffffff';

    protected const string ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9';

    protected const string REFRESH_TOKEN = 'def50200a1b2c3d4e5f6789012345678901234567890abcdef';

    protected const string TOKEN_TYPE = 'Bearer';

    protected const int EXPIRES_IN = 3600;

    protected const string GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';

    protected const string GLUE_BACKEND_API_APPLICATION = 'GLUE_BACKEND_API_APPLICATION';

    protected const string OAUTH_ERROR_TYPE = 'invalid_grant';

    protected const string OAUTH_ERROR_MESSAGE = 'The refresh token is invalid.';

    public function testGivenRefreshTokenWhenProcessPostThenRequestsRefreshTokenGrantForBackendApiApplication(): void
    {
        // Arrange
        $authenticationRequestTransfers = [];
        $processor = new RefreshTokensBackendProcessor(
            $this->createAuthenticationFacadeStub($this->createValidOauthResponseTransfer(), $authenticationRequestTransfers),
        );

        // Act
        $result = $processor->process($this->createResource(), $this->createPostOperation());

        // Assert
        $this->assertCount(1, $authenticationRequestTransfers);
        $oauthRequestTransfer = $authenticationRequestTransfers[0]->getOauthRequestOrFail();
        $this->assertSame(static::GRANT_TYPE_REFRESH_TOKEN, $oauthRequestTransfer->getGrantType());
        $this->assertSame(static::ISSUED_REFRESH_TOKEN, $oauthRequestTransfer->getRefreshToken());
        $this->assertSame(
            static::GLUE_BACKEND_API_APPLICATION,
            $authenticationRequestTransfers[0]->getRequestContextOrFail()->getRequestApplication(),
        );
        $this->assertSame(static::ACCESS_TOKEN, $result->accessToken);
        $this->assertSame(static::REFRESH_TOKEN, $result->refreshToken);
        $this->assertSame(static::TOKEN_TYPE, $result->tokenType);
        $this->assertSame(static::EXPIRES_IN, $result->expiresIn);
    }

    public function testGivenInvalidRefreshTokenWhenProcessPostThenThrowsGlueApiExceptionWithUnauthorizedStatus(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setIsValid(false)
            ->setError((new OauthErrorTransfer())->setErrorType(static::OAUTH_ERROR_TYPE)->setMessage(static::OAUTH_ERROR_MESSAGE));
        $processor = new RefreshTokensBackendProcessor($this->createAuthenticationFacadeStub($oauthResponseTransfer));

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

    public function testGivenInvalidRefreshTokenWithoutErrorDetailsWhenProcessPostThenThrowsGlueApiExceptionWithDefaults(): void
    {
        // Arrange
        $processor = new RefreshTokensBackendProcessor(
            $this->createAuthenticationFacadeStub((new OauthResponseTransfer())->setIsValid(false)),
        );

        // Act + Assert
        try {
            $processor->process($this->createResource(), $this->createPostOperation());
            $this->fail('Expected GlueApiException to be thrown.');
        } catch (GlueApiException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());
            $this->assertSame('001', $exception->getErrorCode());
            $this->assertSame('Failed to refresh the access token.', $exception->getMessage());
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

    protected function createResource(): RefreshTokensBackendResource
    {
        $resource = new RefreshTokensBackendResource();
        $resource->refreshToken = static::ISSUED_REFRESH_TOKEN;

        return $resource;
    }

    protected function createPostOperation(): Post
    {
        return new Post(class: RefreshTokensBackendResource::class);
    }
}
