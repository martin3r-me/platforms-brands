<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsSocialPlatform;
use Livewire\Attributes\On;
use Carbon\Carbon;

class EditorialPlanBoard extends Component
{
    public BrandsSocialBoard $socialBoard;

    // Filter state
    public string $viewMode = 'week'; // day, week, month
    public string $filterStatus = '';
    public string $filterPlatform = '';
    public string $currentDate = '';

    // Inline editing
    public ?int $editingCardId = null;
    public string $editPublishAt = '';

    public function mount(BrandsSocialBoard $brandsSocialBoard)
    {
        $this->socialBoard = $brandsSocialBoard->fresh()->load('brand');
        $this->authorize('view', $this->socialBoard);
        $this->currentDate = now()->format('Y-m-d');
    }

    #[On('updateEditorialPlan')]
    public function refreshBoard()
    {
        $this->socialBoard->refresh();
    }

    public function previousPeriod()
    {
        $date = Carbon::parse($this->currentDate);
        $this->currentDate = match ($this->viewMode) {
            'day' => $date->subDay()->format('Y-m-d'),
            'week' => $date->subWeek()->format('Y-m-d'),
            'month' => $date->subMonth()->format('Y-m-d'),
        };
    }

    public function nextPeriod()
    {
        $date = Carbon::parse($this->currentDate);
        $this->currentDate = match ($this->viewMode) {
            'day' => $date->addDay()->format('Y-m-d'),
            'week' => $date->addWeek()->format('Y-m-d'),
            'month' => $date->addMonth()->format('Y-m-d'),
        };
    }

    public function goToToday()
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function setViewMode(string $mode)
    {
        if (in_array($mode, ['day', 'week', 'month'])) {
            $this->viewMode = $mode;
        }
    }

    /**
     * Start inline editing of publish_at for a card.
     */
    public function startEditPublishAt(int $cardId)
    {
        $card = BrandsSocialCard::find($cardId);
        if (!$card) return;
        $this->authorize('update', $card);

        $this->editingCardId = $cardId;
        $this->editPublishAt = $card->publish_at?->format('Y-m-d\TH:i') ?? '';
    }

    public function cancelEditPublishAt()
    {
        $this->editingCardId = null;
        $this->editPublishAt = '';
    }

    /**
     * Save the inline-edited publish_at value.
     */
    public function savePublishAt()
    {
        if (!$this->editingCardId) return;

        $card = BrandsSocialCard::find($this->editingCardId);
        if (!$card) return;
        $this->authorize('update', $card);

        $updateData = [];
        if ($this->editPublishAt) {
            $updateData['publish_at'] = Carbon::parse($this->editPublishAt);
            if ($card->status === BrandsSocialCard::STATUS_DRAFT) {
                $updateData['status'] = BrandsSocialCard::STATUS_SCHEDULED;
            }
        } else {
            $updateData['publish_at'] = null;
            if ($card->status === BrandsSocialCard::STATUS_SCHEDULED) {
                $updateData['status'] = BrandsSocialCard::STATUS_DRAFT;
            }
        }

        $card->update($updateData);

        $this->editingCardId = null;
        $this->editPublishAt = '';
    }

    /**
     * Publish a card immediately (triggers all ready contracts).
     */
    public function publishNow(int $cardId)
    {
        $card = BrandsSocialCard::with(['contracts.platformFormat.platform'])->find($cardId);
        if (!$card) return;
        $this->authorize('update', $card);

        $readyContracts = $card->contracts->where('status', \Platform\Brands\Models\BrandsSocialCardContract::STATUS_READY);
        if ($readyContracts->isEmpty()) {
            session()->flash('error', 'Keine Contracts mit Status "ready" vorhanden. Generiere zuerst Contracts.');
            return;
        }

        $card->update(['status' => BrandsSocialCard::STATUS_PUBLISHING]);

        /** @var \Platform\Brands\Services\MetaPublishingService $publishingService */
        $publishingService = resolve(\Platform\Brands\Services\MetaPublishingService::class);

        $publishedCount = 0;
        $failedCount = 0;

        foreach ($readyContracts as $contract) {
            $platformKey = $contract->platformFormat->platform->key ?? null;

            $user = Auth::user();
            $publishResult = match ($platformKey) {
                'facebook' => $publishingService->publishToFacebook($contract, $user, $card->team_id),
                'instagram' => $publishingService->publishToInstagram($contract, $user, $card->team_id),
                default => [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => "Publishing für Plattform '{$platformKey}' wird noch nicht unterstützt.",
                ],
            };

            if ($publishResult['success']) {
                $contract->update([
                    'status' => \Platform\Brands\Models\BrandsSocialCardContract::STATUS_PUBLISHED,
                    'published_at' => Carbon::now(),
                    'external_post_id' => $publishResult['external_post_id'],
                    'error_message' => null,
                ]);
                $publishedCount++;
            } else {
                $contract->update([
                    'status' => \Platform\Brands\Models\BrandsSocialCardContract::STATUS_FAILED,
                    'error_message' => $publishResult['error'],
                ]);
                $failedCount++;
            }
        }

        $now = Carbon::now();
        if ($failedCount > 0) {
            $card->update([
                'status' => BrandsSocialCard::STATUS_FAILED,
                'published_at' => $publishedCount > 0 ? $now : null,
            ]);
            session()->flash('error', "{$publishedCount} erfolgreich, {$failedCount} fehlgeschlagen.");
        } else {
            $card->update([
                'status' => BrandsSocialCard::STATUS_PUBLISHED,
                'published_at' => $now,
            ]);
            session()->flash('success', "Alle {$publishedCount} Contract(s) erfolgreich gepublished.");
        }
    }

