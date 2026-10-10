<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = request()->user();
        $appointments = Appointment::query()->visibleTo($user);
        $stats = ['customers' => Customer::count(), 'barbers' => Barber::active()->count(), 'services' => Service::available()->count(),
            'appointments' => (clone $appointments)->count(), 'today' => (clone $appointments)->whereDate('appointment_date', today())->count(),
            'completed' => (clone $appointments)->where('status', 'completed')->count(), 'revenue' => PaymentTransaction::sum('amount')];
        $recentAppointments = (clone $appointments)->with(['customer', 'barber'])->latest()->limit(6)->get();
        $todayAppointments = (clone $appointments)->with(['customer', 'barber'])->whereDate('appointment_date', today())->orderBy('start_time')->get();
        $recentPayments = $user->isAdmin() ? PaymentTransaction::with('payment.appointment.customer')->latest('paid_at')->limit(5)->get() : collect();
        $statusOverview = (clone $appointments)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $periodSql = DB::connection()->getDriverName() === 'mysql' ? "DATE_FORMAT(paid_at, '%Y-%m')" : "strftime('%Y-%m', paid_at)";
        $revenueByMonth = PaymentTransaction::selectRaw("$periodSql period, sum(amount) total")
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('period')
            ->pluck('total', 'period');
        $monthlyRevenue = collect(range(5, 0))->map(function (int $monthsAgo) use ($revenueByMonth) {
            $month = now()->subMonths($monthsAgo);

            return [
                'period' => $month->format('Y-m'),
                'label' => $month->format('M'),
                'total' => (float) ($revenueByMonth[$month->format('Y-m')] ?? 0),
            ];
        });

        return view('dashboard', compact('stats', 'recentAppointments', 'todayAppointments', 'recentPayments', 'statusOverview', 'monthlyRevenue'));
    }
}
