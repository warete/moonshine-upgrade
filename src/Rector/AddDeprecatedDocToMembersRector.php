<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Rector;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddDeprecatedDocToMembersRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<string, array{methods?: array<string, string>, properties?: array<string, string>}> */
    private array $deprecationRules = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function configure(array $configuration): void
    {
        foreach ($configuration as $rule) {
            if (! is_array($rule) || ! isset($rule['class'])) {
                continue;
            }

            $class = ltrim((string) $rule['class'], '\\');

            if (! isset($this->deprecationRules[$class])) {
                $this->deprecationRules[$class] = [
                    'methods' => [],
                    'properties' => [],
                ];
            }

            if (isset($rule['methods']) && is_array($rule['methods'])) {
                $this->deprecationRules[$class]['methods'] = array_merge(
                    $this->deprecationRules[$class]['methods'],
                    $rule['methods']
                );
            }

            if (isset($rule['properties']) && is_array($rule['properties'])) {
                $this->deprecationRules[$class]['properties'] = array_merge(
                    $this->deprecationRules[$class]['properties'],
                    $rule['properties']
                );
            }

            if (isset($rule['method'], $rule['message'])) {
                $this->deprecationRules[$class]['methods'][$rule['method']] = $rule['message'];
            }

            if (isset($rule['property'], $rule['message'])) {
                $this->deprecationRules[$class]['properties'][$rule['property']] = $rule['message'];
            }
        }
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

        $className = $this->getName($node);
        if ($className === null) {
            return null;
        }

        $changed = false;

        foreach ($this->deprecationRules as $baseClass => $rules) {
            if (! $this->isClassOrSubclass($className, $baseClass)) {
                continue;
            }

            if (! empty($rules['properties'])) {
                foreach ($node->getProperties() as $property) {
                    $propertyName = $this->getName($property);
                    if ($propertyName === null) {
                        continue;
                    }

                    if (isset($rules['properties'][$propertyName]) && $this->addDeprecatedDoc($property, $rules['properties'][$propertyName])) {
                        $changed = true;
                    }
                }
            }

            if (! empty($rules['methods'])) {
                foreach ($node->getMethods() as $method) {
                    $methodName = $this->getName($method);
                    if ($methodName === null) {
                        continue;
                    }

                    if (isset($rules['methods'][$methodName]) && $this->addDeprecatedDoc($method, $rules['methods'][$methodName])) {
                        $changed = true;
                    }
                }
            }
        }

        return $changed ? $node : null;
    }

    /**
     * @param Property|ClassMethod $node
     */
    private function addDeprecatedDoc(Node $node, string $message): bool
    {
        $docComment = $node->getDocComment();

        if ($docComment instanceof Doc) {
            $text = $docComment->getText();
            if (str_contains($text, '@deprecated')) {
                return false;
            }

            $lines = explode("\n", $text);
            $lastLine = array_pop($lines);
            $lines[] = '     * @deprecated ' . $message;
            $lines[] = $lastLine;
            $newDoc = implode("\n", $lines);
        } else {
            $newDoc = "/**\n     * @deprecated {$message}\n     */";
        }

        $node->setDocComment(new Doc($newDoc));

        return true;
    }

    private function isClassOrSubclass(string $className, string $baseClass): bool
    {
        $className = ltrim($className, '\\');
        $baseClass = ltrim($baseClass, '\\');

        if ($className === $baseClass) {
            return true;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        if (! $this->reflectionProvider->hasClass($baseClass)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $baseClassReflection = $this->reflectionProvider->getClass($baseClass);

        return $classReflection->isSubclassOf($baseClassReflection->getName());
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add @deprecated PHPDoc to specified class members and their descendants',
            []
        );
    }
}
