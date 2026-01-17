<x-ui-modal size="md" model="modalShow" header="Facebook Page verknüpfen">
    @if($brand)
        <div class="space-y-4">
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0')
                    <div class="text-sm text-blue-700">
                        <p class="font-medium mb-2">OAuth-Verbindung</p>
                        <p class="mb-2">Du wirst zu Meta (Facebook) weitergeleitet, um deine Facebook Page und Instagram Account zu verbinden.</p>
                        <p>Nach der erfolgreichen Authentifizierung werden automatisch:</p>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Die Facebook Page mit dieser Marke verknüpft</li>
                            <li>Der zugehörige Instagram Account (falls vorhanden) angelegt</li>
                            <li>Access Tokens und Refresh Tokens sicher gespeichert</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            @if($this->oauthRedirectUrl)
                <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                    <div>
                        <p class="text-xs font-medium text-gray-600 mb-1">Interne Redirect-URL:</p>
                        <p class="text-xs text-gray-800 font-mono break-all">{{ $this->oauthRedirectUrl }}</p>
                    </div>
                    @if($this->facebookOAuthUrl)
                        <div>
                            <p class="text-xs font-medium text-gray-600 mb-1">Facebook OAuth URL (zu Facebook):</p>
                            <p class="text-xs text-gray-800 font-mono break-all">{{ $this->facebookOAuthUrl }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <x-slot name="footer">
        @if($brand)
            <x-ui-button variant="secondary" wire:click="closeModal">Abbrechen</x-ui-button>
            <x-ui-button variant="success" wire:click="startOAuth">
                <span class="inline-flex items-center gap-2">
                    @svg('heroicon-o-arrow-right', 'w-4 h-4')
                    <span>Mit Meta verbinden</span>
                </span>
            </x-ui-button>
        @endif
    </x-slot>
</x-ui-modal>
