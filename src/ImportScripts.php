<?php

declare(strict_types=1);

namespace Adlacruzes\Composer\ImportScripts;

use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Package\RootPackageInterface;
use RuntimeException;
use Seld\JsonLint\ParsingException;

class ImportScripts
{
    /**
     * @var RootPackageInterface
     */
    private $package;

    public function __construct(
        RootPackageInterface $package
    ) {
        $this->package = $package;
    }

    /**
     * @throws JsonValidationException
     */
    public function execute(): void
    {
        if (false === method_exists($this->package, 'setScripts')) {
            return;
        }

        $extra = $this->package->getExtra();

        $allowFailures = $this->getAllowFailures($extra);

        $scripts = $this->getScriptsFromExtra($extra, $allowFailures);

        if (false === empty($scripts)) {
            $composerScripts = $this->package->getScripts();

            if (true === $this->getOverrideFromExtra($extra)) {
                $this->package->setScripts(array_merge($composerScripts, $scripts));
            } else {
                $this->package->setScripts(array_merge($scripts, $composerScripts));
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

        if (isset($extra['import-scripts']['include'])) {
            if (is_array($extra['import-scripts']['include'])) {
                foreach ($extra['import-scripts']['include'] as $include) {
                    try {
                        $json = new JsonFile($include);
                        if (true === @$json->validateSchema(JsonFile::STRICT_SCHEMA, __DIR__ . '/import-scripts-schema.json')) {
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
     * @param array<string, string> $scripts
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
}
