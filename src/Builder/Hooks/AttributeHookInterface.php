<?php

declare(strict_types=1);

namespace Darken\Builder\Hooks;

use Darken\Builder\Compiler\Extractor\AttributeExtractorInterface;
use Darken\Enum\HookedAttributeType;

interface AttributeHookInterface
{
    public function isValidAttribute(AttributeExtractorInterface $attribute): bool;

    public function attributeType(): HookedAttributeType;
}
