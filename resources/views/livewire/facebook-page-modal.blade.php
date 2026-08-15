<x-nx-modal size="md" model="modalShow" header="{{ $brand && $brand->metaConnection() ? 'Meta erneut verknüpfen' : 'Mit Meta verknüpfen' }}">
    @if($brand)
        <div class="space-y-4">
            @if($brand->metaConnection())
                <x-nx-callout variant="warning" title="Bereits verknüpft">
                    Es existiert bereits eine Meta-Verknüpfung für diese Marke. Durch erneutes Verknüpfen wird der bestehende Token aktualisiert.
                </x-nx-callout>
            @endif

            <x-nx-callout variant="info" title="OAuth-Verbindung">
                <p class="mb-2">Du wirst zu Meta (Facebook) weitergeleitet, um deinen OAuth-Token zu erhalten.</p>
                <p>Nach der erfolgreichen Authentifizierung wird:</p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Der OAuth-Token gespeichert</li>
                    <li>Später können Facebook Pages und Instagram Accounts abgerufen werden</li>
                </ul>
            </x-nx-callout>

            @if($this->oauthRedirectUrl)
                <div class="p-3 bg-[color:var(--nx-hover)] border border-[color:var(--nx-line)] rounded-[8px] space-y-2">
                    <div>
                        <p class="text-xs font-medium text-[color:var(--nx-faint)] mb-1">Interne Redirect-URL:</p>
                        <p class="text-xs text-[color:var(--nx-text)] font-mono break-all">{{ $this->oauthRedirectUrl }}</p>
                    </div>
                    @if($this->facebookOAuthUrl)
                        <div>
                            <p class="text-xs font-medium text-[color:var(--nx-faint)] mb-1">Facebook OAuth URL (zu Facebook):</p>
                            <p class="text-xs text-[color:var(--nx-text)] font-mono break-all">{{ $this->facebookOAuthUrl }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <x-slot name="footer">
        @if($brand)
            <x-nx-button variant="ghost" wire:click="closeModal">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="startOAuth">
                <span class="inline-flex items-center gap-2">
                    @svg('heroicon-o-arrow-right', 'w-4 h-4')
                    <span>Mit Meta verbinden</span>
                </span>
            </x-nx-button>
        @endif
    </x-slot>
</x-nx-modal>
