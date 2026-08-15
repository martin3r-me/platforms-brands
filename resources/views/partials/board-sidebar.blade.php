{{-- Geteilte linke Inner-Sidebar (Board-Übersicht) — nx.
     Params via @include: $sidebarTitle (opt), $detailRows = [['label'=>..,'value'=>..], …] (opt).
     Für zusätzliche Blöcke: eigene Sidebar in der View statt dieses Partials. --}}
<x-ui-page-sidebar :title="$sidebarTitle ?? 'Board-Übersicht'" width="w-80" :defaultOpen="true">
    <div class="p-5 space-y-5">
        @if(!empty($detailRows))
            <div>
                <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Details</h3>
                <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] divide-y divide-[color:var(--nx-line)]">
                    @foreach($detailRows as $r)
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">{{ $r['label'] }}</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $r['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-ui-page-sidebar>
