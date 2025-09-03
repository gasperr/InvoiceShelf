<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\Invoice;
use Illuminate\Http\Request;
use ZipArchive;
use Carbon\Carbon;
use function Laravel\Prompts\outro;

class InvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $limit = $request->input('limit', 10);

        $invoices = Invoice::whereCompany()
            ->applyFilters($request->all())
            ->with('customer')
            ->latest()
            ->paginateData($limit);

        return InvoiceResource::collection($invoices)
            ->additional(['meta' => [
                'invoice_total_count' => Invoice::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\InvoicesRequest $request)
    {
        $this->authorize('create', Invoice::class);

        $invoice = Invoice::createInvoice($request);

        if ($request->has('invoiceSend')) {
            $invoice->send($request->subject, $request->body);
        }

        GenerateInvoicePdfJob::dispatch($invoice);

        return new InvoiceResource($invoice);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\InvoicesRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $invoice = $invoice->updateInvoice($request);

        if (is_string($invoice)) {
            return respondJson($invoice, $invoice);
        }

        GenerateInvoicePdfJob::dispatch($invoice, true);

        return new InvoiceResource($invoice);
    }

    /**
     * delete the specified resources in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteInvoiceRequest $request)
    {
        $this->authorize('delete multiple invoices');

        Invoice::deleteInvoices($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Download current month invoices as zip.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadCurrentMonthInvoices()
    {
        $this->authorize('viewAny', Invoice::class);

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $invoices = Invoice::whereCompany()
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['message' => 'No invoices found for current month'], 404);
        }

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive;
        $fileName = 'invoices_' . Carbon::now()->format('Y_m') . '.zip';
        $tempPath = storage_path('app/temp/' . $fileName);

        $result = $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            return response()->json(['message' => 'Could not create ZIP file'], 500);
        }

        $addedFiles = 0;
        
        foreach ($invoices as $invoice) {
            // Generate or get existing PDF
            $pdfResponse = $invoice->getGeneratedPDFOrStream('invoice');

            // Get PDF content from the response
            if ($pdfResponse) {
                try {
                    $pdfContent = $pdfResponse->getContent();
                    if ($pdfContent) {
                        $zip->addFromString('invoice_' . $invoice->invoice_number . '.pdf', $pdfContent);
                        $addedFiles++;
                    }
                } catch (\Exception $e) {
                    // Skip this invoice if PDF can't be generated
                    continue;
                }
            }
        }
        
        $zip->close();

        if ($addedFiles === 0) {
            return response()->json(['message' => 'No PDF files could be generated'], 404);
        }

        if (!file_exists($tempPath)) {
            return response()->json(['message' => 'ZIP file was not created'], 500);
        }

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
