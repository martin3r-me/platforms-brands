<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsKanbanBoard;

class KanbanBoardPolicy
{
    /**
     * Darf der User dieses Kanban Board sehen?
     */
    public function view(User $user, BrandsKanbanBoard $kanbanBoard): bool
    {
        // User muss im selben Team sein und Zugriff auf die Marke haben
        return $kanbanBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Kanban Board bearbeiten?
     */
    public function update(User $user, BrandsKanbanBoard $kanbanBoard): bool
    {
        // User muss im selben Team sein
        return $kanbanBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Kanban Board löschen?
     */
    public function delete(User $user, BrandsKanbanBoard $kanbanBoard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $kanbanBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User ein Kanban Board erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Kanban Boards erstellen
        return $user->currentTeam !== null;
    }
}
