<?php

namespace Warete\MoonshineUpgrade\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;

final class ReorderConfiguredMethodArgsRector extends \Rector\Rector\AbstractRector implements ConfigurableRectorInterface
{
    /**
     * [
     *   [
     *     'class'      => 'Vendor\\Pkg\\Foo',
     *     'method'     => 'bar',
     *     'call_types' => 'both'|'static'|'instance', // both by default
     *     // one of:
     *     'swap'       => [0, 1],
     *     'order'      => [1, 0, 2],
     *   ],
     *   ...
     * ]
     * @var array<int, array{
     *   class: string,
     *   method: string,
     *   call_types?: 'both'|'static'|'instance',
     *   swap?: array{0:int,1:int},
     *   order?: array<int,int>
     * }>
     */
    private array $rules = [];

    /**
     * @param array<int, array<string, mixed>> $configuration
     */
    public function configure(array $configuration): void
    {
        $normalized = [];
        foreach ($configuration as $item) {
            $class  = $item['class']  ?? ($item[0] ?? null);
            $method = $item['method'] ?? ($item[1] ?? null);
            $callTypes = $item['call_types'] ?? ($item[2] ?? 'both');
            $swap  = $item['swap']  ?? null;
            $order = $item['order'] ?? null;

            if (!$class || !$method) {
                continue;
            }
            $class = ltrim((string) $class, '\\');
            $method = (string) $method;
            $callTypes = \in_array($callTypes, ['both','static','instance'], true) ? $callTypes : 'both';

            $rule = [
                'class'      => $class,
                'method'     => $method,
                'call_types' => $callTypes,
            ];

            if (\is_array($swap) && \count($swap) === 2) {
                $rule['swap'] = [ (int) $swap[0], (int) $swap[1] ];
            } elseif (\is_array($order) && $order !== []) {
                $rule['order'] = array_map('intval', $order);
            } else {
                continue;
            }

            $normalized[] = $rule;
        }

        $this->rules = $normalized;
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }

    public function refactor(Node $node): Node|int|null
    {
        foreach ($this->rules as $rule) {
            $callTypes = $rule['call_types'];

            if ($node instanceof StaticCall && $callTypes !== 'instance') {
                $methodName = $this->getName($node->name);
                if ($methodName === null || $methodName !== $rule['method']) {
                    continue;
                }
                if (! $this->isName($node->class, $rule['class'])) {
                    continue;
                }
                if ($this->hasNamedOrUnpackedArgs($node->args)) {
                    continue;
                }

                $newArgs = $this->reorderedArgs($node->args, $rule);
                if ($newArgs !== null) {
                    $node->args = $newArgs;
                    return $node;
                }
            }

            if ($node instanceof MethodCall && $callTypes !== 'static') {
                $methodName = $this->getName($node->name);
                if ($methodName === null || $methodName !== $rule['method']) {
                    continue;
                }
                if (! $this->isObjectType($node->var, new ObjectType($rule['class']))) {
                    continue;
                }
                if ($this->hasNamedOrUnpackedArgs($node->args)) {
                    continue;
                }

                $newArgs = $this->reorderedArgs($node->args, $rule);
                if ($newArgs !== null) {
                    $node->args = $newArgs;
                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * @param Arg[] $args
     */
    private function hasNamedOrUnpackedArgs(array $args): bool
    {
        foreach ($args as $arg) {
            if ($arg->name !== null || $arg->unpack) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Arg[] $args
     * @param array{swap?: array{0:int,1:int}, order?: array<int,int>} $rule
     * @return Arg[]|null
     */
    private function reorderedArgs(array $args, array $rule): ?array
    {
        if (isset($rule['swap'])) {
            [$i, $j] = $rule['swap'];

            if (!isset($args[$i]) || !isset($args[$j])) {
                return null;
            }
            if ($i === $j) {
                return null;
            }

            $new = $args;
            $tmp = $new[$i];
            $new[$i] = $new[$j];
            $new[$j] = $tmp;
            return $new;
        }

        if (isset($rule['order'])) {
            $order = $rule['order'];
            if ($order === []) {
                return null;
            }
            $new = [];
            foreach ($order as $srcIdx) {
                if (isset($args[$srcIdx])) {
                    $new[] = $args[$srcIdx];
                }
            }
            if ($this->argsEqual($args, $new)) {
                return null;
            }
            return $new;
        }

        return null;
    }

    /**
     * @param Arg[] $a
     * @param Arg[] $b
     */
    private function argsEqual(array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }
        foreach ($a as $i => $arg) {
            if (!isset($b[$i]) || $b[$i] !== $arg) {
                return false;
            }
        }
        return true;
    }

    public function getRuleDefinition(): \Rector\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Rector\RuleDocGenerator\ValueObject\RuleDefinition(
            'Reorder/swap arguments for configured method or static calls',
            []
        );
    }
}
