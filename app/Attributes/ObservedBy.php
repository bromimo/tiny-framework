<?php

namespace App\Attributes;

use Attribute;

/** Атрибут для привязки обсервера к модели.
 * Размещается на классе модели: #[ObservedBy(SomeObserver::class)].
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ObservedBy
{
    /** @param string $observerClass FQCN класса обсервера. */
    public function __construct(public readonly string $observerClass) {}
}
