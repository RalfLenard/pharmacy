<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Distribution;
use App\Models\RecipientDistribution;
use Inertia\Inertia;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\View;
use App\Models\Inventory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{


    public function index()
    {
        $user = Auth::user();

        // Common chart data (only for admin)
        $byGender = RecipientDistribution::with('recipient')
            ->get()
            ->groupBy(fn($record) => $record->recipient->gender ?? 'Unknown')
            ->map(fn($group) => $group->sum('quantity'));

        $byBarangay = RecipientDistribution::with('recipient')
            ->get()
            ->groupBy(fn($record) => $record->recipient->barangay ?? 'Unknown')
            ->map(fn($group) => $group->sum('quantity'));

        $byMedicine = RecipientDistribution::with('distribution.inventory')
            ->get()
            ->groupBy(function ($record) {
                $inventory = $record->distribution->inventory ?? null;
                return $inventory ? "{$inventory->brand_name} ({$inventory->generic_name})" : 'Unknown';
            })
            ->map(fn($group) => $group->sum('quantity'));

        $inventoryLevels = DB::table('inventories')
            ->select(DB::raw("CONCAT(lot_number, ' - ', brand_name, ' (', generic_name, ')') as name"), 'stocks')
            ->orderBy('lot_number')
            ->get();

        // Medicines expiring within 3 months
        $expiringSoon = Inventory::whereDate('expiration_date', '<=', now()->addMonths(3))
            ->orderBy('expiration_date')
            ->get()
            ->groupBy('lot_number')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'medicines' => $group->map(function ($item) {
                        return [
                            'brand_name' => $item->brand_name,
                            'generic_name' => $item->generic_name,
                            'expiration_date' => $item->expiration_date->format('Y-m-d'),
                            'stocks' => $item->stocks,
                        ];
                    }),
                ];
            });

        if ($user->usertype === 'admin') {
            return Inertia::render('Dashboard', [
                'charts' => [
                    'byGender' => $byGender,
                    'byBarangay' => $byBarangay,
                    'byMedicine' => $byMedicine,
                    'inventoryLevels' => $inventoryLevels,
                    'expiringSoon' => $expiringSoon, // Include in dashboard
                ],
            ]);
        } else {
            return Inertia::render('Guest');
        }
    }


    public function generateInventoryReport($lot_number)
    {
        try {
            // Fetch inventories by lot number
            $inventories = Inventory::where('lot_number', $lot_number)->get();

            if ($inventories->isEmpty()) {
                return back()->with('error', 'No inventory found for this lot number.');
            }

            // Render the view to HTML
            $html = View::make('inventoryPdf', compact('inventories', 'lot_number'))->render();

            // Generate PDF using Browsershot
            $pdf = Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->pdf();

            // Stream the PDF to the browser
            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="inventory_lot_' . $lot_number . '.pdf"',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function checkInventoryLot($lot_number)
    {
        $exists = Inventory::where('lot_number', $lot_number)->exists();

        return response()->json(['exists' => $exists]);
    }



    public function generateByRemarks($remarks)
    {
        try {
            // Get the lot number from the query parameter if it exists
            $lotNumber = request()->query('lot_number');

            // Query distributions, filter by remarks and optionally by lot number
            $query = Distribution::with('inventory')
                ->where('remarks', $remarks);

            if ($lotNumber) {
                $query->whereHas('inventory', function ($q) use ($lotNumber) {
                    $q->where('lot_number', $lotNumber);
                });
            }

            $distributions = $query->get();

            if ($distributions->isEmpty()) {
                return back()->with('error', 'No distributions found for this remark and lot number combination.');
            }

            // Render HTML view
            $html = View::make('distributePdf', compact('distributions', 'remarks'))->render();

            // Generate PDF
            $pdf = Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->pdf();

            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="distribution_' . Str::slug($remarks) . '_' . now()->format('Ymd_His') . '.pdf"',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function checkDistributionByRemarks(Request $request)
    {
        $remarks = $request->query('remarks');
        $lotNumber = $request->query('lot_number');

        if (!$remarks) {
            return response()->json(['exists' => false], 400);
        }

        $query = Distribution::where('remarks', $remarks);

        if ($lotNumber) {
            $query->whereHas('inventory', function ($q) use ($lotNumber) {
                $q->where('lot_number', $lotNumber);
            });
        }

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }



    public function generateFilteredPDF(Request $request)
    {
        try {
            $query = RecipientDistribution::with(['recipient', 'distribution.inventory']);
    
            // Apply filters (medicine, lot_number, etc.)
            if ($request->filled('medicine')) {
                $query->whereHas('distribution.inventory', function ($q) use ($request) {
                    $q->where('generic_name', 'like', '%' . $request->medicine . '%')
                      ->orWhere('brand_name', 'like', '%' . $request->medicine . '%');
                });
            }
    
            if ($request->filled('brand_name')) {
                $query->whereHas('distribution.inventory', function ($q) use ($request) {
                    $q->where('brand_name', 'like', '%' . $request->brand_name . '%');
                });
            }
    
            if ($request->filled('generic_name')) {
                $query->whereHas('distribution.inventory', function ($q) use ($request) {
                    $q->where('generic_name', 'like', '%' . $request->generic_name . '%');
                });
            }
    
            if ($request->filled('lot_number')) {
                $query->whereHas('distribution.inventory', function ($q) use ($request) {
                    $q->where('lot_number', 'like', '%' . $request->lot_number . '%');
                });
            }
    
            if ($request->filled('barangay')) {
                $query->whereHas('recipient', function ($q) use ($request) {
                    $q->where('barangay', $request->barangay);
                });
            }
    
            if ($request->filled('gender')) {
                $query->whereHas('recipient', function ($q) use ($request) {
                    $q->where('gender', $request->gender);
                });
            }
    
            // Apply month and year filters using the model's scope
            if ($request->filled('month') && $request->filled('year')) {
                $month = (int) $request->month;
                $year = (int) $request->year;
            
                if ($month >= 1 && $month <= 12 && $year > 1900) {
                    $query->whereYear('date_given', $year)
                          ->whereMonth('date_given', $month);
                }
            }
            
    
            $records = $query->get();
    
            if ($records->isEmpty()) {
                return back()->with('error', 'No records found for the selected filters.');
            }
    
            $html = view('recipientPdf', compact('records', 'request'))->render();
    
            $pdf = Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->pdf();
    
            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="filtered_distribution_' . now()->format('Ymd_His') . '.pdf"'
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF. Please try again later.');
        }
    }
    

    public function checkFilteredPDF(Request $request)
    {
        $query = RecipientDistribution::with(['recipient', 'distribution.inventory']);
    
        // Apply filters (medicine, lot_number, etc.)
        if ($request->filled('medicine')) {
            $query->whereHas('distribution.inventory', function ($q) use ($request) {
                $q->where('generic_name', 'like', '%' . $request->medicine . '%')
                  ->orWhere('brand_name', 'like', '%' . $request->medicine . '%');
            });
        }
    
        if ($request->filled('brand_name')) {
            $query->whereHas('distribution.inventory', function ($q) use ($request) {
                $q->where('brand_name', 'like', '%' . $request->brand_name . '%');
            });
        }
    
        if ($request->filled('generic_name')) {
            $query->whereHas('distribution.inventory', function ($q) use ($request) {
                $q->where('generic_name', 'like', '%' . $request->generic_name . '%');
            });
        }
    
        if ($request->filled('lot_number')) {
            $query->whereHas('distribution.inventory', function ($q) use ($request) {
                $q->where('lot_number', 'like', '%' . $request->lot_number . '%');
            });
        }
    
        if ($request->filled('barangay')) {
            $query->whereHas('recipient', function ($q) use ($request) {
                $q->where('barangay', $request->barangay);
            });
        }
    
        if ($request->filled('gender')) {
            $query->whereHas('recipient', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }
    
        // Apply month and year filters using the model's scope
        if ($request->filled('month') && $request->filled('year')) {
            $month = (int) $request->month;
            $year = (int) $request->year;
        
            if ($month >= 1 && $month <= 12 && $year > 1900) {
                $query->whereYear('date_given', $year)
                      ->whereMonth('date_given', $month);
            }
        }
        
    
        $exists = $query->exists();
    
        return response()->json(['exists' => $exists]);
    }
    
    

}
