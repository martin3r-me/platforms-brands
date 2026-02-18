<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSeoKeyword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteSeoKeywordTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.seo_keywords.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/seo_keywords/{id} - Löscht ein SEO Keyword. REST-Parameter: seo_keyword_id (required, integer) - Keyword-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_keyword_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Keywords (ERFORDERLICH).'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung.'
                ]
            ],
            'required' => ['seo_keyword_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'seo_keyword_id', BrandsSeoKeyword::class,
                'KEYWORD_NOT_FOUND', 'Das angegebene Keyword wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $keyword = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $keyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Keyword nicht löschen (Policy).');
            }

            $keywordText = $keyword->keyword;
            $keywordId = $keyword->id;
            $seoBoardId = $keyword->seo_board_id;
            $teamId = $keyword->team_id;

            $keyword->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.seo_keywords.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'seo_keyword_id' => $keywordId,
                'keyword' => $keywordText,
                'seo_board_id' => $seoBoardId,
                'message' => "Keyword '{$keywordText}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Keywords: ' . $e->getMessage());
        }
    }
}
