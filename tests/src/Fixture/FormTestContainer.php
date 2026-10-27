<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Fixture;

use Composer\InstalledVersions;
use Derafu\Form\Contract\Captcha\CaptchaProviderInterface;
use Derafu\Form\Contract\Csrf\CsrfTokenManagerInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Test infrastructure: loads the real service graph and supplies test doubles
 * only at the application's CSRF and captcha integration boundaries.
 */
final class FormTestContainer extends ContainerBuilder
{
    public function __construct(
        ?CsrfTokenManagerInterface $csrfTokenManager = null,
        ?CaptchaProviderInterface $captchaProvider = null
    ) {
        parent::__construct();
        $root = dirname(__DIR__, 3);
        $this->setParameter('kernel.project_dir', $root . '/tests/fixtures/project');
        (new YamlFileLoader($this, new FileLocator([
            $root . '/tests/fixtures/config',
            (InstalledVersions::getInstallPath('derafu/renderer')
                ?? throw new RuntimeException('derafu/renderer must be installed for these tests.')) . '/resources/config',
        ])))
            ->load('rendering.yaml');
        $services = [
            CsrfTokenManagerInterface::class => $csrfTokenManager,
            CaptchaProviderInterface::class => $captchaProvider,
        ];
        foreach ($services as $id => $service) {
            if ($service !== null) {
                $this->register($id)->setSynthetic(true)->setPublic(true);
            }
        }
        $this->getDefinition(FormRendererInterface::class)->setPublic(true);
        $this->compile();
        foreach ($services as $id => $service) {
            if ($service !== null) {
                $this->set($id, $service);
            }
        }
    }

    public function getFormRenderer(): FormRendererInterface
    {
        $renderer = $this->get(FormRendererInterface::class);
        if (!$renderer instanceof FormRendererInterface) {
            throw new RuntimeException('The form renderer service has an invalid type.');
        }

        return $renderer;
    }
}
