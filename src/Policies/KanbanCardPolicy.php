<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsKanbanCard;

class KanbanCardPolicy
{
    /**
     * Darf der User diese Kanban Card sehen?
     */
    public function view(User $user, BrandsKanbanCard $kanbanCard): bool
    {
        // User muss im selben Team sein
        return $kanbanCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Kanban Card bearbeiten?
     */
    public function update(User $user, BrandsKanbanCard $kanbanCard): bool
    {
        // User muss im selben Team sein
        return $kanbanCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Kanban Card löschen?
     */
    public function delete(User $user, BrandsKanbanCard $kanbanCard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $kanbanCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User eine Kanban Card erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Kanban Cards erstellen
        return $user->currentTeam !== null;
    }
}
