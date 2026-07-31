<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Models\GasMeterReading;
use App\Services\GeminiMeterReader;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class GasMeterReadingController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));
        $monthKey = $month->toDateString();
        $prevMonthKey = $month->copy()->subMonth()->toDateString();

        $readings = GasMeterReading::query()
            ->where(function ($q) use ($monthKey, $prevMonthKey) {
                $q->whereDate('bill_month', $monthKey)
                    ->orWhereDate('bill_month', $prevMonthKey);
            })
            ->get()
            ->groupBy('flat_id');

        $flats = Flat::query()
            ->with('billTypeSettings.billType')
            ->get()
            ->filter(fn (Flat $flat) => $flat->isBillTypeEnabled('gas'))
            ->sortBy(function (Flat $flat) {
                preg_match('/^(\d+)([A-Z])$/i', $flat->name, $m);

                return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $flat->name];
            })
            ->values();

        $rows = $flats->map(function (Flat $flat) use ($readings, $monthKey, $prevMonthKey) {
            $flatReadings = $readings->get($flat->id, collect());
            $current = $flatReadings->first(fn ($r) => $r->bill_month->toDateString() === $monthKey);
            $previous = $flatReadings->first(fn ($r) => $r->bill_month->toDateString() === $prevMonthKey);

            return [
                'flat' => $flat,
                'reading' => $current,
                'suggested_previous_m3' => $previous
                    ? (float) ($previous->confirmed_m3 ?? $previous->current_m3)
                    : 0.0,
            ];
        });

        return view('admin.gas-readings.index', [
            'selectedMonth' => $month,
            'rows' => $rows,
            'geminiReady' => app(GeminiMeterReader::class)->isConfigured(),
        ]);
    }

    public function assist(Request $request, Flat $flat): View|RedirectResponse
    {
        if (! $flat->isBillTypeEnabled('gas')) {
            return redirect()
                ->route('admin.gas-readings.index')
                ->withErrors(['flat_id' => 'Gas is disabled for flat '.$flat->name.'.']);
        }

        $month = $this->resolveMonth($request->query('month'));
        $monthKey = $month->toDateString();
        $prevMonthKey = $month->copy()->subMonth()->toDateString();

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', $monthKey)
            ->first();

        $previous = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', $prevMonthKey)
            ->first();

        $suggestedPrevious = $previous
            ? (float) ($previous->confirmed_m3 ?? $previous->current_m3)
            : 0.0;

        return view('admin.gas-readings.assist', [
            'flat' => $flat,
            'selectedMonth' => $month,
            'reading' => $reading,
            'suggestedPrevious' => $suggestedPrevious,
            'geminiReady' => app(GeminiMeterReader::class)->isConfigured(),
            'pendingSuggestion' => session('gemini_suggestion'),
            'pendingPhotoPath' => session('gemini_photo_path'),
        ]);
    }

    public function uploadPhoto(Request $request, Flat $flat)
    {
        if (! $flat->isBillTypeEnabled('gas')) {
            return response()->json(['message' => 'Gas is disabled for this flat.'], 422);
        }

        $month = $this->resolveMonth($request->input('bill_month'));
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
            'reading_date' => ['nullable', 'date'],
        ]);

        $path = $data['photo']->store('meter-readings/'.$flat->id, 'public');
        $prevMonthKey = $month->copy()->subMonth()->toDateString();
        $previousReading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', $prevMonthKey)
            ->first();
        $suggestedPrev = $previousReading
            ? (float) ($previousReading->confirmed_m3 ?? $previousReading->current_m3)
            : 0.0;

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', $month->toDateString())
            ->first();

        if ($reading) {
            if ($reading->photo_path) {
                Storage::disk('public')->delete($reading->photo_path);
            }
            $reading->update([
                'photo_path' => $path,
                // Clear stale OCR when a new photo arrives — admin must request again.
                'gemini_suggestion' => null,
            ]);
        } else {
            $reading = GasMeterReading::query()->create([
                'flat_id' => $flat->id,
                'bill_month' => $month->toDateString(),
                'reading_date' => $data['reading_date'] ?? $month->copy()->endOfMonth()->toDateString(),
                'previous_m3' => $suggestedPrev,
                'current_m3' => $suggestedPrev,
                'confirmed_m3' => $suggestedPrev,
                'photo_path' => $path,
                'gemini_suggestion' => null,
            ]);
        }

        \App\Support\Auditor::log('gas_reading.photo_uploaded', $reading, [
            'flat' => $flat->name,
            'bill_month' => $month->toDateString(),
        ]);

        return response()->json([
            'ok' => true,
            'reading_id' => $reading->id,
            'flat_id' => $flat->id,
            'bill_month' => $month->format('Y-m'),
            'photo_url' => asset('storage/'.$path),
            'photo_path' => $path,
            'previous_m3' => (float) $reading->previous_m3,
            'message' => 'Photo saved on server. You can request OCR when ready.',
        ]);
    }

    public function requestOcr(Request $request, Flat $flat, GeminiMeterReader $reader)
    {
        if (! $flat->isBillTypeEnabled('gas')) {
            return response()->json(['message' => 'Gas is disabled for this flat.'], 422);
        }

        if (! $reader->isConfigured()) {
            return response()->json(['message' => 'GEMINI_API_KEY is not configured.'], 422);
        }

        $month = $this->resolveMonth($request->input('bill_month'));
        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', $month->toDateString())
            ->first();

        if (! $reading || ! $reading->photo_path) {
            return response()->json(['message' => 'Upload a photo to the server before requesting OCR.'], 422);
        }

        $absolute = Storage::disk('public')->path($reading->photo_path);
        if (! is_readable($absolute)) {
            return response()->json(['message' => 'Stored photo file is missing.'], 422);
        }

        try {
            $suggestion = $reader->suggestFromImage($absolute);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Suggestion only — never overwrite confirmed/current automatically.
        $reading->update(['gemini_suggestion' => $suggestion]);

        \App\Support\Auditor::log('gas_reading.ocr_requested', $reading, [
            'flat' => $flat->name,
            'suggestion' => $suggestion,
        ]);

        return response()->json([
            'ok' => true,
            'reading_id' => $reading->id,
            'flat_id' => $flat->id,
            'bill_month' => $month->format('Y-m'),
            'gemini_suggestion' => $suggestion,
            'message' => 'OCR suggestion ready. Review and save the confirmed reading.',
        ]);
    }

    public function suggest(Request $request, Flat $flat, GeminiMeterReader $reader): RedirectResponse
    {
        // Legacy assist-page form: upload then OCR in one step.
        // Preferred flow on the main list is uploadPhoto + requestOcr (offline-friendly).
        if (! $flat->isBillTypeEnabled('gas')) {
            return back()->withErrors(['photo' => 'Gas is disabled for this flat.']);
        }

        $month = $this->resolveMonth($request->input('bill_month'));
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $path = $data['photo']->store('meter-readings/'.$flat->id, 'public');
        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', $month->toDateString())
            ->first();

        if ($reading) {
            if ($reading->photo_path) {
                Storage::disk('public')->delete($reading->photo_path);
            }
            $reading->update(['photo_path' => $path, 'gemini_suggestion' => null]);
        } else {
            $prev = GasMeterReading::query()
                ->where('flat_id', $flat->id)
                ->whereDate('bill_month', $month->copy()->subMonth()->toDateString())
                ->first();
            $suggestedPrev = $prev ? (float) ($prev->confirmed_m3 ?? $prev->current_m3) : 0.0;
            $reading = GasMeterReading::query()->create([
                'flat_id' => $flat->id,
                'bill_month' => $month->toDateString(),
                'reading_date' => $month->copy()->endOfMonth()->toDateString(),
                'previous_m3' => $suggestedPrev,
                'current_m3' => $suggestedPrev,
                'confirmed_m3' => $suggestedPrev,
                'photo_path' => $path,
            ]);
        }

        try {
            $suggestion = $reader->suggestFromImage(Storage::disk('public')->path($path));
            $reading->update(['gemini_suggestion' => $suggestion]);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.gas-readings.assist', ['flat' => $flat, 'month' => $month->format('Y-m')])
                ->withErrors(['photo' => $e->getMessage()])
                ->with('gemini_photo_path', $path);
        }

        return redirect()
            ->route('admin.gas-readings.assist', ['flat' => $flat, 'month' => $month->format('Y-m')])
            ->with('success', 'Gemini suggested '.$suggestion.' m³. Review and confirm before saving.')
            ->with('gemini_suggestion', $suggestion)
            ->with('gemini_photo_path', $path);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $month = $this->resolveMonth($request->input('bill_month'));

        $data = $request->validate([
            'flat_id' => [
                'required',
                'integer',
                'exists:flats,id',
                Rule::unique('gas_meter_readings', 'flat_id')
                    ->where(fn ($q) => $q->whereDate('bill_month', $month->toDateString())),
            ],
            'reading_date' => ['required', 'date'],
            'previous_m3' => ['required', 'numeric', 'min:0'],
            'current_m3' => ['required', 'numeric', 'min:0'],
            'gemini_suggestion' => ['nullable', 'numeric', 'min:0'],
            'photo_path' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $flat = Flat::query()->findOrFail($data['flat_id']);

        if (! $flat->isBillTypeEnabled('gas')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gas is disabled for flat '.$flat->name.'.'], 422);
            }

            return back()->withErrors(['flat_id' => 'Gas is disabled for flat '.$flat->name.'.'])->withInput();
        }

        if ((float) $data['current_m3'] < (float) $data['previous_m3']) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Current reading cannot be less than previous.', 'errors' => [
                    'current_m3' => ['Current reading cannot be less than previous.'],
                ]], 422);
            }

            return back()->withErrors(['current_m3' => 'Current reading cannot be less than previous.'])->withInput();
        }

        $photoPath = $data['photo_path'] ?? null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('meter-readings/'.$flat->id, 'public');
        }

        $reading = GasMeterReading::query()->create([
            'flat_id' => $flat->id,
            'bill_month' => $month->toDateString(),
            'reading_date' => $data['reading_date'],
            'previous_m3' => $data['previous_m3'],
            'current_m3' => $data['current_m3'],
            'confirmed_m3' => $data['current_m3'],
            'photo_path' => $photoPath,
            'gemini_suggestion' => $data['gemini_suggestion'] ?? null,
        ]);

        \App\Support\Auditor::log('gas_reading.created', $flat, [
            'bill_month' => $month->toDateString(),
            'current_m3' => $data['current_m3'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Gas reading saved for '.$flat->name.'.',
                'reading' => [
                    'id' => $reading->id,
                    'flat_id' => $flat->id,
                    'flat_name' => $flat->name,
                    'bill_month' => $month->format('Y-m'),
                    'reading_date' => $reading->reading_date?->format('Y-m-d'),
                    'previous_m3' => (float) $reading->previous_m3,
                    'current_m3' => (float) $reading->current_m3,
                    'consumed_m3' => $reading->consumedM3(),
                    'photo_path' => $reading->photo_path,
                    'gemini_suggestion' => $reading->gemini_suggestion !== null
                        ? (float) $reading->gemini_suggestion
                        : null,
                    'update_url' => route('admin.gas-readings.update', $reading),
                    'destroy_url' => route('admin.gas-readings.destroy', $reading),
                ],
            ]);
        }

        return redirect()
            ->route('admin.gas-readings.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Gas reading confirmed and saved for '.$flat->name.'.');
    }

    public function update(Request $request, GasMeterReading $gasMeterReading): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'reading_date' => ['required', 'date'],
            'previous_m3' => ['required', 'numeric', 'min:0'],
            'current_m3' => ['required', 'numeric', 'min:0'],
            'gemini_suggestion' => ['nullable', 'numeric', 'min:0'],
            'photo_path' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        if (! $gasMeterReading->flat->isBillTypeEnabled('gas')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gas is disabled for this flat.'], 422);
            }

            return back()->withErrors(['flat_id' => 'Gas is disabled for this flat.']);
        }

        if ((float) $data['current_m3'] < (float) $data['previous_m3']) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Current reading cannot be less than previous.', 'errors' => [
                    'current_m3' => ['Current reading cannot be less than previous.'],
                ]], 422);
            }

            return back()->withErrors(['current_m3' => 'Current reading cannot be less than previous.'])->withInput();
        }

        $photoPath = $gasMeterReading->photo_path;
        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('meter-readings/'.$gasMeterReading->flat_id, 'public');
        } elseif (! empty($data['photo_path'])) {
            $photoPath = $data['photo_path'];
        }

        $gasMeterReading->update([
            'reading_date' => $data['reading_date'],
            'previous_m3' => $data['previous_m3'],
            'current_m3' => $data['current_m3'],
            'confirmed_m3' => $data['current_m3'],
            'photo_path' => $photoPath,
            'gemini_suggestion' => $data['gemini_suggestion'] ?? $gasMeterReading->gemini_suggestion,
        ]);

        $gasMeterReading->refresh();
        $flat = $gasMeterReading->flat;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Gas reading confirmed and saved for '.$flat->name.'.',
                'reading' => [
                    'id' => $gasMeterReading->id,
                    'flat_id' => $flat->id,
                    'flat_name' => $flat->name,
                    'bill_month' => $gasMeterReading->bill_month->format('Y-m'),
                    'reading_date' => $gasMeterReading->reading_date?->format('Y-m-d'),
                    'previous_m3' => (float) $gasMeterReading->previous_m3,
                    'current_m3' => (float) $gasMeterReading->current_m3,
                    'consumed_m3' => $gasMeterReading->consumedM3(),
                    'photo_path' => $gasMeterReading->photo_path,
                    'gemini_suggestion' => $gasMeterReading->gemini_suggestion !== null
                        ? (float) $gasMeterReading->gemini_suggestion
                        : null,
                    'update_url' => route('admin.gas-readings.update', $gasMeterReading),
                    'destroy_url' => route('admin.gas-readings.destroy', $gasMeterReading),
                ],
            ]);
        }

        return redirect()
            ->route('admin.gas-readings.index', ['month' => $gasMeterReading->bill_month->format('Y-m')])
            ->with('success', 'Gas reading confirmed and saved for '.$flat->name.'.');
    }

    public function destroy(GasMeterReading $gasMeterReading): RedirectResponse
    {
        $month = $gasMeterReading->bill_month->format('Y-m');
        if ($gasMeterReading->photo_path) {
            Storage::disk('public')->delete($gasMeterReading->photo_path);
        }
        $gasMeterReading->delete();

        return redirect()
            ->route('admin.gas-readings.index', ['month' => $month])
            ->with('success', 'Gas reading deleted.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        return BillMonth::parse($month);
    }
}
