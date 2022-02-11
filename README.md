

# Mantis 2 Github Connector

A small CLI tool used to create a GitHub Isse out of a mantis issue.
Creates cross-references, so links the github issue to mantis and vice versa.

## Prerequirement
Copy the `config.sample.yaml` to `config.yaml` and change your props as api tokens.

### Mantis:
- Go to User Settings
- Go to **Api-Token** tab

### Github
- Go to https://github.com/settings/tokens
- Click **Generate new token**
- Enter Note & check repo in **select scopes**

Enter credentials into `config.yaml`

## Installation

    composer install
    php cli.php

## CLI Commands

    mantis2github  creates a github issue from a mantis issue
    github-read    read details of a github issue
    mantis-read    read details of a mantis issue
