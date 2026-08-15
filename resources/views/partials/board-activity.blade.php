{{-- Geteiltes Aktivitäten-Panel (rechte Inner-Sidebar) — nx. In den activity-Slot inkludieren. --}}
<x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
    <div class="p-5">
        <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Letzte Aktivitäten</h3>
        <div class="space-y-2">
            @forelse(($activities ?? []) as $activity)
                <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="text-[13px] font-medium leading-snug text-[color:var(--nx-text)]">{{ $activity['title'] ?? 'Aktivität' }}</div>
                        @if(($activity['type'] ?? null) === 'system')
                            <x-nx-badge variant="neutral">System</x-nx-badge>
                        @endif
                    </div>
                    <div class="mt-1 flex items-center gap-1.5 text-[11px] text-[color:var(--nx-faint)]">
                        @svg('heroicon-o-clock', 'w-3 h-3')<span>{{ $activity['time'] ?? '' }}</span>
                    </div>
                </div>
            @empty
                <x-nx-empty icon="heroicon-o-clock">Noch keine Aktivitäten</x-nx-empty>
            @endforelse
        </div>
    </div>
</x-ui-page-sidebar>
