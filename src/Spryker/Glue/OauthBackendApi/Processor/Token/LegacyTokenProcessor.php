<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\OauthBackendApi\Processor\Token;

use Generated\Shared\Transfer\GlueAuthenticationRequestContextTransfer;
use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\Glue\OauthBackendApi\Processor\Logger\AuditLoggerInterface;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the pre-API-Platform `POST /token` contract of the Backend API: a flat snake_case token body
 * on success and a `400` error list on failure.
 */
class LegacyTokenProcessor
{
    /**
     * @uses \Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\ApplicationIdentifierRequestBuilderPlugin::GLUE_BACKEND_API_APPLICATION
     */
    protected const string GLUE_BACKEND_API_APPLICATION = 'GLUE_BACKEND_API_APPLICATION';

    protected const string KEY_ACCESS_TOKEN = 'access_token';

    protected const string KEY_TOKEN_TYPE = 'token_type';

    protected const string KEY_EXPIRES_IN = 'expires_in';

    protected const string KEY_REFRESH_TOKEN = 'refresh_token';

    protected const string KEY_MESSAGE = 'message';

    protected const string KEY_STATUS = 'status';

    protected const string KEY_CODE = 'code';

    protected const string ERROR_CODE_INVALID_CREDENTIALS = 'invalid_grant';

    protected const string ERROR_MESSAGE_INVALID_CREDENTIALS = 'Failed to authenticate user.';

    public function __construct(
        protected AuthenticationFacadeInterface $authenticationFacade,
        protected AuditLoggerInterface $auditLogger,
    ) {
    }

    public function createAccessToken(OauthRequestTransfer $oauthRequestTransfer): JsonResponse
    {
        $glueAuthenticationRequestTransfer = (new GlueAuthenticationRequestTransfer())
            ->setOauthRequest($oauthRequestTransfer)
            ->setRequestContext(
                (new GlueAuthenticationRequestContextTransfer())
                    ->setRequestApplication(static::GLUE_BACKEND_API_APPLICATION),
            );

        $oauthResponseTransfer = $this->authenticationFacade
            ->authenticate($glueAuthenticationRequestTransfer)
            ->getOauthResponseOrFail();

        if ($oauthResponseTransfer->getIsValid() === false) {
            $this->auditLogger->addFailedLoginAuditLog($oauthRequestTransfer);

            return $this->createErrorResponse($oauthResponseTransfer);
        }

        $this->auditLogger->addSuccessfulLoginAuditLog($oauthRequestTransfer);

        return new JsonResponse([
            static::KEY_TOKEN_TYPE => $oauthResponseTransfer->getTokenType(),
            static::KEY_EXPIRES_IN => $oauthResponseTransfer->getExpiresIn(),
            static::KEY_ACCESS_TOKEN => $oauthResponseTransfer->getAccessToken(),
            static::KEY_REFRESH_TOKEN => $oauthResponseTransfer->getRefreshToken(),
        ], Response::HTTP_OK);
    }

    protected function createErrorResponse(OauthResponseTransfer $oauthResponseTransfer): JsonResponse
    {
        $oauthErrorTransfer = $oauthResponseTransfer->getError();

        return new JsonResponse([
            [
                static::KEY_MESSAGE => $oauthErrorTransfer?->getMessage() ?? static::ERROR_MESSAGE_INVALID_CREDENTIALS,
                static::KEY_STATUS => Response::HTTP_BAD_REQUEST,
                static::KEY_CODE => $oauthErrorTransfer?->getErrorType() ?? static::ERROR_CODE_INVALID_CREDENTIALS,
            ],
        ], Response::HTTP_BAD_REQUEST);
    }
}
