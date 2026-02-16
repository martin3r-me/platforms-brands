<?php

namespace Platform\Brands\Services\Export;

interface ExportFormatInterface
{
    /**
     * Returns the format key (e.g. 'json', 'pdf')
     */
    public function getKey(): string;

    /**
     * Returns the human-readable label
     */
    public function getLabel(): string;

    /**
     * Returns the MIME type for the response
     */
    public function getMimeType(): string;

    /**
     * Returns the file extension
     */
    public function getFileExtension(): string;

    /**
     * Export a single board to the format
     */
    public function exportBoard(array $boardData, array $brandContext): string;

    /**
     * Export a complete brand to the format
     */
    public function exportBrand(array $brandData): string;
}
