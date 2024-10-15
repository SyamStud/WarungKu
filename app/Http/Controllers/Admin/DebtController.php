<?php

namespace App\Http\Controllers\Admin;

use App\Models\Debt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

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
        $debts = Debt::where('customer_id', $request->customer_id)->get();

        foreach ($debts as $debt) {
            $debt->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Hutang berhasil dihapus',
        ]);
    }
}
