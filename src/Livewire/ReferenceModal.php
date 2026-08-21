<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Platform\Brands\Models\BrandsReferenceBoard;
use Platform\Brands\Models\BrandsReference;
use Livewire\Attributes\On;

class ReferenceModal extends Component
{
    public bool $modalShow = false;
    public $reference = null;
    public $referenceBoardId;
    public bool $isEdit = false;

    public string $url = '';
    public string $title = '';
    public string $screenshotUrl = '';
    public string $verdict = 'like';
    public string $reason = '';
    public array $aspects = [];
    public string $industry = '';

    public function mount()
    {
        $this->modalShow = false;
    }

    #[On('open-modal-reference')]
    public function openModal($referenceBoardId, $referenceId = null)
    {
        $this->referenceBoardId = $referenceBoardId;
        $this->resetFields();

        if ($referenceId) {
            $this->reference = BrandsReference::findOrFail($referenceId);
            $this->isEdit = true;
            $this->url = $this->reference->url;
            $this->title = $this->reference->title ?? '';
            $this->screenshotUrl = $this->reference->screenshot_url ?? '';
            $this->verdict = $this->reference->verdict ?? 'like';
            $this->reason = $this->reference->reason ?? '';
            $this->aspects = $this->reference->aspects ?? [];
            $this->industry = $this->reference->industry ?? '';
        } else {
            $this->reference = null;
            $this->isEdit = false;
        }

        $this->modalShow = true;
    }

    protected function resetFields(): void
    {
        $this->url = '';
        $this->title = '';
        $this->screenshotUrl = '';
        $this->verdict = 'like';
        $this->reason = '';
        $this->aspects = [];
        $this->industry = '';
        $this->reference = null;
        $this->isEdit = false;
    }

    public function toggleAspect(string $key): void
    {
        if (in_array($key, $this->aspects, true)) {
            $this->aspects = array_values(array_diff($this->aspects, [$key]));
        } else {
            $this->aspects[] = $key;
        }
    }

    protected function normalizedUrl(): ?string
    {
        $url = trim($this->url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    /** Best-effort: OG-Image + Titel von der URL ziehen. */
    public function fetchPreview(): void
    {
        $url = $this->normalizedUrl();
        if (!$url) {
            return;
        }

        try {
            $res = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; BHG-Brands/1.0)'])
                ->get($url);

            if (!$res->ok()) {
                session()->flash('reference_error', 'Seite nicht erreichbar (' . $res->status() . ').');
                return;
            }

            $html = $res->body();

            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
                $this->screenshotUrl = $this->absolutize(trim($m[1]), $url);
            }

            if ($this->title === '') {
                if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $t)
                    || preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $t)) {
                    $this->title = trim(html_entity_decode($t[1], ENT_QUOTES | ENT_HTML5));
                }
            }
        } catch (\Throwable $e) {
            session()->flash('reference_error', 'Vorschau konnte nicht geladen werden.');
        }
    }

    protected function absolutize(string $src, string $base): string
    {
        if (preg_match('#^https?://#i', $src)) {
            return $src;
        }
        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (str_starts_with($src, '//')) {
            return ($parts['scheme'] ?? 'https') . ':' . $src;
        }
        return $origin . '/' . ltrim($src, '/');
    }

    public function rules(): array
    {
        return [
            'url' => 'required|string|max:2048',
            'verdict' => 'required|in:like,dislike,neutral',
            'title' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:2000',
            'industry' => 'nullable|string|max:120',
        ];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsReferenceBoard::findOrFail($this->referenceBoardId);
        $this->authorize('update', $board);

        $data = [
            'url' => $this->normalizedUrl(),
            'title' => $this->title ?: null,
            'screenshot_url' => $this->screenshotUrl ?: null,
            'verdict' => $this->verdict,
            'reason' => $this->reason ?: null,
            'aspects' => $this->aspects ?: null,
            'industry' => $this->industry ?: null,
        ];

        if ($this->reference) {
            $this->reference->update($data);
        } else {
            $data['reference_board_id'] = $this->referenceBoardId;
            BrandsReference::create($data);
        }

        $this->dispatch('updateReferenceBoard');
        $this->closeModal();
    }

    public function deleteReference()
    {
        if (!$this->reference) {
            return;
        }
        $this->authorize('update', $this->reference->referenceBoard);
        $this->reference->delete();

        $this->dispatch('updateReferenceBoard');
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.reference-modal', [
            'aspectLabels' => config('brands.reference_aspects', []),
        ])->layout('platform::layouts.app');
    }
}
