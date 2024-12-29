<?php

declare(strict_types=1);

namespace Darken\Builder;

use Darken\Attributes\Hooks\ConstructorParamHook;
use Darken\Attributes\Hooks\InjectHook;
use Darken\Attributes\Hooks\MiddlewareHook;
use Darken\Attributes\Hooks\QueryParamHook;
use Darken\Attributes\Hooks\RouteParamHook;
use Darken\Attributes\Hooks\SlotHook;
use Darken\Builder\Compiler\DataExtractorVisitor;
use Darken\Builder\Compiler\GlobalVisitor;
use Darken\Builder\Compiler\UseStatementCollector;
use Darken\Builder\Hooks\AttributeHookInterface;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use Throwable;

class CodeCompiler
{
    private array $hooks = [];

    public function __construct()
    {
        $this->hooks[] = new ConstructorParamHook();
        $this->hooks[] = new InjectHook();
        $this->hooks[] = new RouteParamHook();
        $this->hooks[] = new SlotHook();
        $this->hooks[] = new MiddlewareHook();
        $this->hooks[] = new QueryParamHook();
    }

    public function registerHook(AttributeHookInterface $hook): void
    {
        $this->hooks[] = $hook;
    }

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
            $data = new DataExtractorVisitor($use, $this->hooks);
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
            $errorMessage = sprintf(
                "Failed to compile file: %s\n" .
                "Error: %s\n" .
                'File: %s:%d',
                $file->filePath,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            throw new RuntimeException($errorMessage, 0, $e);
        }
    }
}
