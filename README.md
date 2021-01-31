# Composer import scripts

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg?style=flat-square)](https://php.net/)
![Github actions](https://github.com/adlacruzes/composer-import-scripts/workflows/Continuous%20Integration/badge.svg?branch=main)

Composer import scripts is a plugin to import composer scripts from other files at runtime.

I needed a simple way to import scripts and unify commands across multiple libraries. If you need a more powerful tool, you can look at the excellent [composer-merge-plugin](https://github.com/wikimedia/composer-merge-plugin). 

Imported commands have to be invoked with `composer run`.

## Installation

```sh
composer require adlacruzes/composer-import-scripts
```

## Usage

```json
{
    "extra": {
        "import-scripts": {
            "include": [
                "file1.json",
                "file2.json"
            ],
            "allow_failures": false,
            "override": true
        }
    }
}
```

## Configuration

### include
 - `required`

`include` setting is a list of files to import. These files need to be valid JSON according to the [import scripts schema](#json-schema).

### allow_failures
 - `optional`
 - `default: false`

`allow_failures` is a setting that controls errors from the plugin. If set to `true` the following file errors will be ignored and zero scripts will be imported from these files:
 - Invalid JSON schema
 - Invalid file

### override
 - `optional`
 - `default: true`

When `override` is set to `true` the scripts from the `include` setting will override the scripts with the same name defined in `composer.json` 

## JSON schema

This is the scheme files must adapt.

```json
{
    "$schema": "http://json-schema.org/draft-04/schema#",
    "type": "object",
    "properties": {
        "scripts": {
            "type": [
                "object"
            ]
        }
    },
    "required": [
        "scripts"
    ]
}
```

An example of this schema:

```json
{
    "scripts": {
        "one": "echo one",
        "two": "echo two",
        "three": "echo three"
    }
}
```
