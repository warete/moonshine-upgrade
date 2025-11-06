<?php

namespace Warete\MoonshineUpgrade\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitor;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

final class RemoveConfiguredMethodPairsRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<string, array<string, true>>  class => set(method) */
    private array $targets = [];

    /**
     * @param array<int, array{0?:string,1?:string,class?:string,method?:string}> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->targets = [];

        foreach ($configuration as $item) {
            $class = $item['class'] ?? ($item[0] ?? null);
            $method = $item['method'] ?? ($item[1] ?? null);

            if (! $class || ! $method) {
                continue;
            }

            $class = ltrim((string) $class, '\\');
            $method = (string) $method;

            $this->targets[$class][$method] = true;
        }
    }

    public function getNodeTypes(): array
    {
        return [StaticCall::class, MethodCall::class, Expression::class];
    }

    public function refactor(Node $node): Node|int|null
    {
        if ($node instanceof Expression) {
            $expr = $node->expr;

            if (
                ($expr instanceof MethodCall && $this->isTargetInstance($expr)) ||
                ($expr instanceof StaticCall && $this->isTargetStatic($this->getName($expr->class), $this->getName($expr->name)))
            ) {
                return NodeVisitor::REMOVE_NODE;
            }

            return null;
        }

        if ($node instanceof StaticCall) {
            $className = $this->getName($node->class);
            $methodName = $this->getName($node->name);
            if ($this->isTargetStatic($className, $methodName)) {
                return new ConstFetch(new Name('null'));
            }

            return null;
        }

        if ($node instanceof MethodCall) {
            if ($this->isTargetInstance($node)) {
                return $node->var;
            }

            return null;
        }

        return null;
    }

    private function isTargetStatic(?string $className, ?string $methodName): bool
    {
        if ($className === null || $methodName === null) {
            return false;
        }
        $className = ltrim($className, '\\');

        return isset($this->targets[$className][$methodName]);
    }

    private function isTargetInstance(MethodCall $call): bool
    {
        $methodName = $this->getName($call->name);
        if ($methodName === null) {
            return false;
        }

        foreach ($this->targets as $fqcn => $methods) {
            if (! isset($methods[$methodName])) {
                continue;
            }
            if ($this->isObjectType($call->var, new ObjectType($fqcn))) {
                return true;
            }
        }

        return false;
    }
}
