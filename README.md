# Mantis 2 GitHub Connector

[![Packagist Version](https://img.shields.io/packagist/v/artemeon/mantis2github?style=for-the-badge)](https://packagist.org/packages/artemeon/mantis2github)
![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=for-the-badge)
[![Packagist Downloads](https://img.shields.io/packagist/dt/artemeon/mantis2github?style=for-the-badge)](https://packagist.org/packages/artemeon/mantis2github)
[![License](https://img.shields.io/github/license/artemeon/mantis2github?style=for-the-badge)](https://packagist.org/packages/artemeon/mantis2github)

A small CLI tool to create a GitHub issue out of a Mantis issue.
Creates cross-references, so links the GitHub issue to mantis and vice versa.

## Installation

```shell
composer global require artemeon/mantis2github
```

## Configuration

When you first installed the package, call the `configure` command. You only need to do this once.

```shell
mantis2github configure
```

The command will direct you through the installation process.

### Quick setup

If you have used a previous version of this package and already have a `config.yaml` file, you can skip the configuration by running:

```shell
mantis2github configure path/to/config.yaml
```

## Usage

```shell
mantis2github [command]
```

### Available Commands

| Command                      | Description                                                       |
|------------------------------|-------------------------------------------------------------------|
| [`sync`](#sync)              | Create a GitHub issue from a Mantis issue                         |
| [`read:github`](#readgithub) | Read details of a GitHub issue                                    |
| [`read:mantis`](#readmantis) | Read details of a Mantis issue                                    |
| [`issues:list`](#issueslist) | Get a list of Mantis Tickets with their associated GitHub Issues. |

#### `sync`

Create a GitHub issue from a list of Mantis issues.

```shell
mantis2github sync <ids>...
```

##### Arguments

| Argument | required | Description      |
|----------|----------|------------------|
| `ids`    | `true`   | Mantis issue ids |

##### Examples

###### Sync a single issue

```shell
mantis2github sync 123
```

###### Sync multiple issues

```shell
mantis2github sync 123 456 789
```

#### `read:github`

Read details of a GitHub issue.

```shell
mantis2github read:github <id>
```

##### Arguments

| Argument | required | Description     |
|----------|----------|-----------------|
| `id`     | `true`   | GitHub issue id |

#### `read:mantis`

Read details of a Mantis issue.

```shell
mantis2github read:mantis <id>
```

##### Arguments

| Argument | required | Description     |
|----------|----------|-----------------|
| `id`     | `true`   | Mantis issue id |

#### `issues:list`

Get a list of Mantis Tickets with their associated GitHub Issues.

```shell
mantis2github issues:list [--output=html]
```

##### Options

| Option   | Possible values | Description   |
|----------|-----------------|---------------|
| `output` | `html`          | Output Format |

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
