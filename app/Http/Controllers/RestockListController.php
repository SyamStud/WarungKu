<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Inertia\Inertia;
use Mike42\Escpos\Printer;
use App\Models\RestockList;
use Illuminate\Http\Request;
use Mike42\Escpos\CapabilityProfile;
use Illuminate\Support\Facades\Validator;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class RestockListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/RestockList');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validation = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
            ], 422);
        }

        $unit = Unit::where('name', 'PCS')->first();

        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found',
            ], 404);
        }

        $request->merge([
            'unit_id' => $unit->id,
        ]);

        // Cek apakah produk sudah ada dalam RestockList
        $restockEntry = RestockList::where('product_id', $request->product_id)->first();

        if ($restockEntry) {
            // Jika produk sudah ada, tambahkan kuantitasnya
            $restockEntry->quantity += $request->quantity;
            $restockEntry->save();
            $message = 'Quantity updated successfully';
        } else {
            // Jika produk belum ada, buat entri baru
            RestockList::create($request->all());
            $message = 'Restock list created successfully';
        }

        // Berikan respons sukses
        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(RestockList $restockList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RestockList $restockList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RestockList $restockList)
    {
        $restockList->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Restock list updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RestockList $restockList)
    {
        //
    }

    /**
     * Get all restocks data.
     */
    public function restockListData(Request $request)
    {
        $query = RestockList::query();

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('product', function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%');
                })->orWhereHas('supplier', function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%');
                });
            });
        }

        // Handle sorting
        if ($request->has('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
        }

        // Handle pagination
        $perPage = $request->input('per_page', 10);
        $lists = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($lists->currentPage() - 1) * $lists->perPage() + 1;
        $to = min($from + $lists->count() - 1, $lists->total());

        $transformedLists = $lists->map(function ($item) {
            return [
                'id' => $item->id,
                'product' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_id' => (string) $item->unit_id,
                'note' => $item->note,
            ];
        });

        return response()->json([
            'data' => $transformedLists,
            'meta' => [
                'current_page' => $lists->currentPage(),
                'last_page' => $lists->lastPage(),
                'per_page' => $lists->perPage(),
                'total' => $lists->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function print()
    {
        $items = RestockList::all();

        // Menghubungkan ke printer dengan nama printer
        $profile = CapabilityProfile::load('simple');
        $connector = new WindowsPrintConnector("TP806");
        $printer = new Printer($connector, $profile);

        // Nama dan informasi toko
        $tokoName = "DAFTAR BELANJA";

        // Pengaturan format
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text($tokoName . "\n");
        $printer->setTextSize(1, 1);
        $printer->text(str_repeat("-", 47) . "\n"); // Garis pemisah

        // Header kolom barang
        $printer->setEmphasis(true);
        $printer->text(str_pad("Barang", 30) . str_pad("Qty", 16, ' ', STR_PAD_LEFT) . "\n"); // Header dengan margin kanan
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 47) . "\n"); // Garis pemisah lebih lebar

        foreach ($items as $item) {
            // Nama barang dicetak di baris pertama
            $printer->text($item->product->name . "\n");

            // Detail barang: Qty, tanpa harga
            $qty = $item->quantity . " pcs"; // Kuantitas dan satuan
            $note = $item->note ? $item->note : "-"; // Catatan atau tanda "-" jika tidak ada catatan

            // Menampilkan kuantitas dengan margin kanan
            $printer->text(str_pad($qty, 30) . str_pad($note, 16, ' ', STR_PAD_LEFT) . "\n");
        }

        $printer->text(str_repeat("-", 47) . "\n\n");

        $printer->feed(3); // Jarak sebelum potong kertas
        $printer->cut();
        $printer->close();
    }
}
