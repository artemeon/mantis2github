# Mantis 2 GitHub Connector

[![Packagist Version](https://img.shields.io/packagist/v/artemeon/mantis2github?style=for-the-badge)](https://packagist.org/packages/artemeon/mantis2github)
![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg?style=for-the-badge)
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

## MCP Server

This package ships an [MCP](https://modelcontextprotocol.io) server (`mantis-mcp`) that exposes
Mantis ticket details to LLM-powered clients. It reuses the same `config.yaml` as the CLI, so
running `mantis2github configure` once is enough to make both available.

The server speaks the stdio transport and exposes three tools:

| Tool                         | Description                                                                                       | Input                                                          |
|------------------------------|---------------------------------------------------------------------------------------------------|----------------------------------------------------------------|
| `mantis-issue-details`       | Read ticket details (incl. attachment metadata)                                                   | `{ "id": <int> }` or `{ "url": "<mantis-view-url>" }`          |
| `mantis-issue-attachments`   | List attachment metadata (id, filename, size, content type) for a ticket                          | `{ "id": <int> }` or `{ "url": "<mantis-view-url>" }`          |
| `mantis-attachment`          | Fetch the bytes of a single attachment. Images come back as inline image content, text as text, everything else as an embedded resource. Files over 10 MB are rejected. | `{ "issue_id": <int>, "file_id": <int> }`                      |

Textual responses are encoded as [TOON](https://github.com/helgesverre/toon) to keep the LLM context compact.

### Example host configuration

For Claude Desktop (`claude_desktop_config.json`) or any other MCP host that supports stdio servers:

```json
{
  "mcpServers": {
    "mantis": {
      "command": "mantis-mcp"
    }
  }
}
```

If `mantis-mcp` is not on your `$PATH`, point `command` at the absolute path inside your Composer
`vendor/bin/` (or `~/.composer/vendor/bin/` for a global install).

### Add to Claude Code via CLI

```shell
claude mcp add --transport stdio mantis --scope user -- mantis-mcp
```

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
