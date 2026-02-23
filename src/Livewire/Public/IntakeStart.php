<?php

namespace Platform\Brands\Livewire\Public;

use Livewire\Component;
use Platform\Brands\Models\BrandsIntakeBoard;
use Platform\Brands\Models\BrandsIntakeSession;

class IntakeStart extends Component
{
    public string $state = 'loading';
    public ?string $intakeName = null;
    public ?string $intakeDescription = null;
    public ?string $publicToken = null;
    public string $resumeToken = '';
    public ?string $resumeError = null;

    public function mount(string $publicToken)
    {
        $this->publicToken = $publicToken;

        $board = BrandsIntakeBoard::where('public_token', $publicToken)->first();

        if (!$board) {
            $this->state = 'notFound';
            return;
        }

        if ($board->status === 'draft') {
            $this->state = 'notStarted';
            $this->intakeName = $board->name;
            return;
        }

        if ($board->status === 'closed') {
            $this->state = 'notActive';
            $this->intakeName = $board->name;
            return;
        }

        $this->intakeName = $board->name;
        $this->intakeDescription = $board->description;
        $this->state = 'ready';
    }

    public function startNew()
    {
        $board = BrandsIntakeBoard::where('public_token', $this->publicToken)->first();

        if (!$board || $board->status !== 'published') {
            if (!$board) {
                $this->state = 'notActive';
            } elseif ($board->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            $this->intakeName = $board?->name ?? $this->intakeName;
            return;
        }

        $session = $board->sessions()->create([
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referrer' => request()->header('referer'),
            ],
        ]);

        return redirect()->route('brands.public.intake-session', ['sessionToken' => $session->session_token]);
    }

    public function resumeSession()
    {
        $this->resumeError = null;

        $token = strtoupper(trim($this->resumeToken));
        $token = preg_replace('/[^A-Z0-9]/', '', $token);

        if (strlen($token) === 8) {
            $token = substr($token, 0, 4) . '-' . substr($token, 4);
        }

        if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $token)) {
            $this->resumeError = 'Bitte geben Sie einen gueltigen Token im Format XXXX-XXXX ein.';
            return;
        }

        $session = BrandsIntakeSession::where('session_token', $token)->first();

        if (!$session) {
            $this->resumeError = 'Keine Session mit diesem Token gefunden.';
            return;
        }

        $board = BrandsIntakeBoard::where('public_token', $this->publicToken)->first();
        if (!$board || $session->intake_board_id !== $board->id) {
            $this->resumeError = 'Dieser Token gehoert nicht zu diesem Fragebogen.';
            return;
        }

        if ($board->status !== 'published') {
            if ($board->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            $this->intakeName = $board->name ?? $this->intakeName;
            return;
        }

        return redirect()->route('brands.public.intake-session', ['sessionToken' => $session->session_token]);
    }

    public function render()
    {
        return view('brands::livewire.public.intake-start')
            ->layout('platform::layouts.guest');
    }
}
