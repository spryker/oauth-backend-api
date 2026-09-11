<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Glue\OauthBackendApi\BackendApi;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\GlueAuthenticationResponseTransfer;
use Generated\Shared\Transfer\OauthErrorTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\Glue\OauthBackendApi\Api\Backend\EventSubscriber\TokenRequestSubscriber;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group TokenRequestSubscriberTest
 * Add your own group annotations below this line
 */
class TokenRequestSubscriberTest extends Unit
{
    protected const string USERNAME = 'admin@spryker.com';

    protected const string PASSWORD = 'change123';

    protected const string ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9';

    protected const string REFRESH_TOKEN = 'def50200a1b2c3d4e5f6789012345678901234567890abcdef';

    protected const string CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    protected const string CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    protected const string OAUTH_ERROR_TYPE = 'invalid_grant';

    protected const string OAUTH_ERROR_MESSAGE = 'The user credentials were incorrect.';

    public function testGivenJsonTextUnderFormContentTypeWhenPostingTokenThenAnswersLegacyFlatBody(): void
    {
        // Arrange
        $requests = [];
        $event = $this->createRequestEvent(
            Request::create('/token', 'POST', [], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM], json_encode([
                'grantType' => 'password',
                'username' => static::USERNAME,
                'password' => static::PASSWORD,
            ])),
        );

        // Act
        $this->createSubscriber($this->createValidOauthResponseTransfer(), $requests)->onKernelRequest($event);

        // Assert
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = json_decode((string)$response->getContent(), true);
        $this->assertSame(static::ACCESS_TOKEN, $body['access_token']);
        $this->assertSame(static::REFRESH_TOKEN, $body['refresh_token']);
        $this->assertSame('Bearer', $body['token_type']);
        $this->assertSame(static::USERNAME, $requests[0]->getOauthRequestOrFail()->getUsername());
        $this->assertSame('password', $requests[0]->getOauthRequestOrFail()->getGrantType());
        $this->assertSame('GLUE_BACKEND_API_APPLICATION', $requests[0]->getRequestContextOrFail()->getRequestApplication());
    }

    public function testGivenJsonTextAlreadyParsedIntoTheFormBagWhenPostingTokenThenPrefersTheJsonContent(): void
    {
        // Arrange
        $json = json_encode(['grantType' => 'password', 'username' => static::USERNAME, 'password' => static::PASSWORD]);
        $requests = [];
        $event = $this->createRequestEvent(
            Request::create('/token', 'POST', [$json => ''], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM], $json),
        );

        // Act
        $this->createSubscriber($this->createValidOauthResponseTransfer(), $requests)->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_OK, $event->getResponse()?->getStatusCode());
        $this->assertSame('password', $requests[0]->getOauthRequestOrFail()->getGrantType());
        $this->assertSame(static::USERNAME, $requests[0]->getOauthRequestOrFail()->getUsername());
    }

    public function testGivenFormFieldsWhenPostingTokenThenAuthenticatesWithThem(): void
    {
        // Arrange
        $requests = [];
        $event = $this->createRequestEvent(
            Request::create('/token', 'POST', ['grant_type' => 'password', 'username' => static::USERNAME, 'password' => static::PASSWORD], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM]),
        );

        // Act
        $this->createSubscriber($this->createValidOauthResponseTransfer(), $requests)->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_OK, $event->getResponse()?->getStatusCode());
        $this->assertSame(static::PASSWORD, $requests[0]->getOauthRequestOrFail()->getPassword());
    }

    public function testGivenRefreshTokenGrantWhenPostingTokenThenPassesRefreshTokenThrough(): void
    {
        // Arrange
        $requests = [];
        $event = $this->createRequestEvent(
            Request::create('/token', 'POST', [], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM], 'grant_type=refresh_token&refresh_token=' . static::REFRESH_TOKEN),
        );

        // Act
        $this->createSubscriber($this->createValidOauthResponseTransfer(), $requests)->onKernelRequest($event);

        // Assert
        $this->assertSame('refresh_token', $requests[0]->getOauthRequestOrFail()->getGrantType());
        $this->assertSame(static::REFRESH_TOKEN, $requests[0]->getOauthRequestOrFail()->getRefreshToken());
    }

    public function testGivenInvalidCredentialsWhenPostingTokenThenAnswersLegacyErrorList(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setIsValid(false)
            ->setError((new OauthErrorTransfer())->setErrorType(static::OAUTH_ERROR_TYPE)->setMessage(static::OAUTH_ERROR_MESSAGE));
        $event = $this->createRequestEvent(
            Request::create('/token', 'POST', [], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM], 'grantType=password&username=x&password=y'),
        );

        // Act
        $this->createSubscriber($oauthResponseTransfer)->onKernelRequest($event);

        // Assert
        $response = $event->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response?->getStatusCode());
        $this->assertSame(
            [['message' => static::OAUTH_ERROR_MESSAGE, 'status' => Response::HTTP_BAD_REQUEST, 'code' => static::OAUTH_ERROR_TYPE]],
            json_decode((string)$response->getContent(), true),
        );
    }

    public function testGivenJsonApiContentTypeWhenPostingTokenThenLeavesRequestToTheResource(): void
    {
        // Arrange
        $event = $this->createRequestEvent(
            Request::create('/token', 'POST', [], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_JSON_API], '{"data":{"type":"tokens","attributes":{}}}'),
        );

        // Act
        $this->createSubscriber($this->createValidOauthResponseTransfer())->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenOtherPathWhenPostingThenIgnoresRequest(): void
    {
        // Arrange
        $event = $this->createRequestEvent(
            Request::create('/customers', 'POST', [], [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM], 'username=x'),
        );

        // Act
        $this->createSubscriber($this->createValidOauthResponseTransfer())->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    /**
     * @param array<\Generated\Shared\Transfer\GlueAuthenticationRequestTransfer> $requests
     */
    protected function createSubscriber(OauthResponseTransfer $oauthResponseTransfer, array &$requests = []): TokenRequestSubscriber
    {
        $authenticationFacade = Stub::makeEmpty(AuthenticationFacadeInterface::class, [
            'authenticate' => function (GlueAuthenticationRequestTransfer $glueAuthenticationRequestTransfer) use ($oauthResponseTransfer, &$requests): GlueAuthenticationResponseTransfer {
                $requests[] = $glueAuthenticationRequestTransfer;

                return (new GlueAuthenticationResponseTransfer())->setOauthResponse($oauthResponseTransfer);
            },
        ]);

        return new TokenRequestSubscriber($authenticationFacade);
    }

    protected function createValidOauthResponseTransfer(): OauthResponseTransfer
    {
        return (new OauthResponseTransfer())
            ->setIsValid(true)
            ->setAccessToken(static::ACCESS_TOKEN)
            ->setTokenType('Bearer')
            ->setExpiresIn(28800)
            ->setRefreshToken(static::REFRESH_TOKEN);
    }

    protected function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(Stub::makeEmpty(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
