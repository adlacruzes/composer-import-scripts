<?php

declare(strict_types=1);

namespace Adlacruzes\Composer\ImportScripts\Tests;

use Adlacruzes\Composer\ImportScripts\ImportScripts;
use Composer\Json\JsonValidationException;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\ParsingException;

class ImportScriptsTest extends TestCase
{
    /**
     * @var RootPackage & MockObject
     */
    private $package;

    /**
     * @var ImportScripts
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->package = $this->createMock(RootPackage::class);

        $this->plugin = new ImportScripts(
            $this->package
        );
    }

    /**
     * @throws Exception
     */
    public function testPackageWithoutSetScriptsMethod(): void
    {
        $package = $this->createMock(RootPackageInterface::class);

        $package
            ->expects($this->never())
            ->method('getExtra');

        $package
            ->expects($this->never())
            ->method('getScripts');

        (new ImportScripts(
            $package
        ))->execute();
    }

    /**
     * @throws Exception
     */
    public function testInvalidExtraComposerShouldNotThrowException(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'invalid' => 'extra',
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testNoIncludesShouldNotThrowException(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testIncludeNotFound(): void
    {
        $this->expectException(ParsingException::class);

        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        'invalid',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testIncludeNotFoundWithAllowFailuresToTrue(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'allow_failures' => true,
                    'include' => [
                        'invalid',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testIncludeInvalidSchemaWithAllowFailuresToFalse(): void
    {
        $this->expectException(JsonValidationException::class);

        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        __DIR__ . '/Fixtures/invalidSchema.json',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testIncludeInvalidSchemaWithAllowFailuresToTrue(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'allow_failures' => true,
                    'include' => [
                        __DIR__ . '/Fixtures/invalidSchema.json',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testValidSchemaWithEmptyScripts(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        __DIR__ . '/Fixtures/emptyInclude.json',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testImportMultipleScriptsWithNoComposerScripts(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        __DIR__ . '/Fixtures/multipleScripts.json',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->once())
            ->method('getScripts')
            ->willReturn([]);

        $this->package
            ->expects($this->once())
            ->method('setScripts')
            ->with(
                [
                    'one' => ['echo one'],
                    'two' => ['echo two'],
                    'three' => ['echo three'],
                ]
            );

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testImportAndOverrideMultipleScriptsWithComposerScripts(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        __DIR__ . '/Fixtures/multipleScripts.json',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->once())
            ->method('getScripts')
            ->willReturn([
                'scriptFrom' => ['composer'],
            ]);

        $this->package
            ->expects($this->once())
            ->method('setScripts')
            ->with(
                [
                    'one' => ['echo one'],
                    'two' => ['echo two'],
                    'three' => ['echo three'],
                    'scriptFrom' => ['composer'],
                ]
            );

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testImportAndNotOverrideMultipleScriptsWithComposerScripts(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        __DIR__ . '/Fixtures/multipleScripts.json',
                    ],
                    'override' => false,
                ],
            ]);

        $this->package
            ->expects($this->once())
            ->method('getScripts')
            ->willReturn([
                'one' => ['echo one from composer scripts'],
            ]);

        $this->package
            ->expects($this->once())
            ->method('setScripts')
            ->with(
                [
                    'one' => ['echo one from composer scripts'],
                    'two' => ['echo two'],
                    'three' => ['echo three'],
                ]
            );

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testImportAndOverrideNestedScriptsWithComposerScripts(): void
    {
        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'include' => [
                        __DIR__ . '/Fixtures/nestedScripts.json',
                    ],
                    'override' => true,
                ],
            ]);

        $this->package
            ->expects($this->once())
            ->method('getScripts')
            ->willReturn([
                'one' => ['echo one from composer scripts'],
            ]);

        $this->package
            ->expects($this->once())
            ->method('setScripts')
            ->with(
                [
                    'one' => ['echo one'],
                    'two' => ['echo two'],
                    'three' => ['echo three'],
                    'other' => [
                        'echo four',
                        'echo five',
                    ],
                ]
            );

        $this->plugin->execute();
    }
}
