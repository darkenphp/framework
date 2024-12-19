<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Builder\Compiler\GlobalVisitor;
use Darken\Builder\Compiler\UseStatementCollector;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use Throwable;

class CodeCompiler
{
    public function compile(InputFile $file): CodeCompilerOutput
    {
        try {
            $parser = (new ParserFactory())->createForNewestSupportedVersion();
            $ast = $parser->parse($file->getContent());

            // Initialize traverser and add all visitors
            $traverser = new NodeTraverser();

            // Visitor 1: Collect use statements
            $use = new UseStatementCollector();
            $traverser->addVisitor($use);

            // Visitor 2: Extract data based on use statements
            $data = new DataExtractorVisitor($use);
            $traverser->addVisitor($data);

            // Visitor 3: Apply global modifications
            $darkenVisitor = new GlobalVisitor($use, $data);
            $traverser->addVisitor($darkenVisitor);

            // Traverse the AST once with all visitors
            $ast = $traverser->traverse($ast);

            // Initialize pretty printer
            $prettyPrinter = new Standard();
            $code = '<?php /** @var \Darken\Code\Runtime $this */ ?>' . $prettyPrinter->prettyPrintFile($ast);

            return new CodeCompilerOutput($code, $data);
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to compile ' . $file->filePath . ': ' . $e->getMessage(), 0, $e);
        }
    }
}
