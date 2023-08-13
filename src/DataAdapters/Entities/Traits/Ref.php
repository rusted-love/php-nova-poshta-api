<?php

declare(strict_types=1);

namespace BladL\NovaPoshta\Decorators\Objects\Traits;

/**
 * @internal
 */
trait Ref
{
    final public function getRef(): string
    {
        return $this->data->string('Ref');
    }
}