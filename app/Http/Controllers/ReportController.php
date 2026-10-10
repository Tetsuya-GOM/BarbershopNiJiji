<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $from = Carbon::parse(request('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse(request('to', now()->toDateString()))->endOfDay();
        $transactions = PaymentTransaction::with('payment.appointment.customer')->whereBetween('paid_at', [$from, $to]);
        $summary = ['revenue' => (clone $transactions)->sum('amount'), 'transactions' => (clone $transactions)->count(), 'appointments' => Appointment::whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])->count(), 'completed' => Appointment::whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])->where('status', 'completed')->count()];
        $summary['daily'] = PaymentTransaction::whereDate('paid_at', today())->sum('amount');
        $summary['weekly'] = PaymentTransaction::whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount');
        $summary['monthly'] = PaymentTransaction::whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $popular = Appointment::select('service_name_snapshot', DB::raw('count(*) total'))->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])->where('status', 'completed')->groupBy('service_name_snapshot')->orderByDesc('total')->limit(10)->get();
        $nameSql = DB::connection()->getDriverName() === 'mysql' ? "CONCAT(barbers.first_name, ' ', barbers.last_name)" : "barbers.first_name || ' ' || barbers.last_name";
        $barbers = Appointment::join('barbers', 'appointments.barber_id', '=', 'barbers.id')->selectRaw("$nameSql as barber_name, count(*) total")->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])->where('appointments.status', 'completed')->groupBy('barbers.id', 'barbers.first_name', 'barbers.last_name')->orderByDesc('total')->get();
        $rows = $transactions->orderByDesc('paid_at')->paginate(20)->withQueryString();

        return view('reports.index', compact('from', 'to', 'summary', 'popular', 'barbers', 'rows'));
    }
}
