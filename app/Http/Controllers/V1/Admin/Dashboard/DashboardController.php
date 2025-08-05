<?php

namespace App\Http\Controllers\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;
use function Laravel\Prompts\outro;
use function Pest\Laravel\get;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('view dashboard', $company);

        $invoice_totals = [];
        $expense_totals = [];
        $tax_totals = [];
        $receipt_totals = [];
        $net_income_totals = [];
        $net_income_totals_prev_year = [];
        $receipt_totals_prev_year = [];
        $tax_totals_prev_year = [];

        $i = 0;
        $months = [];
        $monthCounter = 0;
        $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
        $startDate = Carbon::now();
        $start = Carbon::now();
        $end = Carbon::now();
        $terms = explode('-', $fiscalYear);
        $companyStartMonth = intval($terms[0]);

        if ($companyStartMonth <= $start->month) {
            $startDate->month($companyStartMonth)->startOfMonth();
            $start->month($companyStartMonth)->startOfMonth();
            $end->month($companyStartMonth)->endOfMonth();
        } else {
            $startDate->subYear()->month($companyStartMonth)->startOfMonth();
            $start->subYear()->month($companyStartMonth)->startOfMonth();
            $end->subYear()->month($companyStartMonth)->endOfMonth();
        }

        if ($request->has('year')) {
            $year = $request->year;

            $baseDate = Carbon::createFromDate($year, 1, 1);

            $startDate = $baseDate;

            $start = $baseDate->copy()->startOfMonth(); // Jan 1, 2025
            $end = $baseDate->copy()->endOfMonth();   // Jan 31, 2025

        }

        while ($monthCounter < 12) {
            array_push(
                $invoice_totals,
                Invoice::whereBetween(
                    'invoice_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->sum('base_total')
            );
            array_push(
                $expense_totals,
                Expense::whereBetween(
                    'expense_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->sum('base_amount')
            );
            array_push(
                $tax_totals,
                Invoice::whereBetween(
                    'invoice_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->sum('tax')
            );
            array_push(
                $receipt_totals,
                Payment::whereBetween(
                    'payment_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->sum('base_amount')
            );

            array_push(
                $receipt_totals_prev_year,
                Payment::whereBetween(
                    'payment_date',
                    [$start->copy()->subYear()->format('Y-m-d'), $end->copy()->subYear()->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->sum('base_amount')
            );
            array_push(
                $tax_totals_prev_year,
                Invoice::whereBetween(
                    'invoice_date',
                    [$start->copy()->subYear()->format('Y-m-d'), $end->copy()->subYear()->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->sum('tax')
            );
            array_push(
                $net_income_totals,
                ($receipt_totals[$i] - $tax_totals[$i])
            );
            outro($tax_totals_prev_year[$i]);
            array_push(
                $net_income_totals_prev_year,
                ($receipt_totals_prev_year[$i] - $tax_totals_prev_year[$i])
            );
            $i++;
            array_push($months, $start->translatedFormat('M'));
            $monthCounter++;
            $end->startOfMonth();
            $start->addMonth()->startOfMonth();
            $end->addMonth()->endOfMonth();
        }

        $start->subMonth()->endOfMonth();

        $total_sales = Invoice::whereBetween(
            'invoice_date',
            [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('base_total');

        $total_tax = Invoice::whereBetween(
            'invoice_date',
            [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('tax');

        $total_receipts = Payment::whereBetween(
            'payment_date',
            [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('base_amount');


        $total_net_income = (int) $total_receipts - (int) $total_tax;

        $chart_data = [
            'months' => $months,
            'invoice_totals' => $invoice_totals,
            'expense_totals' => $tax_totals,
            'receipt_totals' => $receipt_totals,
            'net_income_totals' => $net_income_totals,
            'net_income_totals_prev_year' => $net_income_totals_prev_year,
        ];
        outro(json_encode($chart_data));

//        $total_customer_count = Customer::whereCompany()->count();

        $recurringInvoices = RecurringInvoice::whereCompany()->get();
        $total_customer_count = $recurringInvoices->sum('total');

        $total_invoice_count = Invoice::whereCompany()
            ->count();

        $total_overall_net_income = Invoice::whereCompany()
            ->sum('total') - Invoice::whereCompany()
                ->sum('tax');

        // First day of prev month
        $firstDayLastMonth = (new \DateTime('first day of last month'))->format('Y-m-d');

        // Last day of prev month
        $lastDayLastMonth = (new \DateTime('last day of last month'))->format('Y-m-d');


        // First day of this month
        $firstDayThisMonth = (new \DateTime('first day of this month'))->format('Y-m-d');

        // Last day of this month
        $lastDayThisMonth = (new \DateTime('last day of this month'))->format('Y-m-d');


        $total_this_month = Invoice::whereBetween(
            'invoice_date',
            [$firstDayThisMonth, $lastDayThisMonth]
        )
            ->whereCompany()
            ->sum('total');
        $tax_this_month = Invoice::whereBetween(
            'invoice_date',
            [$firstDayThisMonth, $lastDayThisMonth]
        )
            ->whereCompany()
            ->sum('tax');

        $total_amount_due = $total_this_month - $tax_this_month;

        $total_previous_month = Invoice::whereBetween(
            'invoice_date',
            [$firstDayLastMonth, $lastDayLastMonth]
        )
            ->whereCompany()
            ->sum('total');
        $tax_previous_month = Invoice::whereBetween(
            'invoice_date',
            [$firstDayLastMonth, $lastDayLastMonth]
        )
            ->whereCompany()
            ->sum('tax');

        $total_net_previous_month = $total_previous_month - $tax_previous_month;

        $recent_due_invoices = Invoice::with('customer')
            ->whereCompany()
            ->where('base_due_amount', '>', 0)
            ->take(5)
            ->latest()
            ->get();
        $recent_estimates = Estimate::with('customer')->whereCompany()->take(5)->latest()->get();

        return response()->json([
            'total_amount_due' => $total_amount_due,
            'total_overall_net_income' => $total_overall_net_income,
            'total_net_previous_month' => $total_net_previous_month,
            'total_customer_count' => $total_customer_count,
            'total_invoice_count' => $total_invoice_count,

            'recent_due_invoices' => BouncerFacade::can('view-invoice', Invoice::class) ? $recent_due_invoices : [],

            'chart_data' => $chart_data,

            'total_sales' => $total_sales,
            'total_tax' => $total_tax,
            'total_receipts' => $total_receipts,
            'total_net_income' => $total_net_income,
        ]);
    }
}
