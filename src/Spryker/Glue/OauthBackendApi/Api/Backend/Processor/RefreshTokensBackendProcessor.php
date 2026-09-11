<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\OauthBackendApi\Api\Backend\Processor;

use Generated\Api\Backend\RefreshTokensBackendResource;
use Generated\Shared\Transfer\OauthRequestTransfer;

/**
 * Exchanges a refresh token of a Back Office user or a merchant user for a new token pair.
 */
class RefreshTokensBackendProcessor extends AbstractTokenBackendProcessor
{
    /**
     * @uses \Spryker\Zed\Oauth\OauthConfig::GRANT_TYPE_REFRESH_TOKEN
     */
    protected const string GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';

    protected const string ERROR_MESSAGE_INVALID_REFRESH_TOKEN = 'Failed to refresh the access token.';

    protected function processPost(mixed $data): mixed
    {
        return $this->processRefreshTokenGrant($data);
    }

    protected function processRefreshTokenGrant(RefreshTokensBackendResource $resource): RefreshTokensBackendResource
    {
        $oauthRequestTransfer = (new OauthRequestTransfer())
            ->setGrantType(static::GRANT_TYPE_REFRESH_TOKEN)
            ->setRefreshToken($resource->refreshToken);

        $oauthResponseTransfer = $this->authenticate($oauthRequestTransfer);

        if ($oauthResponseTransfer->getIsValid() === false) {
            throw $this->createInvalidGrantException($oauthResponseTransfer, static::ERROR_MESSAGE_INVALID_REFRESH_TOKEN);
        }

        $resource->accessToken = $oauthResponseTransfer->getAccessToken();
        $resource->tokenType = $oauthResponseTransfer->getTokenType();
        $resource->expiresIn = $oauthResponseTransfer->getExpiresIn();
        $resource->refreshToken = $oauthResponseTransfer->getRefreshToken();

        return $resource;
    }
}
