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
use Illuminate\Support\Facades\Log;

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


    public function generateInventoryReport(Request $request)
    {
        try {
            $lot_number = $request->input('lot_number'); // optional
            $month = $request->input('month'); // optional
            $year = $request->input('year');   // optional

            $query = Inventory::query();

            if ($lot_number) {
                $query->where('lot_number', $lot_number);
            }

            if ($month && $year) {
                $query->whereMonth('date_in', $month)
                    ->whereYear('date_in', $year);
            } elseif ($year) {
                $query->whereYear('date_in', $year);
            }

            $inventories = $query->orderBy('date_in', 'desc')->get();

            if ($inventories->isEmpty()) {
                return back()->with('error', 'No inventory found for the specified filters.');
            }

            $title = 'Inventory Report';
            if ($lot_number) $title .= " - Lot #$lot_number";
            if ($month && $year) $title .= " for " . Carbon::create()->month($month)->format('F') . " $year";
            elseif ($year) $title .= " for $year";

            $html = View::make('inventoryPdf', compact('inventories', 'title'))->render();

            $pdf = Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->pdf();

            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="inventory_report.pdf"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function checkInventoryLot(Request $request)
    {
        $lot_number = $request->input('lot_number');
        $month = $request->input('month'); // optional
        $year = $request->input('year');   // optional

        $query = Inventory::query();

        if ($lot_number) {
            $query->where('lot_number', $lot_number);
        }

        if ($month && $year) {
            $query->whereMonth('date_in', $month)
                ->whereYear('date_in', $year);
        } elseif ($year) {
            $query->whereYear('date_in', $year);
        }

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }



    public function generateByRemarks(Request $request, $remarks = null)
    {
        try {
            // Get query parameters
            $lotNumber = $request->query('lot_number');
            $month     = $request->query('month');
            $year      = $request->query('year');

            // Sanitize and validate month
            $month = !empty($month) ? (int) $month : null;
            $year  = !empty($year) ? (int) $year : null;

            // Base query with relationship
            $query = Distribution::with('inventory');

            // Apply filters
            if ($remarks && strtolower($remarks) !== 'all') {
                $query->where('remarks', $remarks);
            }

            if ($lotNumber) {
                $query->whereHas('inventory', function ($q) use ($lotNumber) {
                    $q->where('lot_number', $lotNumber);
                });
            }

            if ($month && $year) {
                if ($month >= 1 && $month <= 12) {
                    $query->whereMonth('date_distribute', $month)
                        ->whereYear('date_distribute', $year);
                }
            } elseif ($year) {
                $query->whereYear('date_distribute', $year);
            }

            $distributions = $query->get();

            if ($distributions->isEmpty()) {
                return back()->with('error', 'No distributions found for the selected filters.');
            }

            // Generate HTML from view
            $html = View::make('distributePdf', [
                'distributions' => $distributions,
                'remarks'       => $remarks ?? 'All',
                'lot_number'    => $lotNumber ?? 'All',
                'month'         => $month,
                'year'          => $year,
            ])->render();

            // File name formatting
            $timestamp = now()->format('Ymd_His');
            $filename = 'distribution_' .
                Str::slug($remarks ?? 'all') . '_' .
                Str::slug($lotNumber ?? 'all') . '_' .
                ($month ? $month . '-' . $year : ($year ?? 'all')) . '_' .
                $timestamp . '.pdf';

            // Generate PDF
            $pdf = Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->pdf();

            // Stream the PDF
            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }


    public function checkDistributionByRemarks(Request $request)
    {
        $remarks   = $request->query('remarks');
        $lotNumber = $request->query('lot_number');
        $month     = $request->query('month');
        $year      = $request->query('year');

        // Sanitize and validate month/year
        $month = !empty($month) ? (int) $month : null;
        $year  = !empty($year) ? (int) $year : null;

        // Start query
        $query = Distribution::query();

        // Apply filters
        if ($remarks && strtolower($remarks) !== 'all') {
            $query->where('remarks', $remarks);
        }

        if ($lotNumber) {
            $query->whereHas('inventory', function ($q) use ($lotNumber) {
                $q->where('lot_number', $lotNumber);
            });
        }

        if ($month && $year) {
            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('date_distribute', $month)
                    ->whereYear('date_distribute', $year);
            }
        } elseif ($year) {
            $query->whereYear('date_distribute', $year);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }


    public function generateFilteredPDF(Request $request)
    {
        try {
            $query = RecipientDistribution::with(['recipient', 'distribution.inventory']);

            $this->applyCommonFilters($query, $request);

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

            return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="filtered_distribution_' . now()->format('Ymd_His') . '.pdf"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF. Please try again later.');
        }
    }

    private function applyCommonFilters($query, Request $request)
    {
        // Inventory Filters
        if ($request->filled('brand_name')) {
            $query->whereHas('distribution.inventory', fn($q) =>
            $q->where('brand_name', 'like', '%' . $request->brand_name . '%'));
        }

        if ($request->filled('generic_name')) {
            $query->whereHas('distribution.inventory', fn($q) =>
            $q->where('generic_name', 'like', '%' . $request->generic_name . '%'));
        }

        if ($request->filled('lot_number')) {
            $query->whereHas('distribution.inventory', fn($q) =>
            $q->where('lot_number', 'like', '%' . $request->lot_number . '%'));
        }

        // Recipient Filters
        if ($request->filled('barangay')) {
            $query->whereHas('recipient', fn($q) =>
            $q->where('barangay', $request->barangay));
        }

        if ($request->filled('gender')) {
            $query->whereHas('recipient', fn($q) =>
            $q->where('gender', $request->gender));
        }

        // Date Filters
        $month = $request->filled('month') ? (int) $request->month : null;
        $year = $request->filled('year') ? (int) $request->year : null;

        if ($month && $year && $month >= 1 && $month <= 12 && $year > 1900) {
            $query->whereMonth('date_given', $month)
                ->whereYear('date_given', $year);
        }
    }


    public function checkFilteredPDF(Request $request)
    {
        $query = RecipientDistribution::query();

        $this->applyCommonFilters($query, $request);

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }


    public function getAvailableMonths(Request $request)
    {
        $year = $request->input('year');
    
        $query = RecipientDistribution::query();
    
        if ($year) {
            $query->whereYear('date_given', $year);
        }
    
        $months = $query->selectRaw('DISTINCT MONTH(date_given) as month')
                        ->orderBy('month')
                        ->pluck('month');
    
        return response()->json(['months' => $months]);
    }
    
    
}
