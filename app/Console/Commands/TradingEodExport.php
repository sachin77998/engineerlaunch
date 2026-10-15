<?php

namespace App\Console\Commands;

use App\Models\Trading\MfOrder;
use App\Services\Trading\OrderExporter;
use App\Services\Trading\TradingSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TradingEodExport extends Command
{
    protected $signature = 'trading:eod-export {--date= : Day to export (default today, IST)}';
    protected $description = "End-of-day export of all orders to Excel and PDF (private storage; emailed when trading setting export.email is set)";

    public function handle(OrderExporter $exporter, TradingSettings $settings): int
    {
        $day = $this->option('date') ?: now('Asia/Kolkata')->toDateString();
        $orders = MfOrder::with(['scheme:scheme_code,name,plan,option', 'client:id,client_code'])->whereDate('created_at', $day)->orderBy('id')->get();
        $rows = $exporter->rows($orders);
        $dir = 'trading-exports/' . substr($day, 0, 7);
        $xlsx = $dir . '/orders_' . $day . '.xlsx';
        $pdf = $dir . '/orders_' . $day . '.pdf';
        Storage::disk('local')->put($xlsx, file_get_contents($tmp = $exporter->xlsx($rows, 'Orders ' . $day)));
        @unlink($tmp);
        $summary = ['Orders' => $orders->count(), 'Buy amount (₹)' => number_format($orders->where('side', 'buy')->sum('amount'), 2), 'Fees (₹)' => number_format($orders->sum('total_fees'), 2)];
        Storage::disk('local')->put($pdf, $exporter->pdf($rows, 'Orders ' . $day, $summary)->output());
        $this->info("{$orders->count()} orders exported to storage/app/{$xlsx} and .pdf");
        if ($to = $settings->get('export.email')) {
            Mail::raw("Do Trading orders for {$day}: {$orders->count()} orders. Excel and PDF attached.", function ($m) use ($to, $day, $xlsx, $pdf) {
                $m->to($to)->subject('Do Trading orders ' . $day)
                    ->attach(Storage::disk('local')->path($xlsx))->attach(Storage::disk('local')->path($pdf));
            });
            $this->info('Emailed to ' . $to);
        }
        return self::SUCCESS;
    }
}
