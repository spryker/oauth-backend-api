<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\OauthBackendApi\Api\Backend\Processor;

use Generated\Shared\Transfer\GlueAuthenticationRequestContextTransfer;
use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Processor\AbstractBackendProcessor;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requests OAuth grants through the {@see AuthenticationFacadeInterface} so that they are selected for the
 * `GLUE_BACKEND_API_APPLICATION` context.
 */
abstract class AbstractTokenBackendProcessor extends AbstractBackendProcessor
{
    /**
     * @uses \Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\ApplicationIdentifierRequestBuilderPlugin::GLUE_BACKEND_API_APPLICATION
     */
    protected const string GLUE_BACKEND_API_APPLICATION = 'GLUE_BACKEND_API_APPLICATION';

    /**
     * @uses \Spryker\Glue\OauthBackendApi\OauthBackendApiConfig::RESPONSE_CODE_ACCESS_CODE_INVALID
     */
    protected const string ERROR_CODE_INVALID_GRANT = '001';

    public function __construct(
        protected AuthenticationFacadeInterface $authenticationFacade,
    ) {
    }

    protected function authenticate(OauthRequestTransfer $oauthRequestTransfer): OauthResponseTransfer
    {
        $glueAuthenticationRequestTransfer = (new GlueAuthenticationRequestTransfer())
            ->setOauthRequest($oauthRequestTransfer)
            ->setRequestContext(
                (new GlueAuthenticationRequestContextTransfer())
                    ->setRequestApplication(static::GLUE_BACKEND_API_APPLICATION),
            );

        return $this->authenticationFacade
            ->authenticate($glueAuthenticationRequestTransfer)
            ->getOauthResponseOrFail();
    }

    protected function createInvalidGrantException(
        OauthResponseTransfer $oauthResponseTransfer,
        string $defaultMessage,
    ): GlueApiException {
        $oauthErrorTransfer = $oauthResponseTransfer->getError();

        return new GlueApiException(
            Response::HTTP_UNAUTHORIZED,
            $oauthErrorTransfer?->getErrorType() ?? static::ERROR_CODE_INVALID_GRANT,
            $oauthErrorTransfer?->getMessage() ?? $defaultMessage,
        );
    }
}