    /**
     * Get the date range for the current view.
     */
    protected function getDateRange(): array
    {
        $current = Carbon::parse($this->currentDate);

        return match ($this->viewMode) {
            'day' => [
                'start' => $current->copy()->startOfDay(),
                'end' => $current->copy()->endOfDay(),
            ],
            'week' => [
                'start' => $current->copy()->startOfWeek(Carbon::MONDAY),
                'end' => $current->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            'month' => [
                'start' => $current->copy()->startOfMonth(),
                'end' => $current->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Get days to display in the current view.
     */
    protected function getDays(array $range): array
    {
        $days = [];
        $current = $range['start']->copy();
        while ($current->lte($range['end'])) {
            $days[] = $current->copy();
            $current->addDay();
        }
        return $days;
    }

    /**
     * Get the title for the current period.
     */
    protected function getPeriodTitle(): string
    {
        $current = Carbon::parse($this->currentDate);
        return match ($this->viewMode) {
            'day' => $current->translatedFormat('l, j. F Y'),
            'week' => 'KW ' . $current->isoWeek() . ' · ' . $current->copy()->startOfWeek(Carbon::MONDAY)->format('d.m.') . ' – ' . $current->copy()->endOfWeek(Carbon::SUNDAY)->format('d.m.Y'),
            'month' => $current->translatedFormat('F Y'),
        };
    }

    public function render()
    {
        $user = Auth::user();
        $range = $this->getDateRange();
        $days = $this->getDays($range);

        // Build query for cards with publish_at in range + unscheduled cards
        $query = BrandsSocialCard::query()
            ->where('social_board_id', $this->socialBoard->id)
            ->with(['contracts.platformFormat.platform', 'slot']);

        // Apply status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Apply platform filter
        if ($this->filterPlatform) {
            $platformId = (int) $this->filterPlatform;
            $query->whereHas('contracts.platformFormat.platform', function ($q) use ($platformId) {
                $q->where('brands_social_platforms.id', $platformId);
            });
        }

        // Get scheduled cards (with publish_at in range)
        $scheduledCards = (clone $query)
            ->whereNotNull('publish_at')
            ->whereBetween('publish_at', [$range['start'], $range['end']])
            ->orderBy('publish_at')
            ->get();

        // Get unscheduled cards (no publish_at) - only show if not filtered by date strictly
        $unscheduledCards = (clone $query)
            ->whereNull('publish_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group scheduled cards by date
        $cardsByDate = [];
        foreach ($days as $day) {
            $dateKey = $day->format('Y-m-d');
            $cardsByDate[$dateKey] = $scheduledCards->filter(function ($card) use ($day) {
                return $card->publish_at->isSameDay($day);
            })->values();
        }

        // Get available platforms for filter
        $platforms = BrandsSocialPlatform::where('is_active', true)
            ->where('team_id', $this->socialBoard->team_id)
            ->orderBy('name')
            ->get();

        return view('brands::livewire.editorial-plan-board', [
            'user' => $user,
            'days' => $days,
            'cardsByDate' => $cardsByDate,
            'unscheduledCards' => $unscheduledCards,
            'periodTitle' => $this->getPeriodTitle(),
            'range' => $range,
            'platforms' => $platforms,
            'statuses' => BrandsSocialCard::STATUSES,
        ])->layout('platform::layouts.app');
    }
}
