<?php

namespace Kuria\Event;

/**
 * External observable interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface ExternalObservableInterface extends ObservableInterface
{
    /**
     * Get the underlying observable object
     *
     * @return ObservableInterface|null
     */
    public function getNestedObservable();

    /**
     * Set or replace the underlying observable object
     *
     * @param ObservableInterface|null $observable
     * @return static
     */
    public function setNestedObservable(ObservableInterface $observable = null);
}
