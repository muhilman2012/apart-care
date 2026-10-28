<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrLocation;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QrLabelController extends Controller
{
    public function index(): View
    {
        return view('admin.qr-labels', [
            'locations' => QrLocation::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.qr-form', ['location' => new QrLocation]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('reference_image')) {
            $data['reference_image_path'] = $request->file('reference_image')->store('locations', 'public');
        }
        $location = QrLocation::create($data);

        return redirect()->route('admin.qr.index')->with('success', "Label {$location->code} berhasil dibuat dan disimpan.");
    }

    public function edit(QrLocation $qrLocation): View
    {
        return view('admin.qr-form', ['location' => $qrLocation]);
    }

    public function update(Request $request, QrLocation $qrLocation): RedirectResponse
    {
        $data = $this->validated($request, $qrLocation);
        if ($request->hasFile('reference_image')) {
            $data['reference_image_path'] = $request->file('reference_image')->store('locations', 'public');
        }
        $qrLocation->update($data);

        return redirect()->route('admin.qr.index')->with('success', "Label {$qrLocation->code} berhasil diperbarui.");
    }

    public function toggle(QrLocation $qrLocation): RedirectResponse
    {
        $qrLocation->update(['is_active' => ! $qrLocation->is_active]);

        return back()->with('success', 'Status label berhasil diperbarui.');
    }

    public function svg(QrLocation $qrLocation): Response
    {
        $qrCode = new QrCode(
            data: route('complaint.create', $qrLocation),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 360,
            margin: 18,
            foregroundColor: new Color(23, 33, 31),
            backgroundColor: new Color(255, 253, 248),
        );
        $result = (new SvgWriter)->write($qrCode);

        return response($result->getString(), 200, ['Content-Type' => $result->getMimeType()]);
    }

    private function validated(Request $request, ?QrLocation $location = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('qr_locations')->ignore($location)],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:unit,facility,asset,location'],
            'tower_name' => ['required', 'string', 'max:50'],
            'floor_number' => ['nullable', 'integer', 'min:0', 'max:255'],
            'zone' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'installation_notes' => ['nullable', 'string', 'max:1000'],
            'reference_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
