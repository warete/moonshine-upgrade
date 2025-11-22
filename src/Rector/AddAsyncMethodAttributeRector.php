<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Rector;

use Illuminate\Support\Arr;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\Rector\AbstractRector;
use Rector\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddAsyncMethodAttributeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private ?string $attributeFqcn = null;

    /** @var string[] */
    private ?array $allowedBuilderClasses = null;

    /** @var string[] */
    private $asyncMethodSelectors;

    private const CRUD_REQUEST_CONTRACT = 'MoonShine\\Contracts\\Core\\DependencyInjection\\CrudRequestContract';
    private const JSON_RESPONSE_FQCN = 'MoonShine\\Crud\\JsonResponse';

    public function __construct(NodeTypeResolver $nodeTypeResolver)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
    }

    public function configure(array $configuration): void
    {
        $attributeFqcn = Arr::get($configuration, 'attributeFqcn');
        $allowedBuilderClasses = Arr::get($configuration, 'allowedBuilderClasses');
        $asyncMethodSelectors = Arr::get($configuration, 'asyncMethodSelectors');
        if (! $asyncMethodSelectors) {
            $asyncMethodSelectors = ['asyncMethod', 'method', 'onChangeMethod', 'getAsyncMethodUrl'];
        }
        $this->attributeFqcn = ltrim((string) $attributeFqcn, '\\');
        $this->allowedBuilderClasses = array_map(static fn (string $c): string => ltrim($c, '\\'), $allowedBuilderClasses);
        $this->asyncMethodSelectors = $asyncMethodSelectors;
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Class_) {
            return null;
        }

        $asyncNames = $this->collectAsyncMethodNames($node);

        $changed = false;

        foreach ($node->getMethods() as $classMethod) {
            $name = $classMethod->name->toString();

            $isAsyncByUsage = in_array($name, $asyncNames, true);
            $isAsyncBySignature = $this->isAsyncBySignature($classMethod);

            if (! $isAsyncByUsage && ! $isAsyncBySignature) {
                continue;
            }

            if ($this->hasAttribute($classMethod, $this->attributeFqcn)) {
                continue;
            }

            $this->addAttribute($classMethod, $this->attributeFqcn);
            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function isAsyncBySignature(ClassMethod $method): bool
    {
        foreach ($method->getParams() as $param) {
            $type = $param->type;
            if ($type === null) {
                continue;
            }
            if ($this->typeMatches($type, self::CRUD_REQUEST_CONTRACT)) {
                return true;
            }
        }

        $return = $method->returnType;

        return $return instanceof Node && $this->typeMatches($return, self::JSON_RESPONSE_FQCN);
    }

    private function typeMatches(Node $typeNode, string $fqcn): bool
    {
        if ($typeNode instanceof UnionType) {
            foreach ($typeNode->types as $t) {
                if ($this->typeMatches($t, $fqcn)) {
                    return true;
                }
            }

            return false;
        }

        if ($typeNode instanceof NullableType) {
            return $this->typeMatches($typeNode->type, $fqcn);
        }

        if ($typeNode instanceof Identifier) {
            return false;
        }

        return $this->isName($typeNode, ltrim($fqcn, '\\'))
            || $this->isName($typeNode, '\\' . ltrim($fqcn, '\\'));
    }

    private function collectAsyncMethodNames(Class_ $class): array
    {
        $found = [];

        $this->traverseNodesWithCallable($class, function (Node $n) use (&$found): ?Node {
            if (! $n instanceof MethodCall) {
                return null;
            }

            $methodName = $this->getName($n->name);
            if ($methodName === null || ! in_array($methodName, $this->asyncMethodSelectors, true)) {
                return null;
            }

            $arg = $n->args[0] ?? null;
            if (! $arg instanceof Arg || ! $arg->value instanceof String_) {
                return null;
            }

            $candidate = $arg->value->value;
            if ($candidate === '') {
                return null;
            }

            if (! $this->isOnAllowedBuilderChain($n)) {
                return null;
            }

            if (! $this->isOnAllowedBuilderChain($n) && ! $this->isRouterEndpointsAsyncCallFlexible($n)) {
                return null;
            }

            $found[] = $candidate;

            return null;
        });

        return array_values(array_unique($found));
    }

    private function isOnAllowedBuilderChain(MethodCall $call): bool
    {
        $expr = $call->var;

        if ($expr instanceof StaticCall && $this->isAllowedStaticMake($expr)) {
            return true;
        }

        if ($this->nodeTypeResolver->isObjectType($expr, $this->makeUnionObjectType())) {
            return true;
        }

        $cursor = $expr;
        $hops = 0;
        while ($cursor instanceof MethodCall && $hops < 3) {
            $inner = $cursor->var;

            if ($inner instanceof StaticCall && $this->isAllowedStaticMake($inner)) {
                return true;
            }

            if ($this->nodeTypeResolver->isObjectType($inner, $this->makeUnionObjectType())) {
                return true;
            }

            $cursor = $inner;
            $hops++;
        }

        return false;
    }

    private function isAllowedStaticMake(StaticCall $staticCall): bool
    {
        $name = $this->getName($staticCall->name);
        if ($name !== 'make') {
            return false;
        }
        $className = $this->getName($staticCall->class);
        if ($className === null) {
            return false;
        }
        foreach ($this->allowedBuilderClasses as $allowed) {
            if ($className === $allowed || is_a($className, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    private function makeUnionObjectType(): ObjectType
    {
        return new ObjectType($this->allowedBuilderClasses[0] ?? 'stdClass');
    }

    private function hasAttribute(ClassMethod $method, string $attributeFqcn): bool
    {
        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === ltrim($attributeFqcn, '\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addAttribute(ClassMethod $method, string $attributeFqcn): void
    {
        $attr = new Attribute(new FullyQualified($attributeFqcn), []);
        $group = new AttributeGroup([$attr]);
        array_unshift($method->attrGroups, $group);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add #[AsyncMethod] to methods referenced via asyncMethod()/method() or having CrudRequestContract param / JsonResponse return type',
            []
        );
    }

    private function isRouterEndpointsAsyncCallFlexible(MethodCall $call): bool
    {
        $m = $this->getName($call->name);
        if ($m !== 'method') {
            return false;
        }

        $arg0 = $call->args[0] ?? null;
        if (! $arg0 instanceof Arg || ! $arg0->value instanceof String_) {
            return false;
        }

        $node = $call->var;

        $seenGetEndpoints = false;
        $seenGetRouter = false;

        for ($hop = 0; $hop < 12 && $node instanceof Node; $hop++) {
            if ($node instanceof MethodCall) {
                $name = $this->getName($node->name);
                if ($name === 'getEndpoints' || $name === 'endpoints') {
                    $seenGetEndpoints = true;
                } elseif ($name === 'getRouter' || $name === 'router') {
                    $seenGetRouter = true;
                }
                $node = $node->var;

                continue;
            }

            if ($node instanceof PropertyFetch) {
                $node = $node->var;

                continue;
            }

            if ($node instanceof Variable) {
                $isThis = $this->getName($node) === 'this';

                return $isThis && $seenGetEndpoints && $seenGetRouter;
            }

            break;
        }

        return false;
    }
}
