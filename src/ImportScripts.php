<?php

declare(strict_types=1);

namespace Adlacruzes\Composer\ImportScripts;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Util\RemoteFilesystem;
use RuntimeException;
use Seld\JsonLint\ParsingException;

class ImportScripts
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(
        Composer $composer,
        IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * @throws JsonValidationException
     * @throws ParsingException
     */
    public function execute(): void
    {
        $package = $this->composer->getPackage();

        if (false === method_exists($package, 'setScripts')) {
            $this->io->write('Skipping importing scripts', true, IOInterface::VERY_VERBOSE);

            return;
        }

        $extra = $package->getExtra();

        $allowFailures = $this->getAllowFailures($extra);

        $scripts = $this->getScriptsFromExtra($extra, $allowFailures);

        if (0 !== count($scripts)) {
            $composerScripts = $package->getScripts();

            if (true === $this->getOverrideFromExtra($extra)) {
                $package->setScripts(array_merge($composerScripts, $scripts));
            } else {
                $package->setScripts(array_merge($scripts, $composerScripts));
            }
        }
    }

    /**
     * @param array<mixed> $extra
     * @return bool
     */
    private function getAllowFailures(array $extra): bool
    {
        if (isset($extra['import-scripts']['allow_failures'])) {
            if (is_bool($extra['import-scripts']['allow_failures'])) {
                return $extra['import-scripts']['allow_failures'];
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $extra
     * @param bool $allowFailures
     * @return array<mixed>
     * @throws JsonValidationException
     * @throws RuntimeException
     * @throws ParsingException
     */
    private function getScriptsFromExtra(array $extra, bool $allowFailures): array
    {
        $scripts = [];

        $httpDownloader = $this->getHttpDownloader();

        if (isset($extra['import-scripts']['include'])) {
            if (is_array($extra['import-scripts']['include'])) {
                foreach ($extra['import-scripts']['include'] as $include) {
                    try {
                        $json = new JsonFile($include, $httpDownloader);
                        if (true === @$json->validateSchema(JsonFile::STRICT_SCHEMA, __DIR__ . '/import-scripts-schema.json')) {
                            $this->io->write("Importing script: $include", true, IOInterface::VERY_VERBOSE);
                            $scripts = array_merge(
                                $scripts,
                                $this->parseScriptsToComposerFormat($json->read()['scripts'])
                            );
                        }
                    } catch (ParsingException | JsonValidationException | RuntimeException $e) {
                        if (false === $allowFailures) {
                            throw $e;
                        }
                    }
                }
            }
        }

        return $scripts;
    }

    /**
     * @param array<mixed> $extra
     * @return bool
     */
    private function getOverrideFromExtra(array $extra): bool
    {
        if (isset($extra['import-scripts']['override'])) {
            if (is_bool($extra['import-scripts']['override'])) {
                return $extra['import-scripts']['override'];
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $scripts
     * @return array<string, array<int, string>>
     */
    private function parseScriptsToComposerFormat(array $scripts): array
    {
        $parsed = [];

        foreach ($scripts as $name => $script) {
            if (is_array($script)) {
                $parsed[$name] = $script;
            } else {
                $parsed[$name] = [$script];
            }
        }

        return $parsed;
    }

    /**
     * @return mixed
     */
    private function getHttpDownloader()
    {
        if (class_exists('Composer\Util\HttpDownloader')) {
            return new \Composer\Util\HttpDownloader($this->io, $this->composer->getConfig());
        }

        // Composer v1 compatibility
        return new RemoteFilesystem($this->io, $this->composer->getConfig());
    }
}
