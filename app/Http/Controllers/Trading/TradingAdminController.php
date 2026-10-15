<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Models\Trading\MfOrder;
use App\Models\Trading\TradingAuditLog;
use App\Models\Trading\TradingClient;
use App\Models\Trading\TradingClientDocument;
use App\Models\Trading\TradingHoliday;
use App\Models\Trading\TradingMinorMajor;
use App\Services\Trading\KycService;
use App\Services\Trading\MarketDataSync;
use App\Services\Trading\OrderExporter;
use App\Services\Trading\OrderService;
use App\Services\Trading\TradingAudit;
use App\Services\Trading\TradingSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Back office for "Do Trading" (owner/admin only). */
class TradingAdminController extends Controller
{
    public function __construct(private TradingAudit $audit) {}

    public function dashboard(Request $request)
    {
        $today = now('Asia/Kolkata')->toDateString();
        $stats = [
            'clients' => TradingClient::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'orders_today' => MfOrder::whereDate('created_at', $today)->selectRaw('status, count(*) c, sum(amount) amt')->groupBy('status')->get()->keyBy('status'),
            'pending_minor_major' => TradingMinorMajor::whereNotIn('status', ['completed', 'rejected'])->count(),
            'schemes' => DB::table('mf_schemes')->where('is_active', true)->count(),
            'nav_date' => DB::table('mf_schemes')->max('nav_date'),
        ];
        $clients = TradingClient::with('user:id,name,email')->when($request->query('status', 'submitted'), fn ($q, $s) => $s === 'all' ? $q : $q->where('status', $s))
            ->latest('updated_at')->paginate(25)->withQueryString();
        return view('trading.admin.dashboard', compact('stats', 'clients'));
    }

    public function client(TradingClient $client)
    {
        $client->load(['user:id,name,email', 'nominees', 'jointHolders', 'documents', 'verifications', 'orders' => fn ($q) => $q->latest('id')->limit(20), 'holdings.scheme']);
        $this->audit->log('admin.client_viewed', $client);
        return view('trading.admin.client', ['client' => $client, 'documents' => TradingOnboardingController::DOCUMENTS,
            'minorMajor' => TradingMinorMajor::where('trading_client_id', $client->id)->latest('id')->get()]);
    }

    /** Manual result for a KYC check (PAN, Aadhaar, bank, address, mobile). */
    public function verifyCheck(Request $request, TradingClient $client, KycService $kyc)
    {
        $data = $request->validate(['check' => ['required', Rule::in(['pan', 'aadhaar', 'bank', 'address', 'mobile'])], 'result' => ['required', Rule::in(['passed', 'failed'])], 'note' => 'nullable|string|max:200']);
        $kyc->record($client, $data['check'], 'manual', $data['result'], $data['note'] ?? 'Checked by back office');
        if ($data['result'] === 'passed') {
            $column = ['pan' => 'pan_verified_at', 'aadhaar' => 'aadhaar_verified_at', 'bank' => 'bank_verified_at', 'address' => 'address_verified_at', 'mobile' => 'mobile_verified_at'][$data['check']];
            $client->forceFill([$column => now()])->save();
        }
        return back()->with('success', ucfirst($data['check']) . ' marked ' . $data['result'] . '.');
    }

