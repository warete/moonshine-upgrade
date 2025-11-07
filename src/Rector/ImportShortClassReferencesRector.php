<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Rector;

use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\Resources\ModelResource;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Rector\RuleDocGenerator\ValueObject\RuleDefinition;

final class ImportShortClassReferencesRector extends AbstractRector
{
    /**
     * @var array<string> List of parent classes/interfaces to check against
     */
    private array $parentClasses = [
        ModelResource::class,
        Page::class,
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
        if (! $currentClass instanceof Class_) {
            return null;
        }

        if (! $currentClass->name instanceof Identifier) {
            return null;
        }

        $currentClassName = $currentClass->name->toString();
        $currentNamespace = $node->name ? $node->name->toString() : '';
        $fullCurrentClassName = $currentNamespace ? $currentNamespace . '\\' . $currentClassName : $currentClassName;

        if (! $this->isTargetClass($fullCurrentClassName)) {
            return null;
        }

        $classesToImport = [];

        $this->traverseNodesWithCallable($node->stmts, function (Node $subNode) use ($currentNamespace, &$classesToImport): null {
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

            if ($classNamespace !== $currentNamespace && ($classNamespace !== '' && $classNamespace !== '0')) {
                return null;
            }

            $possibleFqcn = $classNamespace === '' || $classNamespace === '0' ? $currentNamespace . '\\' . $shortClassName : $className;

            if ($this->reflectionProvider->hasClass($possibleFqcn) && $this->isTargetClass($possibleFqcn)) {
                $classesToImport[$shortClassName] = $possibleFqcn;
            }

            return null;
        });

        if ($classesToImport === []) {
            return null;
        }

        $existingImports = [];
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Use_) {
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

            $newUseStatements[] = new Use_([
                new UseUse(
                    new Name(ltrim($fqcn, '\\'))
                ),
            ]);

            $changed = true;
        }

        if ($changed) {
            $insertPosition = 0;
            $lastUsePosition = -1;

            foreach ($node->stmts as $i => $stmt) {
                if ($stmt instanceof Use_) {
                    $lastUsePosition = $i;
                }
            }

            $insertPosition = $lastUsePosition >= 0 ? $lastUsePosition + 1 : 0;

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

        if ($this->parentClasses === []) {
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
