<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Rector\RuleDocGenerator\ValueObject\RuleDefinition;

final class ImportShortClassReferencesRector extends AbstractRector
{
    /**
     * @var array<string> List of parent classes/interfaces to check against
     */
    private array $parentClasses = [
        'MoonShine\\Laravel\\Resources\\ModelResource',
        'MoonShine\\Laravel\\Pages\\Page',
        'MoonShine\\Crud\\Resources\\CrudResource',
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeTypes(): array
    {
        return [Namespace_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Namespace_) {
            return null;
        }

        $currentClass = $this->findClassInNamespace($node);
        if ($currentClass === null) {
            return null;
        }

        if ($currentClass->name === null) {
            return null;
        }

        $currentClassName = $currentClass->name->toString();
        $currentNamespace = $node->name ? $node->name->toString() : '';
        $fullCurrentClassName = $currentNamespace ? $currentNamespace . '\\' . $currentClassName : $currentClassName;

        if (! $this->isTargetClass($fullCurrentClassName)) {
            return null;
        }

        $classesToImport = [];

        $this->traverseNodesWithCallable($node->stmts, function (Node $subNode) use ($currentNamespace, &$classesToImport) {
            if (! $subNode instanceof ClassConstFetch) {
                return null;
            }

            if (! $subNode->class instanceof Name) {
                return null;
            }

            $constName = $this->getName($subNode->name);
            if ($constName !== 'class') {
                return null;
            }

            $className = $subNode->class->toString();

            if (in_array(strtolower($className), ['self', 'static', 'parent'])) {
                return null;
            }

            $classNamespace = '';
            $shortClassName = $className;

            if (str_contains($className, '\\')) {
                $parts = explode('\\', $className);
                $shortClassName = array_pop($parts);
                $classNamespace = implode('\\', $parts);
            }

            if ($classNamespace !== $currentNamespace && !empty($classNamespace)) {
                return null;
            }

            $possibleFqcn = empty($classNamespace) ? $currentNamespace . '\\' . $shortClassName : $className;

            if ($this->reflectionProvider->hasClass($possibleFqcn)) {
                if ($this->isTargetClass($possibleFqcn)) {
                    $classesToImport[$shortClassName] = $possibleFqcn;
                }
            }

            return null;
        });

        if (empty($classesToImport)) {
            return null;
        }

        $existingImports = [];
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $alias = $use->alias ? $use->alias->toString() : $use->name->getLast();
                    $existingImports[$alias] = $use->name->toString();
                }
            }
        }

        $changed = false;
        $newUseStatements = [];

        foreach ($classesToImport as $shortName => $fqcn) {
            if (isset($existingImports[$shortName])) {
                if ($existingImports[$shortName] !== ltrim($fqcn, '\\')) {
                    continue;
                }
                continue;
            }

            $newUseStatements[] = new Node\Stmt\Use_([
                new Node\Stmt\UseUse(
                    new Name(ltrim($fqcn, '\\'))
                )
            ]);

            $changed = true;
        }

        if ($changed) {
            $insertPosition = 0;
            $lastUsePosition = -1;

            foreach ($node->stmts as $i => $stmt) {
                if ($stmt instanceof Node\Stmt\Use_) {
                    $lastUsePosition = $i;
                }
            }

            if ($lastUsePosition >= 0) {
                $insertPosition = $lastUsePosition + 1;
            } else {
                $insertPosition = 0;
            }

            array_splice($node->stmts, $insertPosition, 0, $newUseStatements);
        }

        return $changed ? $node : null;
    }

    private function findClassInNamespace(Namespace_ $namespace): ?Class_
    {
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Class_) {
                return $stmt;
            }
        }

        return null;
    }

    private function isTargetClass(string $className): bool
    {
        $className = ltrim($className, '\\');

        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        if (empty($this->parentClasses)) {
            return true;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        foreach ($this->parentClasses as $parentClass) {
            if (! $this->reflectionProvider->hasClass($parentClass)) {
                continue;
            }

            $parentReflection = $this->reflectionProvider->getClass($parentClass);

            if (
                $classReflection->getName() === $parentReflection->getName() ||
                $classReflection->isSubclassOf($parentReflection->getName())
            ) {
                return true;
            }

            foreach ($classReflection->getInterfaces() as $interface) {
                if ($interface->getName() === $parentReflection->getName()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Import short class references for classes from the same namespace that extend/implement specified parent classes',
            []
        );
    }
}
