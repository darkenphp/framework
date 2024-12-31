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
        $this->registerHook(new ConstructorParamHook());
        $this->registerHook(new InjectHook());
        $this->registerHook(new RouteParamHook());
        $this->registerHook(new SlotHook());
        $this->registerHook(new MiddlewareHook());
        $this->registerHook(new QueryParamHook());
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
            $code = self::doNotModifyHint(true). $prettyPrinter->prettyPrintFile($ast);

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

    public static function doNotModifyHint(bool $addRuntime): string
    {
        $hint = '<?php /**';
        if ($addRuntime) {
            $hint .= ' @var \Darken\Code\Runtime $this';
        }
        $hint .= ' Do not edit this file. It is auto-generated and changes will be overwritten during the next compile. */ ?>';
        return $hint;
    }
}
