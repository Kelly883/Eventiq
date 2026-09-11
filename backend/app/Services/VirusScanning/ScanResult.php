<?php

namespace App\Services\VirusScanning;

readonly class ScanResult
{
    public function __construct(
        public bool $isClean,
        public ?string $threatName = null,
        public ?string $scannerEngine = null,
        public ?string $rawOutput = null,
    ) {}

    public static function clean(string $engine = 'basic-validation'): self
    {
        return new self(isClean: true, scannerEngine: $engine);
    }

    public static function infected(string $threatName, ?string $rawOutput = null): self
    {
        return new self(isClean: false, threatName: $threatName, rawOutput: $rawOutput);
    }

    public static function unavailable(string $reason): self
    {
        return new self(
            isClean: true,
            scannerEngine: 'unavailable',
            rawOutput: $reason,
        );
    }
}
