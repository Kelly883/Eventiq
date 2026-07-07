<?php

namespace App\Features\OfflineSync\Services;

class ConflictResolution
{
    public static function resolveLastWriteWins(
        ?int $expectedRevision,
        ?int $serverRevision,
        callable $apply
    ): array {
        // If client provided an expected revision, enforce it.
        if ($expectedRevision !== null && $serverRevision !== null && $serverRevision !== $expectedRevision) {
            return [
                'status' => 'conflict',
                'server_state' => ['revision' => $serverRevision],
            ];
        }

        $apply();

        return [
            'status' => 'applied',
        ];
    }
}

