<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSeoBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateSeoBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.seo_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/seo_boards/{id} - Aktualisiert ein SEO Board. REST-Parameter: seo_board_id (required, integer) - SEO Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren. budget_limit_cents (optional, integer) - Budget-Limit. refresh_interval_days (optional, integer) - Refresh-Intervall.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH). Nutze "brands.seo_boards.GET" um SEO Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des SEO Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des SEO Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: SEO Board als erledigt markieren.'
                ],
                'budget_limit_cents' => [
                    'type' => 'integer',
                    'description' => 'Optional: Monatliches Budget-Limit in Cents (null = unbegrenzt).'
                ],
                'refresh_interval_days' => [
                    'type' => 'integer',
                    'description' => 'Optional: Refresh-Intervall in Tagen.'
                ],
            ],
            'required' => ['seo_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'seo_board_id', BrandsSeoBoard::class,
                'SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $seoBoard = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses SEO Board nicht bearbeiten (Policy).');
            }

            $updateData = [];

            foreach (['name', 'description', 'budget_limit_cents', 'refresh_interval_days'] as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $seoBoard->update($updateData);
            }

            $seoBoard->refresh();
            $seoBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'description' => $seoBoard->description,
                'brand_id' => $seoBoard->brand_id,
                'brand_name' => $seoBoard->brand->name,
                'team_id' => $seoBoard->team_id,
                'done' => $seoBoard->done,
                'done_at' => $seoBoard->done_at?->toIso8601String(),
                'budget_limit_cents' => $seoBoard->budget_limit_cents,
                'budget_spent_cents' => $seoBoard->budget_spent_cents,
                'refresh_interval_days' => $seoBoard->refresh_interval_days,
                'updated_at' => $seoBoard->updated_at->toIso8601String(),
                'message' => "SEO Board '{$seoBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des SEO Boards: ' . $e->getMessage());
        }
    }
}
