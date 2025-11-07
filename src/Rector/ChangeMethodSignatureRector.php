<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Rector;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\RuleDocGenerator\ValueObject\RuleDefinition;

final class ChangeMethodSignatureRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<string, array<string, array{params?: array<int, string>, return?: string}>>
     */
    private array $signatureChanges = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * @param array<int, array{class: string, method: string, params?: array<int, string>, return?: string}> $configuration
     */
    public function configure(array $configuration): void
    {
        foreach ($configuration as $rule) {
            if (!is_array($rule) || !isset($rule['class'], $rule['method'])) {
                continue;
            }

            $class = ltrim((string) $rule['class'], '\\');
            $method = (string) $rule['method'];

            if (!isset($this->signatureChanges[$class])) {
                $this->signatureChanges[$class] = [];
            }

            $signature = [];

            if (isset($rule['params']) && is_array($rule['params'])) {
                $signature['params'] = [];
                foreach ($rule['params'] as $index => $type) {
                    $signature['params'][(int) $index] = ltrim((string) $type, '\\');
                }
            }

            if (isset($rule['return'])) {
                $signature['return'] = ltrim((string) $rule['return'], '\\');
            }

            if (!empty($signature)) {
                $this->signatureChanges[$class][$method] = $signature;
            }
        }
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        $className = $this->getName($node);
        if ($className === null) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $changed = false;

        foreach ($this->signatureChanges as $baseClass => $methods) {
            if (!$this->classUsesTraitOrExtendsClass($classReflection, $baseClass)) {
                continue;
            }

            foreach ($node->getMethods() as $method) {
                $methodName = $this->getName($method);
                if ($methodName === null || !isset($methods[$methodName])) {
                    continue;
                }

                $signature = $methods[$methodName];
                if ($this->updateMethodSignature($method, $signature)) {
                    $changed = true;
                }
            }
        }

        return $changed ? $node : null;
    }

    private function classUsesTraitOrExtendsClass(ClassReflection $classReflection, string $baseClassOrTrait): bool
    {
        $baseClassOrTrait = ltrim($baseClassOrTrait, '\\');

        if ($classReflection->getName() === $baseClassOrTrait) {
            return true;
        }

        if (!$this->reflectionProvider->hasClass($baseClassOrTrait)) {
            return false;
        }

        $baseReflection = $this->reflectionProvider->getClass($baseClassOrTrait);

        if ($baseReflection->isTrait()) {
            return $this->classUsesTraitRecursively($classReflection, $baseClassOrTrait);
        }

        return $classReflection->isSubclassOf($baseReflection->getName());
    }

    private function classUsesTraitRecursively(ClassReflection $classReflection, string $traitName): bool
    {
        $traitName = ltrim($traitName, '\\');

        foreach ($classReflection->getTraits() as $trait) {
            if ($trait->getName() === $traitName) {
                return true;
            }

            if ($this->classUsesTraitRecursively($trait, $traitName)) {
                return true;
            }
        }

        $parentClass = $classReflection->getParentClass();
        if ($parentClass !== null && $parentClass !== false) {
            return $this->classUsesTraitRecursively($parentClass, $traitName);
        }

        return false;
    }

    /**
     * @param array{params?: array<int, string>, return?: string} $signature
     */
    private function updateMethodSignature(ClassMethod $method, array $signature): bool
    {
        $changed = false;

        if (isset($signature['params'])) {
            foreach ($signature['params'] as $index => $newType) {
                if (!isset($method->params[$index])) {
                    continue;
                }

                $param = $method->params[$index];
                $newTypeNode = $this->createTypeNode($newType);

                if ($newTypeNode !== null) {
                    $param->type = $newTypeNode;
                    $changed = true;
                }
            }
        }

        if (isset($signature['return'])) {
            $newReturnType = $this->createTypeNode($signature['return']);

            if ($newReturnType !== null) {
                $method->returnType = $newReturnType;
                $changed = true;
            }
        }

        return $changed;
    }

    private function createTypeNode(string $type): Identifier|Name|ComplexType|null
    {
        if (str_starts_with($type, '?')) {
            $innerType = $this->createTypeNode(substr($type, 1));
            return $innerType !== null ? new NullableType($innerType) : null;
        }

        if (str_contains($type, '|')) {
            $types = array_map('trim', explode('|', $type));
            $typeNodes = [];
            foreach ($types as $t) {
                $node = $this->createTypeNode($t);
                if ($node !== null) {
                    $typeNodes[] = $node;
                }
            }
            return !empty($typeNodes) ? new UnionType($typeNodes) : null;
        }

        $scalarTypes = ['string', 'int', 'bool', 'float', 'array', 'object', 'mixed', 'void', 'never', 'null', 'true', 'false'];
        if (in_array(strtolower($type), $scalarTypes, true)) {
            return new Identifier(strtolower($type));
        }

        return new FullyQualified($type);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change method parameter and return types for specified classes and their descendants',
            []
        );
    }
}
