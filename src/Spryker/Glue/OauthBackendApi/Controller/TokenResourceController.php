<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthBackendApi\Controller;

use Generated\Shared\Transfer\ApiTokenAttributesTransfer;
use Generated\Shared\Transfer\ApiTokenResponseAttributesTransfer;
use Generated\Shared\Transfer\GlueAuthenticationRequestContextTransfer;
use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\GlueAuthenticationResponseTransfer;
use Generated\Shared\Transfer\GlueErrorTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueResourceTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Spryker\Glue\Kernel\Backend\Controller\AbstractBackendApiController;
use Spryker\Glue\OauthBackendApi\OauthBackendApiConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Spryker\Glue\OauthBackendApi\OauthBackendApiFactory getFactory()
 */
class TokenResourceController extends AbstractBackendApiController
{
    /**
     * @var string
     */
    protected const GLUE_BACKEND_API_APPLICATION = 'GLUE_BACKEND_API_APPLICATION';

    /**
     * @Glue({
     *     "post": {
     *          "summary": [
     *              "Creates access token for user."
     *          ],
     *          "parameters": [{
     *              "ref": "acceptLanguage"
     *          }],
     *          "responseAttributesClassName": "Generated\\Shared\\Transfer\\ApiTokenResponseAttributesTransfer",
     *          "requestAttributesClassName": "Generated\\Shared\\Transfer\\ApiTokenAttributesTransfer",
     *          "responses": {
     *              "400": "Bad request"
     *          }
     *     }
     * })
     *
     * @param \Generated\Shared\Transfer\ApiTokenAttributesTransfer $apiTokenAttributesTransfer
     * @param \Generated\Shared\Transfer\GlueRequestTransfer $glueRequestTransfer
     *
     * @return \Generated\Shared\Transfer\GlueResponseTransfer
     */
    public function postAction(
        ApiTokenAttributesTransfer $apiTokenAttributesTransfer,
        GlueRequestTransfer $glueRequestTransfer
    ): GlueResponseTransfer {
        $oauthRequestTransfer = (new OauthRequestTransfer())
            ->fromArray($apiTokenAttributesTransfer->toArray(), true);

        $glueAuthenticationRequestContextTransfer = (new GlueAuthenticationRequestContextTransfer())
            ->setRequestApplication(static::GLUE_BACKEND_API_APPLICATION);

        $glueAuthenticationRequestTransfer = (new GlueAuthenticationRequestTransfer())
            ->setOauthRequest($oauthRequestTransfer)
            ->setRequestContext($glueAuthenticationRequestContextTransfer);

        $glueAuthenticationResponseTransfer = $this->getFactory()->getAuthenticationFacade()->authenticate($glueAuthenticationRequestTransfer);

        $glueResponseTransfer = $this->mapAuthenticationAttributesToGlueResponseTransfer(
            $glueAuthenticationResponseTransfer,
            $glueRequestTransfer,
        );

        if ($this->getFactory()->getConfig()->isConventionalResponseCodeEnabled() && !$glueResponseTransfer->getHttpStatus()) {
            $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);
        }

        return $glueResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\GlueAuthenticationResponseTransfer $glueAuthenticationResponseTransfer
     * @param \Generated\Shared\Transfer\GlueRequestTransfer $glueRequestTransfer
     *
     * @return \Generated\Shared\Transfer\GlueResponseTransfer
     */
    protected function mapAuthenticationAttributesToGlueResponseTransfer(
        GlueAuthenticationResponseTransfer $glueAuthenticationResponseTransfer,
        GlueRequestTransfer $glueRequestTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();

        if ($glueAuthenticationResponseTransfer->getOauthResponseOrFail()->getIsValid() === false) {
            $glueResponseTransfer
                ->setHttpStatus(Response::HTTP_BAD_REQUEST)
                ->addError((new GlueErrorTransfer())
                    ->setMessage($glueAuthenticationResponseTransfer->getOauthResponseOrFail()->getErrorOrFail()->getMessage())
                    ->setStatus(Response::HTTP_BAD_REQUEST)
                    ->setCode($glueAuthenticationResponseTransfer->getOauthResponseOrFail()->getErrorOrFail()->getErrorType()));

            return $glueResponseTransfer;
        }

        $resourceTransfer = (new GlueResourceTransfer())
            ->setType(OauthBackendApiConfig::RESOURCE_TOKEN)
            ->setAttributes(
                (new ApiTokenResponseAttributesTransfer())
                ->fromArray($glueAuthenticationResponseTransfer->getOauthResponseOrFail()->toArray(), true),
            );
        $glueResponseTransfer->addResource($resourceTransfer);

        return $glueResponseTransfer;
    }
}
