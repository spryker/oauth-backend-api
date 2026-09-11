<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\OauthBackendApi\Api\Backend\Processor;

use Generated\Api\Backend\TokensBackendResource;
use Generated\Shared\Transfer\AuditLoggerConfigCriteriaTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Spryker\Shared\Log\AuditLoggerTrait;

/**
 * Issues Backend API access tokens for Back Office users and merchant users with the password grant.
 */
class TokensBackendProcessor extends AbstractTokenBackendProcessor
{
    use AuditLoggerTrait;

    /**
     * @uses \Spryker\Zed\Oauth\OauthConfig::GRANT_TYPE_PASSWORD
     */
    protected const string GRANT_TYPE_PASSWORD = 'password';

    /**
     * @uses \Spryker\Shared\Log\LogConfig::AUDIT_LOGGER_CHANNEL_NAME_SECURITY
     */
    protected const string AUDIT_LOGGER_CHANNEL_NAME_SECURITY = 'security';

    /**
     * @uses \Spryker\Shared\Log\Handler\TagFilterBufferedStreamHandler::RECORD_KEY_CONTEXT_TAGS
     */
    protected const string AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS = 'tags';

    protected const string AUDIT_LOGGER_ACTION_FAILED_LOGIN = 'Failed Login';

    protected const string AUDIT_LOGGER_ACTION_SUCCESSFUL_LOGIN = 'Successful Login';

    protected const string AUDIT_LOGGER_TAG_FAILED_LOGIN = 'failed_login';

    protected const string AUDIT_LOGGER_TAG_SUCCESSFUL_LOGIN = 'successful_login';

    protected const string AUDIT_LOGGER_CONTEXT_KEY_USERNAME = 'username';

    protected const string ERROR_MESSAGE_INVALID_CREDENTIALS = 'Failed to authenticate user.';

    protected function processPost(mixed $data): mixed
    {
        return $this->processPasswordGrant($data);
    }

    protected function processPasswordGrant(TokensBackendResource $resource): TokensBackendResource
    {
        $oauthRequestTransfer = (new OauthRequestTransfer())
            ->setGrantType(static::GRANT_TYPE_PASSWORD)
            ->setUsername($resource->username)
            ->setPassword($resource->password);

        $oauthResponseTransfer = $this->authenticate($oauthRequestTransfer);

        if ($oauthResponseTransfer->getIsValid() === false) {
            $this->addLoginAuditLog(static::AUDIT_LOGGER_ACTION_FAILED_LOGIN, static::AUDIT_LOGGER_TAG_FAILED_LOGIN, $oauthRequestTransfer);

            throw $this->createInvalidGrantException($oauthResponseTransfer, static::ERROR_MESSAGE_INVALID_CREDENTIALS);
        }

        $this->addLoginAuditLog(static::AUDIT_LOGGER_ACTION_SUCCESSFUL_LOGIN, static::AUDIT_LOGGER_TAG_SUCCESSFUL_LOGIN, $oauthRequestTransfer);

        $resource->accessToken = $oauthResponseTransfer->getAccessToken();
        $resource->tokenType = $oauthResponseTransfer->getTokenType();
        $resource->expiresIn = $oauthResponseTransfer->getExpiresIn();
        $resource->refreshToken = $oauthResponseTransfer->getRefreshToken();

        return $resource;
    }

    protected function addLoginAuditLog(string $action, string $tag, OauthRequestTransfer $oauthRequestTransfer): void
    {
        $this->getAuditLogger(
            (new AuditLoggerConfigCriteriaTransfer())->setChannelName(static::AUDIT_LOGGER_CHANNEL_NAME_SECURITY),
        )->info($action, [
            static::AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS => [$tag],
            static::AUDIT_LOGGER_CONTEXT_KEY_USERNAME => $oauthRequestTransfer->getUsername(),
        ]);
    }
}
