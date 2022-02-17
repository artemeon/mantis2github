# Mantis 2 Github Connector

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

## Usage

```shell
mantis2github [command]
```

### Available Commands

| Command                      | Description                               |
|------------------------------|-------------------------------------------|
| [`sync`](#sync)              | Create a GitHub issue from a Mantis issue |
| [`read:github`](#readgithub) | Read details of a GitHub issue            |
| [`read:mantis`](#readmantis) | Read details of a Mantis issue            |

#### `sync`

Create a GitHub issue from a Mantis issue.

```shell
mantis2github sync [id]
```

##### Arguments

| Argument | required | Description     |
|----------|----------|-----------------|
| `id`     | `false`  | Mantis issue id |

#### `read:github`

Read details of a GitHub issue.

```shell
mantis2github read:github [id]
```

##### Arguments

| Argument | required | Description     |
|----------|----------|-----------------|
| `id`     | `false`  | GitHub issue id |

#### `read:mantis`

Read details of a Mantis issue.

```shell
mantis2github read:mantis [id]
```

##### Arguments

| Argument | required | Description     |
|----------|----------|-----------------|
| `id`     | `false`  | Mantis issue id |
