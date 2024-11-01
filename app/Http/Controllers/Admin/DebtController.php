<?php

namespace App\Http\Controllers\Admin;

use App\Models\Debt;
use Inertia\Inertia;
use App\Exports\DebtsExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\Debugbar\Facades\Debugbar;
use Maatwebsite\Excel\Facades\Excel;

class DebtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Debts');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $customerId = $request->input('customer_id');
        $debts = Debt::where('customer_id', $customerId)->get();

        foreach ($debts as $debt) {
            $debt->delete();

            // Hapus semua hutang item yang terkait dengan hutang ini
            $debt->debtItems()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Hutang berhasil dihapus',
        ]);
    }

    /**
     * Export debts to excel.
     */

    public function exportExcel()
    {
        // Tangkap tanggal dari query string
        $startDate = request('start_date');
        $endDate = request('end_date');

        // Pastikan tanggal diberikan
        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }

        return Excel::download(new DebtsExport($startDate, $endDate), 'hutang-' . now() . '.xlsx');
    }
}
