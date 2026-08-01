<?php

namespace App\GraphQL\Concerns;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ResolveInfo;

trait ComplexityAnalyzer
{
    protected function assertQueryWithinLimits(ResolveInfo $resolveInfo): void
    {
        $maxDepth = (int) config('graphql.security.query_max_depth', 8);
        $maxComplexity = (int) config('graphql.security.query_max_complexity', 120);

        $depth = $this->nodeDepth($resolveInfo->fieldNodes[0] ?? null);
        if ($depth > $maxDepth) {
            throw new Error("GraphQL query depth ({$depth}) exceeds maximum allowed depth ({$maxDepth}).");
        }

        $complexity = $this->nodeComplexity($resolveInfo->fieldNodes[0] ?? null);
        if ($complexity > $maxComplexity) {
            throw new Error("GraphQL query complexity ({$complexity}) exceeds maximum allowed complexity ({$maxComplexity}).");
        }
    }

    private function nodeDepth(?FieldNode $node): int
    {
        if (! $node || ! $node->selectionSet) {
            return 1;
        }

        $maxChildDepth = 0;
        foreach ($node->selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $maxChildDepth = max($maxChildDepth, $this->nodeDepth($selection));
            }
        }

        return $maxChildDepth + 1;
    }

    private function nodeComplexity(?FieldNode $node): int
    {
        if (! $node || ! $node->selectionSet) {
            return 1;
        }

        $complexity = 1;
        foreach ($node->selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $complexity += $this->nodeComplexity($selection);
            }
        }

        return $complexity;
    }
}
