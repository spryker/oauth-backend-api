<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthBackendApi\Processor\Mapper;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\OauthAccessTokenDataTransfer;

interface GlueRequestMapperInterface
{
    public function mapOauthAccessTokenDataTransferToGlueRequestTransfer(
        OauthAccessTokenDataTransfer $oauthAccessTokenDataTransfer,
        GlueRequestTransfer $glueRequestTransfer
    ): GlueRequestTransfer;
}
