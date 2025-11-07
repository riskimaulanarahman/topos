<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Income::with('category')
            ->where('created_by', auth()->id())
            ->orderByDesc('date');
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }
        $incomes = $q->paginate(10);
        $categories = IncomeCategory::orderBy('name')->get();

        return view('pages.income.index', compact('incomes','categories'));
    }

    public function create()
    {
        $categories = IncomeCategory::orderBy('name')->get();
        return view('pages.income.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:income_categories,id'],
            'notes' => ['nullable','string','max:1000'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120']
        ]);
        $data['reference_no'] = $this->generateIncomeRef($data['date']);
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('public/income_attachments');
            $data['attachment_path'] = $path;
        }
        Income::create($data);

        return redirect()->route('income.index')->with('success', 'Data berhasil ditambahkan!');
    }

    public function edit(Income $income)
    {
        if (auth()->user()->roles !== 'admin' && $income->created_by !== auth()->id()) {
            abort(403);
        }
        $categories = IncomeCategory::orderBy('name')->get();
        return view('pages.income.edit', compact('income','categories'));
    }

    public function update(Request $request, Income $income)
    {
        if (auth()->user()->roles !== 'admin' && $income->created_by !== auth()->id()) {
            abort(403);
        }
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:income_categories,id'],
            'notes' => ['nullable','string','max:1000'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120']
        ]);
        if ($request->hasFile('attachment')) {
            if ($income->attachment_path) {
                Storage::delete($income->attachment_path);
            }
            $path = $request->file('attachment')->store('public/income_attachments');
            $data['attachment_path'] = $path;
        }
        $income->update($data);

        return redirect()->route('income.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(Income $income)
    {
        if (auth()->user()->roles !== 'admin' && $income->created_by !== auth()->id()) {
            abort(403);
        }
        if ($income->attachment_path) {
            Storage::delete($income->attachment_path);
        }
        $income->delete();
        return redirect()->route('income.index')->with('success', 'Data berhasil dihapus!');
    }

    private function generateIncomeRef($date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');
        $prefix = 'INC-' . $ymd . '-';
        $last = Income::where('reference_no', 'like', $prefix . '%')
            ->orderBy('reference_no', 'desc')
            ->value('reference_no');
        $n = 1;
        if ($last) {
            $n = (int) substr($last, -4) + 1;
        }
        $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        while (Income::where('reference_no', $ref)->exists()) {
            $n++;
            $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }
        return $ref;
    }
}
