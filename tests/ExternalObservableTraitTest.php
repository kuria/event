<?php

namespace Kuria\Event;

/**
 * @requires PHP 5.4
 */
class ExternalObservableTraitTest extends ExternalObservableTest
{
    protected function getObservableClass()
    {
        return __NAMESPACE__ . '\TestExternalObservableUsingTrait';
    }
}
