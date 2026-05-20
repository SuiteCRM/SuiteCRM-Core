<?php

namespace App\Data\Service\Record\RecordSaveHandlers;

interface RecordSaveHandlerRegistryInterface
{
    /**
     * Get the before save handlers for the module key
     * @param string $module
     * @return RecordSaveHandlerInterface[]
     */
    public function getBeforeSaveHandlers(string $module): array;

    /**
     * Get the after save handlers for the module key
     * @param string $module
     * @return RecordSaveHandlerInterface[]
     */
    public function getAfterSaveHandlers(string $module): array;
}
