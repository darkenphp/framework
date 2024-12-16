<?php

declare(strict_types=1);

namespace Darken\Builder\Compiler;

use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class UseStatementCollector extends NodeVisitorAbstract
{
    private $useStatements = [];

    public function enterNode(Node $node)
    {
        // Collect use statements within a namespace
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $alias = $use->alias ? $use->alias->toString() : $use->name->getLast();
                $this->useStatements[$alias] = '\\' . $use->name->toString();
            }

        }

        // Collect group use statements (e.g., use Foo\{Bar, Baz as Qux};)
        if ($node instanceof GroupUse) {
            $prefix = '\\' . $node->prefix->toString();
            foreach ($node->uses as $use) {
                $alias = $use->alias ? $use->alias->toString() : $use->name->getLast();
                $fullName = $prefix . '\\' . $use->name->toString();
                $this->useStatements[$alias] = $fullName;
            }
        }
    }

    public function getUseStatements(): array
    {
        return $this->useStatements;
    }
}
