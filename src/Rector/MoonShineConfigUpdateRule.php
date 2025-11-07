<?php

namespace Warete\MoonshineUpgrade\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class MoonShineConfigUpdateRule extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Updates MoonShine config: adds palette key after layout and converts auth.middleware to array',
            []
        );
    }

    public function getNodeTypes(): array
    {
        return [Return_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Return_) {
            return null;
        }

        $filePath = $this->file->getFilePath();
        if (! str_ends_with($filePath, '/config/moonshine.php')) {
            return null;
        }

        if (! $node->expr instanceof Array_) {
            return null;
        }

        $array = $node->expr;
        $changed = false;

        $paletteAdded = $this->addPaletteAfterLayout($array);
        if ($paletteAdded) {
            $changed = true;
        }

        $middlewareConverted = $this->convertAuthMiddlewareToArray($array);
        if ($middlewareConverted) {
            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function addPaletteAfterLayout(Array_ $array): bool
    {
        $layoutIndex = null;
        $hasPalette = false;

        foreach ($array->items as $index => $item) {
            if (! $item instanceof ArrayItem || ! $item->key instanceof String_) {
                continue;
            }

            if ($item->key->value === 'layout') {
                $layoutIndex = $index;
            }

            if ($item->key->value === 'palette') {
                $hasPalette = true;
            }
        }

        if ($hasPalette || $layoutIndex === null) {
            return false;
        }

        $paletteItem = new ArrayItem(
            new ClassConstFetch(
                new FullyQualified('MoonShine\ColorManager\Palettes\PurplePalette'),
                'class'
            ),
            new String_('palette')
        );

        array_splice($array->items, $layoutIndex + 1, 0, [$paletteItem]);

        return true;
    }

    private function convertAuthMiddlewareToArray(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if (! $item instanceof ArrayItem || ! $item->key instanceof String_) {
                continue;
            }

            if ($item->key->value !== 'auth') {
                continue;
            }

            if (! $item->value instanceof Array_) {
                continue;
            }

            foreach ($item->value->items as $authItem) {
                if (! $authItem instanceof ArrayItem || ! $authItem->key instanceof String_) {
                    continue;
                }

                if ($authItem->key->value !== 'middleware') {
                    continue;
                }

                if ($authItem->value instanceof Array_) {
                    return false;
                }

                $authItem->value = new Array_([
                    new ArrayItem($authItem->value),
                ]);

                return true;
            }
        }

        return false;
    }
}
