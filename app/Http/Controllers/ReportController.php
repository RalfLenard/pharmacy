<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use Carbon\Carbon;
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
            $lot_number = $request->input('lot_number');
            $month = $request->input('month');
            $year = $request->input('year');
            $stock_type = $request->input('stock_type');
            $date = $request->input('date');
            $preparedBy = $request->input('prepared_by'); // 🔥 new line
    
            $query = Inventory::query();
    
            if ($lot_number) {
                $query->where('lot_number', $lot_number);
            }
    
            if ($date) {
                $query->whereDate('date_in', $date);
            } elseif ($month && $year) {
                $query->whereMonth('date_in', $month)
                    ->whereYear('date_in', $year);
            } elseif ($year) {
                $query->whereYear('date_in', $year);
            }
    
            if ($stock_type) {
                $query->where('stock_type', $stock_type);
            }
    
            $inventories = $query->orderBy('date_in', 'desc')->get();
    
            if ($inventories->isEmpty()) {
                if ($date) {
                    return back()->with('error', 'No inventory records found for ' . Carbon::parse($date)->format('F j, Y') . '.');
                } elseif ($month && $year) {
                    return back()->with('error', 'No inventory records found for ' . Carbon::create()->month($month)->format('F') . " $year.");
                } elseif ($year) {
                    return back()->with('error', "No inventory records found for $year.");
                } elseif ($lot_number) {
                    return back()->with('error', "No inventory found for Lot #$lot_number.");
                }
    
                return back()->with('error', 'No inventory found matching the provided filters.');
            }
    
            $title = 'Inventory Report';
            if ($stock_type) $title .= " - $stock_type";
            if ($lot_number) $title .= " - Lot #$lot_number";
            if ($date) {
                $title .= " for " . Carbon::parse($date)->format('F j, Y');
            } elseif ($month && $year) {
                $title .= " for " . Carbon::create()->month($month)->format('F') . " $year";
            } elseif ($year) {
                $title .= " for $year";
            }
    
            $html = View::make('inventoryPdf', compact('inventories', 'title', 'preparedBy'))->render();
    
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
            \Log::error('PDF Generation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An unexpected error occurred while generating the PDF report. Please contact the administrator.');
        }
    }
    

    public function checkInventoryLot(Request $request)
    {
        try {
            $lot_number = $request->input('lot_number');
            $month = $request->input('month');
            $year = $request->input('year');
            $stock_type = $request->input('stock_type');
            $date = $request->input('date');

            $query = Inventory::query();

            if ($lot_number) {
                $query->where('lot_number', $lot_number);
            }

            if ($stock_type) {
                $query->where('stock_type', $stock_type);
            }

            if ($date) {
                $query->whereDate('date_in', $date);
            } elseif ($month && $year) {
                $query->whereMonth('date_in', $month)
                    ->whereYear('date_in', $year);
            } elseif ($year) {
                $query->whereYear('date_in', $year);
            }

            $exists = $query->exists();

            if (!$exists) {
                $message = 'No inventory match found';
                if ($date) {
                    $message .= ' for ' . Carbon::parse($date)->format('F j, Y');
                } elseif ($month && $year) {
                    $message .= ' for ' . Carbon::create()->month($month)->format('F') . " $year";
                } elseif ($year) {
                    $message .= " for $year";
                } elseif ($lot_number) {
                    $message .= " for Lot #$lot_number";
                }

                return response()->json([
                    'exists' => false,
                    'message' => $message . '.',
                ]);
            }

            return response()->json(['exists' => true]);
        } catch (\Exception $e) {
            \Log::error('Inventory Check Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred while checking the inventory. Please try again later.'], 500);
        }
    }




    public function generateByRemarks(Request $request, $remarks = null)
    {
        try {
            $stockType = $request->query('stock_type');
            $month = $request->query('month');
            $year = $request->query('year');
            $exactDate = $request->query('date');
            $preparedBy = $request->input('prepared_by'); // new line to get prepared_by
    
            $month = !empty($month) ? (int) $month : null;
            $year = !empty($year) ? (int) $year : null;
    
            $query = Distribution::with('inventory');
    
            if ($remarks && strtolower($remarks) !== 'all') {
                $query->where('remarks', $remarks);
            }
    
            if ($stockType) {
                $query->whereHas('inventory', function ($q) use ($stockType) {
                    $q->where('stock_type', $stockType);
                });
            }
    
            if ($exactDate) {
                $query->whereDate('date_distribute', $exactDate);
            } elseif ($month && $year && $month >= 1 && $month <= 12) {
                $query->whereMonth('date_distribute', $month)
                      ->whereYear('date_distribute', $year);
            } elseif ($year) {
                $query->whereYear('date_distribute', $year);
            }
    
            $distributions = $query->get();
    
            if ($distributions->isEmpty()) {
                return back()->with('error', 'No distributions found for the selected filters.');
            }
    
            // Save UUID to DB
            $reportId = substr((string) Str::uuid(), 0, 8);
    
            GeneratedReport::create([
                'report_id' => $reportId,
            ]);
    
            // Pass prepared_by to the view
            $html = View::make('distributePdf', [
                'distributions' => $distributions,
                'remarks' => $remarks ?? 'All',
                'stock_type' => $stockType ?? 'All',
                'month' => $month,
                'year' => $year,
                'date' => $exactDate,
                'report_id' => $reportId,
                'prepared_by' => $preparedBy, // pass it here
            ])->render();
    
            $timestamp = now()->format('Ymd_His');
    
            $filename = 'distribution_' . Str::slug($remarks ?? 'all') . '_' .
                        Str::slug($stockType ?? 'all') . '_' .
                        ($exactDate ? Str::slug($exactDate) : ($month ? $month . '-' . $year : ($year ?? 'all'))) .
                        '_' . $timestamp . '.pdf';
    
            $pdf = Browsershot::html($html)
                    ->format('A4')
                    ->margins(10, 10, 10, 10)
                    ->showBackground()
                    ->pdf();
    
            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
    


    public function checkDistributionByRemarks(Request $request)
    {
        $remarks = $request->query('remarks');
        $stockType = $request->query('stock_type');
        $month = $request->query('month');
        $year = $request->query('year');
        $exactDate = $request->query('date'); // ✅ NEW

        $month = !empty($month) ? (int) $month : null;
        $year = !empty($year) ? (int) $year : null;

        $query = Distribution::query();

        if ($remarks && strtolower($remarks) !== 'all') {
            $query->where('remarks', $remarks);
        }

        if ($stockType) {
            $query->whereHas('inventory', function ($q) use ($stockType) {
                $q->where('stock_type', $stockType);
            });
        }

        if ($exactDate) {
            $query->whereDate('date_distribute', $exactDate);
        } elseif ($month && $year) {
            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('date_distribute', $month)
                    ->whereYear('date_distribute', $year);
            }
        } elseif ($year) {
            $query->whereYear('date_distribute', $year);
        }

        return response()->json([
            'exists' => $query->exists(),
            'message' => $query->exists() ? null : 'No distributions found for the given filters.'
        ]);
    }



    // public function generateFilteredPDF(Request $request)
    // {
    //     try {
    //         $query = RecipientDistribution::with(['recipient', 'distribution.inventory']);

    //         $this->applyCommonFilters($query, $request);

    //         $records = $query->get();

    //         if ($records->isEmpty()) {
    //             return back()->with('error', 'No records found for the selected filters.');
    //         }

    //         $html = view('recipientPdf', compact('records', 'request'))->render();

    //         $pdf = Browsershot::html($html)
    //             ->format('A4')
    //             ->margins(10, 10, 10, 10)
    //             ->showBackground()
    //             ->pdf();

    //         return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($pdf) {
    //             echo $pdf;
    //         }, 200, [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'inline; filename="filtered_distribution_' . now()->format('Ymd_His') . '.pdf"',
    //         ]);
    //     } catch (\Exception $e) {
    //         return back()->with('error', 'Failed to generate PDF. Please try again later.');
    //     }
    // }

    public function generateFilteredPDF(Request $request)
    {
        try {
            $preparedBy = $request->input('prepared_by'); // <-- Get prepared_by from request
    
            $query = RecipientDistribution::with(['recipient', 'distribution.inventory']);
    
            $this->applyCommonFilters($query, $request);
    
            $records = $query->get();
    
            if ($records->isEmpty()) {
                return back()->with('error', 'No records found for the selected filters.');
            }
    
            // Pass prepared_by to view
            $html = view('recipientPdf', compact('records', 'request', 'preparedBy'))->render();
    
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
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
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
        $startDate = $request->input('start_date'); // format: Y-m-d
        $endDate = $request->input('end_date');
        $exactDate = $request->input('date');
        $month = $request->filled('month') ? (int) $request->month : null;
        $year = $request->filled('year') ? (int) $request->year : null;
    
        if ($startDate && $endDate) {
            $query->whereBetween('date_given', [$startDate, $endDate]);
        } elseif ($exactDate) {
            $query->whereDate('date_given', $exactDate);
        } elseif ($month && $year && $month >= 1 && $year > 1900) {
            $query->whereMonth('date_given', $month)
                  ->whereYear('date_given', $year);
        }
    }
    
    public function checkFilteredPDF(Request $request)
    {
        $query = RecipientDistribution::query();
    
        $this->applyCommonFilters($query, $request);
    
        $exists = $query->exists();
    
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? null : 'No records found for the selected filters.'
        ]);
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