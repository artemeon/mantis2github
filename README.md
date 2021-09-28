

# Mantis 2 Github Connector

A small CLI tool used to create a GitHub Isse out of a mantis issue.
Creates cross-references, so links the github issue to mantis and vice versa.

## Installation

    composer install
    php cli.php

Copy the `config.sample.yaml` to `config.yaml` and change your props as api tokens.

## CLI Commands

    mantis2github  creates a github issue from a mantis issue
    github-read    read details of a mantis issue
    mantis-read    read details of a mantis issue
