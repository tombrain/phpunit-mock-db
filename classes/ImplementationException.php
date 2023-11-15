<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use PHPUnit\Framework\InvalidArgumentException;

final class ImplementationException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(
            sprintf('object implementing interface %s\Matcher\Invocation', __NAMESPACE__)
        );
    }
}