    public function reviewDocument(Request $request, TradingClientDocument $document)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'rejected'])], 'remarks' => 'nullable|string|max:200']);
        $document->forceFill(['status' => $data['status'], 'remarks' => $data['remarks'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()])->save();
        $this->audit->log('admin.document_' . $data['status'], $document, ['type' => $document->type], $document->trading_client_id);
        return back()->with('success', 'Document ' . $data['status'] . '.');
    }

    public function decide(Request $request, TradingClient $client)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['verified', 'rejected', 'suspended'])], 'reason' => 'required_unless:decision,verified|nullable|string|max:250']);
        if ($data['decision'] === 'verified') {
            $missing = collect(['pan_verified_at' => 'PAN', 'aadhaar_verified_at' => 'Aadhaar', 'bank_verified_at' => 'bank account', 'email_verified_at' => 'email'])
                ->filter(fn ($label, $column) => !$client->{$column})->values()->all();
            if ($missing) return back()->withErrors(['decision' => 'Verify first: ' . implode(', ', $missing) . '.']);
        }
        $client->forceFill(['status' => $data['decision'], 'rejection_reason' => $data['reason'] ?? null, 'verified_by' => $request->user()->id, 'verified_at' => $data['decision'] === 'verified' ? now() : $client->verified_at])->save();
        $this->audit->log('admin.client_' . $data['decision'], $client, ['reason' => $data['reason'] ?? null]);
        try {
            \Illuminate\Support\Facades\Mail::raw($data['decision'] === 'verified'
                ? "Your Do Trading account {$client->client_code} is verified. You can now invest in mutual funds."
                : "Your Do Trading account {$client->client_code} needs attention: " . ($data['reason'] ?? ''), fn ($m) => $m->to($client->email ?? $client->user->email)->subject('Do Trading account ' . $data['decision']));
        } catch (\Throwable $e) {
            report($e);
        }
        return back()->with('success', 'Client ' . $data['decision'] . '.');
    }

    public function minorMajor(Request $request, TradingMinorMajor $minorMajor, KycService $kyc)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'completed', 'rejected'])], 'remarks' => 'nullable|string|max:200']);
        DB::transaction(function () use ($minorMajor, $data, $request) {
            $minorMajor->forceFill(['status' => $data['status'], 'remarks' => $data['remarks'] ?? null, 'processed_by' => $request->user()->id,
                'completed_at' => $data['status'] === 'completed' ? now() : null])->save();
            if ($data['status'] === 'completed') {
                $client = TradingClient::findOrFail($minorMajor->trading_client_id);
                $client->forceFill(['is_minor' => false, 'guardian_name' => null, 'guardian_relation' => null, 'guardian_pan' => null, 'guardian_dob' => null,
                    'pan' => $minorMajor->new_pan, 'pan_hash' => TradingClient::hashOf($minorMajor->new_pan), 'bank_account' => $minorMajor->new_bank_account,
                    'bank_account_hash' => TradingClient::hashOf($minorMajor->new_bank_account), 'bank_account_last4' => substr($minorMajor->new_bank_account, -4), 'ifsc' => $minorMajor->new_ifsc])->save();
            }
        });
        $this->audit->log('admin.minor_major_' . $data['status'], $minorMajor, [], $minorMajor->trading_client_id);
        return back()->with('success', 'Minor to major request ' . $data['status'] . '.');
    }

    public function orders(Request $request)
    {
        $orders = $this->orderQuery($request)->paginate(50)->withQueryString();
        return view('trading.admin.orders', ['orders' => $orders, 'statuses' => array_keys(OrderService::TRANSITIONS)]);
    }

    public function updateOrder(Request $request, MfOrder $order, OrderService $service)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(OrderService::TRANSITIONS))], 'note' => 'nullable|string|max:250', 'nav' => 'nullable|numeric|min:0.0001']);
        if ($data['status'] === 'allotted') {
            if (!isset($data['nav'])) return back()->withErrors(['nav' => 'NAV is required to allot.']);
            $service->allot($order, (float) $data['nav'], $order->applicable_nav_date->toDateString());
        } else {
            $service->transition($order, $data['status'], 'admin', $data['note'] ?? 'Updated by back office');
        }
        return back()->with('success', 'Order ' . $order->order_no . ' updated.');
    }

    /** All orders for a date range as Excel or PDF (end-of-day export). */
    public function export(Request $request, string $format, OrderExporter $exporter)
    {
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $orders = $this->orderQuery($request)->limit(20000)->get();
        $this->audit->log('admin.orders_exported', null, ['format' => $format, 'count' => $orders->count(), 'from' => $request->query('from'), 'to' => $request->query('to')]);
        $label = ($request->query('from') ?: 'all') . '_' . ($request->query('to') ?: now('Asia/Kolkata')->toDateString());
        $rows = $exporter->rows($orders);
        $summary = ['Orders' => $orders->count(), 'Buy amount (₹)' => number_format($orders->where('side', 'buy')->sum('amount'), 2), 'Fees (₹)' => number_format($orders->sum('total_fees'), 2)];
        return $format === 'xlsx'
            ? response()->download($exporter->xlsx($rows, 'Orders'), 'orders_' . $label . '.xlsx')->deleteFileAfterSend()
            : $exporter->pdf($rows, 'Orders ' . str_replace('_', ' to ', $label), $summary)->download('orders_' . $label . '.pdf');
    }

    public function settings(TradingSettings $settings)
    {
        return view('trading.admin.settings', ['values' => $settings->all(), 'defaults' => TradingSettings::DEFAULTS,
            'holidays' => TradingHoliday::orderBy('holiday_on')->whereYear('holiday_on', '>=', now()->year)->get()]);
    }

    public function saveSettings(Request $request, TradingSettings $settings)
    {
        $data = $request->validate(['settings' => 'required|array', 'settings.*' => 'nullable|string|max:200']);
        foreach ($data['settings'] as $key => $value) {
            if (!array_key_exists($key, TradingSettings::DEFAULTS)) continue;
            $type = TradingSettings::DEFAULTS[$key][2];
            if (in_array($type, ['number', 'percent'], true) && !is_numeric($value)) return back()->withErrors([$key => TradingSettings::DEFAULTS[$key][1] . ' must be a number.']);
            if ($type === 'time' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value)) return back()->withErrors([$key => TradingSettings::DEFAULTS[$key][1] . ' must be HH:MM.']);
            $settings->set($key, $value);
        }
        $this->audit->log('admin.settings_saved', null, ['keys' => array_keys($data['settings'])]);
        return back()->with('success', 'Settings saved.');
    }

    public function addHoliday(Request $request)
    {
        $data = $request->validate(['holiday_on' => 'required|date', 'description' => 'required|string|max:120']);
        TradingHoliday::updateOrCreate(['holiday_on' => $data['holiday_on'], 'segment' => 'CM'], ['description' => $data['description'], 'source' => 'manual']);
        cache()->forget('trading:holidays');
        $this->audit->log('admin.holiday_added', null, $data);
        return back()->with('success', 'Holiday added.');
    }

    public function syncMarket(MarketDataSync $sync)
    {
        $out = [];
        foreach (['NAVs' => fn () => implode(' / ', $sync->syncNavs()), 'Holidays' => fn () => $sync->syncHolidays(), 'Margins' => fn () => $sync->syncMargins()] as $label => $fn) {
            try { $out[] = $label . ': ' . $fn(); } catch (\Throwable $e) { $out[] = $label . ' failed: ' . $e->getMessage(); }
        }
        $this->audit->log('admin.market_synced', null, ['result' => $out]);
        return back()->with('success', implode(' · ', $out));
    }

    public function audit(Request $request)
    {
        $logs = TradingAuditLog::query()->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a . '%'))
            ->when($request->query('client'), fn ($q, $c) => $q->where('trading_client_id', $c))->latest('id')->paginate(100)->withQueryString();
        return view('trading.admin.audit', compact('logs'));
    }

    private function orderQuery(Request $request)
    {
        return MfOrder::with(['scheme:scheme_code,name,plan,option', 'client:id,client_code,full_name'])
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('order_no', $s)->orWhereHas('client', fn ($c) => $c->where('client_code', $s))))
            ->latest('id');
    }
}
