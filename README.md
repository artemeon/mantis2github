# Mantis 2 Github Connector

A small CLI tool used to create a GitHub Isse out of a mantis issue.
Creates cross-references, so links the github issue to mantis and vice versa.

## Installation

```shell
composer global require artemeon/mantis2github
```

## (Initial) Set up

When you first installed the package, call the `setup` command. You only need to do this once.

```shell
mantis2github setup
```

You will be directed through the installation process.

### Mantis:
- Go to User Settings
- Go to **Api-Token** tab

### Github
- Go to https://github.com/settings/tokens
- Click **Generate new token**
- Enter Note & check `repo` in **select scopes**

## Usage

```shell
mantis2github [command]
```

### Available Commands

| Command       | Description                               |
|---------------|-------------------------------------------|
| `sync`        | Create a GitHub Issue from a Mantis Issue |
| `github-read` | Read details of a GitHub Issue            |
| `mantis-read` | Read details of a Mantis Issue            |
