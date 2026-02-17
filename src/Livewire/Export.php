<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Auth;

class Export extends Component
{
    public BrandsBrand $brand;

    public function mount(BrandsBrand $brandsBrand)
    {
        $this->brand = $brandsBrand;
        $this->authorize('view', $this->brand);
    }

    public function render()
    {
        $this->brand->load([
            'ciBoards',
            'contentBoards',
            'socialBoards',
            'kanbanBoards',
            'multiContentBoards',
            'guidelineBoards',
        ]);

        $exportService = app(\Platform\Brands\Services\BrandsExportService::class);
        $formats = collect($exportService->getAvailableFormats())->map(fn ($f) => [
            'key' => $f->getKey(),
            'label' => $f->getLabel(),
        ])->values()->toArray();

        // Collect all boards for individual export
        $boards = collect();

        foreach ($this->brand->ciBoards as $b) {
            $boards->push(['id' => $b->id, 'name' => $b->name, 'type' => 'ci', 'type_label' => 'CI Board', 'route_type' => 'ci-board']);
        }
        foreach ($this->brand->contentBoards as $b) {
            $boards->push(['id' => $b->id, 'name' => $b->name, 'type' => 'content', 'type_label' => 'Content Board', 'route_type' => 'content-board']);
        }
        foreach ($this->brand->socialBoards as $b) {
            $boards->push(['id' => $b->id, 'name' => $b->name, 'type' => 'social', 'type_label' => 'Social Board', 'route_type' => 'social-board']);
        }
        foreach ($this->brand->kanbanBoards as $b) {
            $boards->push(['id' => $b->id, 'name' => $b->name, 'type' => 'kanban', 'type_label' => 'Kanban Board', 'route_type' => 'kanban-board']);
        }
        foreach ($this->brand->multiContentBoards as $b) {
            $boards->push(['id' => $b->id, 'name' => $b->name, 'type' => 'multi_content', 'type_label' => 'Multi-Content Board', 'route_type' => 'multi-content-board']);
        }
        foreach ($this->brand->guidelineBoards as $b) {
            $boards->push(['id' => $b->id, 'name' => $b->name, 'type' => 'guideline', 'type_label' => 'Guidelines Board', 'route_type' => 'guideline-board']);
        }

        return view('brands::livewire.export', [
            'formats' => $formats,
            'boards' => $boards,
        ])->layout('platform::layouts.app');
    }
}
