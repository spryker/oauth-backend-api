<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\OauthBackendApi\Processor\Logger;

use Generated\Shared\Transfer\OauthRequestTransfer;

interface AuditLoggerInterface
{
    public function addFailedLoginAuditLog(OauthRequestTransfer $oauthRequestTransfer): void;

    public function addSuccessfulLoginAuditLog(OauthRequestTransfer $oauthRequestTransfer): void;
}
