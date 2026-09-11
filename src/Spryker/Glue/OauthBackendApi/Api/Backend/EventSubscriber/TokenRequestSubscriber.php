<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\OauthBackendApi\Api\Backend\EventSubscriber;

use Generated\Shared\Transfer\OauthRequestTransfer;
use Spryker\ApiPlatform\Attribute\ApiType;
use Spryker\Glue\OauthBackendApi\Processor\Logger\AuditLogger;
use Spryker\Glue\OauthBackendApi\Processor\Token\LegacyTokenProcessor;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Serves `POST /token` requests that do not speak JSON:API - form-encoded or plain JSON bodies of the
 * pre-API-Platform contract - before routing, the way {@see \Spryker\Glue\AuthRestApi\Api\Storefront\EventSubscriber\TokenRequestSubscriber}
 * does for the Storefront API. JSON:API requests fall through to the `tokens` resource.
 */
#[ApiType(types: ['backend'])]
class TokenRequestSubscriber implements EventSubscriberInterface
{
    protected const string TOKEN_PATH = '/token';

    protected const string CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    protected const int PRIORITY_BEFORE_ROUTER = 100;

    protected const array ATTRIBUTE_KEYS_BY_PROPERTY = [
        OauthRequestTransfer::GRANT_TYPE => ['grantType', 'grant_type'],
        OauthRequestTransfer::USERNAME => ['username'],
        OauthRequestTransfer::PASSWORD => ['password'],
        OauthRequestTransfer::REFRESH_TOKEN => ['refreshToken', 'refresh_token'],
        OauthRequestTransfer::SCOPE => ['scope'],
    ];

    public function __construct(
        protected AuthenticationFacadeInterface $authenticationFacade,
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', static::PRIORITY_BEFORE_ROUTER],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() !== Request::METHOD_POST || rtrim($request->getPathInfo(), '/') !== static::TOKEN_PATH) {
            return;
        }

        if (str_starts_with((string)$request->headers->get('Content-Type'), static::CONTENT_TYPE_JSON_API)) {
            return;
        }

        $legacyTokenProcessor = new LegacyTokenProcessor($this->authenticationFacade, new AuditLogger());

        $event->setResponse($legacyTokenProcessor->createAccessToken($this->createOauthRequestTransfer($request)));
    }

    protected function createOauthRequestTransfer(Request $request): OauthRequestTransfer
    {
        $attributes = $this->extractAttributes($request);
        $oauthRequestTransfer = new OauthRequestTransfer();

        foreach (static::ATTRIBUTE_KEYS_BY_PROPERTY as $property => $keys) {
            foreach ($keys as $key) {
                if (isset($attributes[$key]) && is_scalar($attributes[$key])) {
                    $oauthRequestTransfer->offsetSet($property, (string)$attributes[$key]);

                    break;
                }
            }
        }

        return $oauthRequestTransfer;
    }

    /**
     * The legacy clients disagree on the encoding: the Robot suites send JSON text under a form-encoded
     * content type, which PHP has already parsed into a single meaningless form key by the time the
     * request arrives, and Cypress sends real form fields. Decodable JSON therefore wins over the form bag.
     *
     * @return array<int|string, mixed>
     */
    protected function extractAttributes(Request $request): array
    {
        $content = (string)$request->getContent();
        $jsonAttributes = json_decode($content, true);

        if (is_array($jsonAttributes)) {
            return $jsonAttributes;
        }

        $formAttributes = $request->request->all();

        if ($formAttributes !== []) {
            return $formAttributes;
        }

        parse_str($content, $parsedAttributes);

        return $parsedAttributes;
    }
}
