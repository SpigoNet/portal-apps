<?php

namespace App\Modules\StreamingManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\StreamingManager\Models\Streaming;
use App\Modules\StreamingManager\Models\StreamingPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function store(Request $request, Streaming $streaming)
    {
        // Only members or owner can add funds
        $isOwner = $streaming->user_id === Auth::id();
        $isMember = $streaming->members()->where('user_id', Auth::id())->exists();

        if (!$isOwner && !$isMember) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        $userId = Auth::id();
        $note = null;

        // If owner is adding
        if ($isOwner) {
            // Check if they are paying for someone else (registered user)
            if (!empty($validated['target_user_id'])) {
                $userId = $validated['target_user_id'];
                // Verify if this user is actually a member of the streaming? 
                // Flexible: Maybe they are paying for someone who just left? 
                // Strict: Only allow members. Let's start flexible or strict? 
                // Strict ensures data integrity mostly.
                if (!$streaming->members()->where('user_id', $userId)->exists() && $userId != Auth::id()) {
                    // Allow paying for self (owner)
                }
            }
            // Check if they provided a note (Guest payment)
            elseif (!empty($validated['note'])) {
                $userId = null;
                $note = $validated['note'];
            }
        }

        $streaming->payments()->create([
            'user_id' => $userId,
            'amount' => $validated['amount'],
            'note' => $note,
            'status' => ($isOwner) ? 'approved' : 'pending',
            'approved_at' => ($isOwner) ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Fundo adicionado!');
    }

    public function approve(StreamingPayment $payment)
    {
        if ($payment->streaming->user_id !== Auth::id()) {
            abort(403);
        }

        $payment->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pagamento aprovado!');
    }
}
