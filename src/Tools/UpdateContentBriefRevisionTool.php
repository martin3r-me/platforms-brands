<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefRevision;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateContentBriefRevisionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_revisions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_brief_revisions/{id} - Aktualisiert eine bestehende Revision. Parameter: revision_id (required). summary, revision_type, metrics_before, metrics_after, changes, revised_at (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'revision_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Revision (ERFORDERLICH).',
                ],
                'revision_type' => [
                    'type' => 'string',
                    'description' => 'Neuer Revisionstyp. Werte per Lookup-Tabelle (brands.lookup_values.GET name="revision_type").',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'Neue Beschreibung der Änderung.',
                ],
                'metrics_before' => [
                    'type' => 'object',
                    'description' => 'Aktualisierte Metriken VOR der Änderung.',
                ],
                'metrics_after' => [
                    'type' => 'object',
                    'description' => 'Aktualisierte Metriken NACH der Änderung.',
                ],
                'changes' => [
                    'type' => 'array',
                    'description' => 'Aktualisierte Liste von Einzeländerungen.',
                ],
                'revised_at' => [
                    'type' => 'string',
                    'description' => 'Neuer Zeitpunkt (ISO 8601).',
                ],
            ],
            'required' => ['revision_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $revisionId = $arguments['revision_id'] ?? null;
            if (!$revisionId) {
                return ToolResult::error('VALIDATION_ERROR', 'revision_id ist erforderlich.');
            }

            $revision = BrandsContentBriefRevision::with('contentBriefBoard')->find($revisionId);
            if (!$revision) {
                return ToolResult::error('NOT_FOUND', 'Revision nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $revision->contentBriefBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Keine Berechtigung.');
            }

            // Revision-Typ validieren
            if (isset($arguments['revision_type'])) {
                $teamId = $revision->contentBriefBoard->team_id;
                if (!BrandsContentBriefBoard::isValidLookupValue('revision_type', $arguments['revision_type'], $teamId)) {
                    $allowed = BrandsContentBriefBoard::getAllowedValues('revision_type', $teamId);
                    if (!empty($allowed)) {
                        return ToolResult::error('VALIDATION_ERROR', 'Ungültiger revision_type. Erlaubt: ' . implode(', ', $allowed));
                    }
                }
            }

            $updateData = [];
            foreach (['revision_type', 'summary', 'metrics_before', 'metrics_after', 'changes', 'revised_at'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $revision->update($updateData);
            }

            $revision->refresh();

            return ToolResult::success([
                'id' => $revision->id,
                'uuid' => $revision->uuid,
                'revision_type' => $revision->revision_type,
                'summary' => $revision->summary,
                'metrics_delta' => $revision->metrics_delta,
                'revised_at' => $revision->revised_at->toIso8601String(),
                'message' => 'Revision erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Revision: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief', 'revision', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
