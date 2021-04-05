<?php

declare(strict_types=1);

namespace Adlacruzes\Composer\ImportScripts\Tests;

use Adlacruzes\Composer\ImportScripts\ImportScripts;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
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
     * @var Composer & MockObject
     */
    private $composer;

    /**
     * @var IOInterface & MockObject
     */
    private $io;

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
        $this->composer = $this->createMock(Composer::class);
        $this->io = $this->createMock(IOInterface::class);
        $this->package = $this->createMock(RootPackage::class);

        $this->composer
            ->method('getConfig')
            ->willReturn(new Config());

        $this->plugin = new ImportScripts(
            $this->composer,
            $this->io
        );
    }

    /**
     * @throws Exception
     */
    public function testPackageWithoutSetScriptsMethod(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);
        $package = $this->createMock(RootPackageInterface::class);

        $package
            ->expects($this->never())
            ->method('getExtra');

        $package
            ->expects($this->never())
            ->method('getScripts');

        $composer
            ->method('getPackage')
            ->willReturn($package);

        $io
            ->expects($this->once())
            ->method('write');

        (new ImportScripts(
            $composer,
            $io
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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

        $this->plugin->execute();
    }

    /**
     * @throws Exception
     */
    public function testIncludeInvalidNestedScriptsWithAllowFailuresToFalse(): void
    {
        $this->expectException(JsonValidationException::class);

        $this->package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn([
                'import-scripts' => [
                    'allow_failures' => false,
                    'include' => [
                        __DIR__ . '/Fixtures/invalidNestedScripts.json',
                    ],
                ],
            ]);

        $this->package
            ->expects($this->never())
            ->method('getScripts');

        $this->package
            ->expects($this->never())
            ->method('setScripts');

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->never())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->once())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->once())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->once())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->once())
            ->method('write');

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

        $this->composer
            ->method('getPackage')
            ->willReturn($this->package);

        $this->io
            ->expects($this->once())
            ->method('write');

        $this->plugin->execute();
    }
}
