<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

class MantisAttachment
{
    public function __construct(
        private readonly int $id,
        private readonly string $filename,
        private readonly int $size,
        private readonly ?string $contentType,
        private readonly ?string $contentBase64 = null,
    ) {
    }

    final public function getId(): int
    {
        return $this->id;
    }

    final public function getFilename(): string
    {
        return $this->filename;
    }

    final public function getSize(): int
    {
        return $this->size;
    }

    final public function getContentType(): ?string
    {
        return $this->contentType;
    }

    final public function getContentBase64(): ?string
    {
        return $this->contentBase64;
    }
}
