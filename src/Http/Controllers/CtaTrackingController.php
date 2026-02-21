<?php

namespace Platform\Brands\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Platform\Brands\Models\BrandsCta;

class CtaTrackingController extends Controller
{
    /**
     * Track a CTA click and redirect to the target URL.
     *
     * GET /track/cta/{uuid}/click
     * Public endpoint – no auth required.
     */
    public function click(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
        $cta = BrandsCta::where('uuid', $uuid)
            ->where('is_active', true)
            ->first();

        if (!$cta) {
            abort(404);
        }

        // Increment click counter and update last_clicked_at
        $cta->increment('clicks');
        $cta->update(['last_clicked_at' => now()]);

        $redirectUrl = $cta->getRedirectUrl();

        if (!$redirectUrl) {
            abort(404);
        }

        return redirect()->away($redirectUrl);
    }

    /**
     * Bulk track CTA impressions.
     *
     * POST /track/cta/impressions
     * Body: { "uuids": ["uuid1", "uuid2", ...] }
     * Public endpoint – no auth required.
     */
    public function impressions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => 'required|array|max:100',
            'uuids.*' => 'required|string|max:64',
        ]);

        $uuids = $validated['uuids'];

        // Bulk increment impressions for all matching active CTAs
        $updated = BrandsCta::whereIn('uuid', $uuids)
            ->where('is_active', true)
            ->increment('impressions');

        return response()->json([
            'tracked' => $updated,
        ]);
    }
}
