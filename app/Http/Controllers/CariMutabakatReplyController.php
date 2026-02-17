<?php

namespace App\Http\Controllers;

use App\Models\CariMutabakatItem;
use App\Models\CariMutabakatReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class CariMutabakatReplyController extends Controller
{
    public function show(string $token)
    {
        $item = CariMutabakatItem::where('token', $token)
            ->with(['request.customer'])
            ->firstOrFail();

        if ($item->reply) {
            return view('cari-mutabakat.already-replied', compact('item'));
        }

        return view('cari-mutabakat.reply', compact('item'));
    }

    public function store(Request $request, string $token)
    {
        $item = CariMutabakatItem::where('token', $token)->firstOrFail();

        if ($item->reply) {
            return redirect()
                ->route('cari-mutabakat.reply', $token)
                ->with('error', 'Bu mutabakat için zaten cevap verilmiş.');
        }

        $validated = $request->validate([
            'cevap' => 'required|in:mutabıkız,mutabık_değiliz',
            'cevaplayan_unvan' => 'nullable|string|max:255',
            'cevaplayan_vergi_no' => 'nullable|string|max:20',
            'aciklama' => 'nullable|string|max:2000',
            'ekstre' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5120)], // 5MB
            'e_imzali_form' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5120)], // 5MB
        ]);

        $ekstrePath = null;
        $eImzaliFormPath = null;

        if ($request->hasFile('ekstre')) {
            $ekstrePath = $request->file('ekstre')->store('cari-mutabakat-replies/' . $item->id, 'public');
        }
        if ($request->hasFile('e_imzali_form')) {
            $eImzaliFormPath = $request->file('e_imzali_form')->store('cari-mutabakat-replies/' . $item->id, 'public');
        }

        CariMutabakatReply::create([
            'item_id' => $item->id,
            'cevap' => $validated['cevap'],
            'cevaplayan_unvan' => $validated['cevaplayan_unvan'] ?? null,
            'cevaplayan_vergi_no' => $validated['cevaplayan_vergi_no'] ?? null,
            'aciklama' => $validated['aciklama'] ?? null,
            'ekstre_path' => $ekstrePath,
            'e_imzali_form_path' => $eImzaliFormPath,
            'replied_at' => now(),
        ]);

        $item->update([
            'reply_status' => 'received',
            'reply_received_at' => now(),
        ]);

        return view('cari-mutabakat.thank-you', compact('item'));
    }
}
