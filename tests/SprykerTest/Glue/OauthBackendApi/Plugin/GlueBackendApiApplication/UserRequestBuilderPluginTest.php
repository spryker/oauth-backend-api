<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\OauthBackendApi\Plugin\GlueBackendApiApplication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Spryker\Glue\OauthBackendApi\Plugin\GlueBackendApiApplication\UserRequestBuilderPlugin;
use SprykerTest\Glue\OauthBackendApi\OauthBackendApiTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group OauthBackendApi
 * @group Plugin
 * @group GlueBackendApiApplication
 * @group UserRequestBuilderPluginTest
 * Add your own group annotations below this line
 */
class UserRequestBuilderPluginTest extends Unit
{
    /**
     * @var string
     */
    protected const JWT_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ7XCJpZF91c2VyXCI6MSwgXCJ1dWlkXCI6XCJ0ZXN0XCJ9IiwiaWF0IjoxNTE2MjM5MDIyfQ.MyyguYSEdNLjbe-CoBG_HetocStir-cw1WGK3cchDRc';

    /**
     * JWT where sub claim is a plain numeric string, not a JSON-encoded object.
     * Triggers `json_decode("1234567890", true)` returning int instead of array.
     *
     * @var string
     */
    protected const JWT_TOKEN_WITH_NON_JSON_OBJECT_SUB = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

    /**
     * @var int
     */
    protected const SURROGATE_IDENTIFIER = 1;

    /**
     * @var string
     */
    protected const NATURAL_IDENTIFIER = 'test';

    /**
     * @var \SprykerTest\Glue\OauthBackendApi\OauthBackendApiTester
     */
    protected OauthBackendApiTester $tester;

    public function testUserRequestBuilderReturnsEmptyRequestUserWhenAuthorizationTokenNotProvided(): void
    {
        // Act
        $glueRequestTransfer = (new UserRequestBuilderPlugin())->build(new GlueRequestTransfer());

        // Assert
        $this->assertEmpty($glueRequestTransfer->getRequestUser());
    }

    public function testUserRequestBuilderReturnsEmptyRequestUserWhenAuthorizationTokenIsWrong(): void
    {
        // Arrange
        $glueRequestTransfer = (new GlueRequestTransfer())->setMeta(['authorization' => [0 => 'Bearer invalid']]);

        // Act
        $glueRequestTransfer = (new UserRequestBuilderPlugin())->build($glueRequestTransfer);

        // Assert
        $this->assertEmpty($glueRequestTransfer->getRequestUser());
    }

    public function testUserRequestBuilderReturnsEmptyRequestUserWhenJwtSubClaimIsNotJsonObject(): void
    {
        // Arrange
        $glueRequestTransfer = (new GlueRequestTransfer())->setMeta(['authorization' => [0 => 'Bearer ' . static::JWT_TOKEN_WITH_NON_JSON_OBJECT_SUB]]);

        // Act
        $glueRequestTransfer = (new UserRequestBuilderPlugin())->build($glueRequestTransfer);

        // Assert
        $this->assertEmpty($glueRequestTransfer->getRequestUser());
    }

    public function testUserRequestBuilderReturnsNotEmptyRequestUserWhenAuthorizationTokenIsCorrect(): void
    {
        //Arrange
        $glueRequestTransfer = (new GlueRequestTransfer())->setMeta(['authorization' => [0 => 'Bearer ' . static::JWT_TOKEN]]);

        //Act
        $glueRequestTransfer = (new UserRequestBuilderPlugin())->build($glueRequestTransfer);

        //Assert
        $this->assertNotEmpty($glueRequestTransfer->getRequestUser());
        $this->assertSame(static::SURROGATE_IDENTIFIER, $glueRequestTransfer->getRequestUser()->getSurrogateIdentifier());
        $this->assertSame(static::NATURAL_IDENTIFIER, $glueRequestTransfer->getRequestUser()->getNaturalIdentifier());
    }
}
