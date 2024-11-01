<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Restock;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Stock;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard');
    }

    public function dashboardSummary()
    {
        // Hitung total transaksi hari ini
        $totalTransactionToday = Transaction::whereDate('created_at', Carbon::today())->sum('total_price');

        // Hitung total pembelian (purchase) hari ini
        $totalPurchaseToday = Restock::whereDate('created_at', Carbon::today())->sum('price');

        // Ambil transaksi dengan total tertinggi hari ini
        $highestTransaction = Transaction::whereDate('created_at', Carbon::today())->orderBy('total_price', 'desc')->first();
        $highestTransactionPrice = $highestTransaction ? $highestTransaction->total_price : 0; // Jika tidak ada transaksi, set 0

        // Dapatkan produk yang paling banyak terjual hari ini
        $topOne = TransactionItem::select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->with('product')
            ->whereDate('created_at', Carbon::today())
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->first();
        $topOneProductName = $topOne && $topOne->product ? $topOne->product->name : 'N/A'; // Jika tidak ada produk, set 'N/A'

        $topProducts = TransactionItem::select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(price) as total_price'))
            ->with('product')
            ->whereDate('created_at', Carbon::today())
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $needRestock = Stock::select('product_variant_id', DB::raw('SUM(quantity) as total_quantity'))
            ->with('productVariant.product')
            ->groupBy('product_variant_id')
            ->havingRaw('SUM(quantity) <= 5')
            ->orderBy('total_quantity')
            ->limit(10)
            ->get();

        // Mengembalikan semua data dalam satu respons JSON
        return response()->json([
            'total_transaction' => $totalTransactionToday,
            'total_purchase' => $totalPurchaseToday,
            'highest_transaction' => $highestTransactionPrice,
            'top_one' => $topOneProductName,
            'top_products' => $topProducts,
            'need_restock' => $needRestock,
        ]);
    }
}
