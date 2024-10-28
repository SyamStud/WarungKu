<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Ads;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\Debugbar\Twig\Extension\Debug;

class AdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('SuperAdmin/AdsSlides');
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
        Debugbar::info($request->all());
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'image' => 'required',
            'logo' => 'required',
            'link' => 'required',
            'status' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $ads = new Ads();
        $ads->title = $request->title;
        $ads->description = $request->description;
        $ads->link = $request->link;
        $ads->status = $request->status;

        $image = $request->file('image');
        $logo = $request->file('logo');

        // Simpan gambar dan logo ke folder 'public/slide'
        $imagePath = $image->store('public/slide');
        $logoPath = $logo->store('public/slide');

        $ads->image = str_replace('public/', '', $imagePath);
        $ads->logo = str_replace('public/', '', $logoPath);

        $ads->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Slide berhasil ditambahkan',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ads $ads)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ads $ads)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ads $ads, $id)
    {
        $ads = Ads::find($id);

        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'link' => 'required',
            'status' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $ads->title = $request->title;
        $ads->description = $request->description;
        $ads->link = $request->link;
        $ads->status = $request->status;

        if ($request->hasFile('image')) {
            // Hapus file gambar lama
            if ($ads->image) {
                Storage::delete('public/' . $ads->image);
            }
            $image = $request->file('image');
            $imagePath = $image->store('public/slide');
            $ads->image = str_replace('public/', '', $imagePath);
        }

        if ($request->hasFile('logo')) {
            // Hapus file logo lama
            if ($ads->logo) {
                Storage::delete('public/' . $ads->logo);
            }
            $logo = $request->file('logo');
            $logoPath = $logo->store('public/slide');
            $ads->logo = str_replace('public/', '', $logoPath);
        }

        $ads->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Slide berhasil diperbarui',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ads $ads, $id)
    {
        $ads = Ads::find($id);
        $ads->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Slide berhasil dihapus',
        ]);
    }

    /**
     * Get all ads data.
     */
    public function adsData(Request $request)
    {
        $query = Ads::query();

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%");
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
        $ads = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($ads->currentPage() - 1) * $ads->perPage() + 1;
        $to = min($from + $ads->count() - 1, $ads->total());

        $transformedAds = $ads->map(function ($ads) {
            return [
                'id' => $ads->id,
                'title' => $ads->title,
                'description' => $ads->description,
                'image' => $ads->image,
                'logo' => $ads->logo,
                'link' => $ads->link,
                'status' => $ads->status,
                'created_at' => $ads->created_at->format('d F Y'),
            ];
        });

        return response()->json([
            'data' => $transformedAds,
            'meta' => [
                'current_page' => $ads->currentPage(),
                'last_page' => $ads->lastPage(),
                'per_page' => $ads->perPage(),
                'total' => $ads->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function indexReceipt()
    {
        return Inertia::render('SuperAdmin/AdsReceipt');
    }

    public function storeReceipt(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'sponsorType' => 'required',
            'sponsorName' => 'required',
            'sponsorDescription' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $ads = Ads::where('type', 'receipt')->first();

        if ($ads) {
            $ads->sponsor_type = $request->sponsorType;
            $ads->sponsor_name = $request->sponsorName;
            $ads->sponsor_description = $request->sponsorDescription;
            $ads->save();
        } else {
            $ads = new Ads();
            $ads->sponsor_type = $request->sponsorType;
            $ads->sponsor_name = $request->sponsorName;
            $ads->sponsor_description = $request->sponsorDescription;
            $ads->type = 'receipt';
            $ads->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Receipt berhasil ditambahkan',
        ]);
    }
}
