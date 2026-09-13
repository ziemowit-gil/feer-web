<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SklepDiscountCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel admin: CRUD kodów rabatowych sklepu.
 */
class SklepDiscountCodeController extends Controller
{
    public function index()
    {
        $codes = SklepDiscountCode::orderByDesc('id')->get();

        return view('admin.sklep.discount-codes.index', compact('codes'));
    }

    public function create()
    {
        return view('admin.sklep.discount-codes.form', ['code' => new SklepDiscountCode]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, new SklepDiscountCode);

        SklepDiscountCode::create($data);

        return redirect()->route('admin.sklep.kody-rabatowe.index')->with('status', 'Kod rabatowy został dodany.');
    }

    public function edit(SklepDiscountCode $discountCode)
    {
        return view('admin.sklep.discount-codes.form', ['code' => $discountCode]);
    }

    public function update(Request $request, SklepDiscountCode $discountCode)
    {
        $data = $this->validated($request, $discountCode);

        $discountCode->update($data);

        return redirect()->route('admin.sklep.kody-rabatowe.index')->with('status', 'Kod rabatowy został zaktualizowany.');
    }

    public function destroy(SklepDiscountCode $discountCode)
    {
        $discountCode->delete();

        return redirect()->route('admin.sklep.kody-rabatowe.index')->with('status', 'Kod rabatowy został usunięty.');
    }

    private function validated(Request $request, SklepDiscountCode $code): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('sklep_discount_codes', 'code')->ignore($code->id)],
            'type' => ['required', Rule::in(array_keys(SklepDiscountCode::TYPES))],
            'value' => ['required', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['code'] = mb_strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        if ($data['type'] === 'percent' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        return $data;
    }
}
