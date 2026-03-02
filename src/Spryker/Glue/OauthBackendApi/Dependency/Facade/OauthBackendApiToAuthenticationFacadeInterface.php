<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthBackendApi\Dependency\Facade;

use Generated\Shared\Transfer\GlueAuthenticationRequestTransfer;
use Generated\Shared\Transfer\GlueAuthenticationResponseTransfer;

interface OauthBackendApiToAuthenticationFacadeInterface
{
    public function authenticate(GlueAuthenticationRequestTransfer $glueAuthenticationRequestTransfer): GlueAuthenticationResponseTransfer;
}
