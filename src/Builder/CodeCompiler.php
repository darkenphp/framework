<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Builder\Compiler\GlobalVisitor;
use Darken\Builder\Compiler\UseStatementCollector;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class CodeCompiler
{
    public function compile(InputFile $file): CodeCompilerOutput
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($file->getContent());
        $traverser = new NodeTraverser();

        // collect use statements
        $use = new UseStatementCollector();
        $traverser->addVisitor($use);
        $traverser->traverse($ast);

        $darkenVisitor = new GlobalVisitor($use->getUseStatements());
        $traverser->addVisitor($darkenVisitor);
        $ast = $traverser->traverse($ast);

        // Pretty print the modified AST
        $prettyPrinter = new Standard();
        $code = '<?php /** @var \Darken\Code\Runtime $this */ ?>' . $prettyPrinter->prettyPrintFile($ast);

        return new CodeCompilerOutput($code, $darkenVisitor->meta);
    }
}
