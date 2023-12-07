<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthBackendApi\Processor\Extractor;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Spryker\Glue\OauthBackendApi\OauthBackendApiConfig;

class BackendAccessTokenExtractor implements BackendAccessTokenExtractorInterface
{
    /**
     * @param \Generated\Shared\Transfer\GlueRequestTransfer $glueRequestTransfer
     *
     * @return array<string>|null
     */
    public function extract(GlueRequestTransfer $glueRequestTransfer): ?array
    {
        if (!$this->isAuthorizationHeaderSet($glueRequestTransfer)) {
            return null;
        }

        return $this->extractTokenData($glueRequestTransfer->getMeta()[OauthBackendApiConfig::HEADER_AUTHORIZATION][0]);
    }

    /**
     * @param \Generated\Shared\Transfer\GlueRequestTransfer $glueRequestTransfer
     *
     * @return bool
     */
    public function isAuthorizationHeaderSet(GlueRequestTransfer $glueRequestTransfer): bool
    {
        return ($glueRequestTransfer->getMeta() &&
            array_key_exists(OauthBackendApiConfig::HEADER_AUTHORIZATION, $glueRequestTransfer->getMeta()));
    }

    /**
     * @param string $authorizationToken
     *
     * @return array<string>|null
     */
    protected function extractTokenData(string $authorizationToken): ?array
    {
        $result = preg_split('/\s+/', $authorizationToken);
        if ($result === false || !isset($result[1])) {
            return null;
        }

        return $result;
    }
}
