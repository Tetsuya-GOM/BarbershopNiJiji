<x-app-layout>
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-amber-600">OVERVIEW</p>
            <h1 class="text-3xl font-black">Good day, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
            <p class="mt-1 text-slate-500">Here is what is happening in the shop.</p>
        </div>
        @if(auth()->user()->isAdmin())
            <a class="btn-primary" href="{{ route('appointments.create') }}">+ New appointment</a>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Customers', $stats['customers'], 'C'],
            ['Active Barbers', $stats['barbers'], 'B'],
            ['Services', $stats['services'], 'S'],
            ['Appointments', $stats['appointments'], 'A'],
            ["Today's Schedule", $stats['today'], 'T'],
            ['Completed', $stats['completed'], 'OK'],
            ['Total Revenue', '&#8369;'.number_format($stats['revenue'], 2), '&#8369;'],
        ] as $card)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $card[0] }}</p>
                        <p class="mt-2 text-3xl font-black">{!! $card[1] !!}</p>
                    </div>
                    <div class="grid h-11 min-w-11 place-items-center rounded-xl bg-amber-100 px-2 text-sm font-bold text-amber-700">{!! $card[2] !!}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold">Today's schedule</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($todayAppointments as $appointment)
                    <a href="{{ route('appointments.show', $appointment) }}" class="flex items-center gap-4 p-5 hover:bg-slate-50">
                        <div class="w-20 font-bold text-amber-700">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</div>
                        <div class="flex-1">
                            <div class="font-semibold">{{ $appointment->customer->full_name }}</div>
                            <div class="text-sm text-slate-500">{{ $appointment->service_name_snapshot }} &middot; {{ $appointment->barber->full_name }}</div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize">{{ str_replace('_', ' ', $appointment->status) }}</span>
                    </a>
                @empty
                    <div class="p-10 text-center text-slate-500">No appointments scheduled today.</div>
                @endforelse
            </div>
        </section>

        <section class="card p-5">
            <h2 class="font-bold">Status overview</h2>
            <div class="mt-5 space-y-4">
                @foreach(\App\Models\Appointment::STATUSES as $status)
                    @php
                        $count = $statusOverview[$status] ?? 0;
                        $percentage = $stats['appointments'] ? ($count / $stats['appointments'] * 100) : 0;
                    @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span class="capitalize">{{ str_replace('_', ' ', $status) }}</span><b>{{ $count }}</b></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-amber-500" style="width: {{ $percentage }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card overflow-hidden">
            @php
                $maxRevenue = max(1, $monthlyRevenue->max('total'));
                $sixMonthTotal = $monthlyRevenue->sum('total');
            @endphp
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div>
                    <h2 class="font-bold">Monthly revenue</h2>
                    <p class="mt-1 text-xs text-slate-500">Payments collected during the last six months</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">6-month total</p>
                    <p class="text-lg font-black">&#8369;{{ number_format($sixMonthTotal, 2) }}</p>
                </div>
            </div>

            <div class="p-5">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-x-0 top-3 border-t border-dashed border-slate-200"></div>
                    <div class="pointer-events-none absolute inset-x-0 top-[35%] border-t border-dashed border-slate-200"></div>
                    <div class="pointer-events-none absolute inset-x-0 top-[68%] border-t border-dashed border-slate-200"></div>
                    <div class="relative flex h-56 items-end justify-around gap-3 border-b border-slate-200">
                        @foreach($monthlyRevenue as $month)
                            @php $barHeight = $month['total'] > 0 ? max(10, ($month['total'] / $maxRevenue) * 150) : 4; @endphp
                            <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end pt-5">
                                <div class="mb-2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100">
                                    &#8369;{{ number_format($month['total'], 2) }}
                                </div>
                                <div tabindex="0"
                                     aria-label="{{ $month['label'] }} revenue: {{ number_format($month['total'], 2) }} pesos"
                                     class="w-full max-w-12 rounded-t-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 {{ $month['total'] > 0 ? 'bg-gradient-to-t from-amber-500 to-amber-300 shadow-sm group-hover:from-amber-600 group-hover:to-amber-400' : 'bg-slate-200' }}"
                                     style="height: {{ $barHeight }}px"></div>
                                <span class="mt-2 text-xs font-semibold text-slate-500">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($sixMonthTotal == 0)
                    <p class="mt-3 text-center text-sm text-slate-500">Revenue will appear here after payments are recorded.</p>
                @else
                    <p class="mt-3 text-center text-xs text-slate-400">Hover over or focus a bar to see its exact value.</p>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold">Recent appointments</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($recentAppointments as $appointment)
                    <a class="flex justify-between p-4 hover:bg-slate-50" href="{{ route('appointments.show', $appointment) }}">
                        <div>
                            <div class="font-semibold">{{ $appointment->customer->full_name }}</div>
                            <div class="text-sm text-slate-500">{{ $appointment->appointment_date->format('M d') }} &middot; {{ $appointment->barber->full_name }}</div>
                        </div>
                        <span class="text-sm capitalize text-slate-500">{{ str_replace('_', ' ', $appointment->status) }}</span>
                    </a>
                @empty
                    <div class="p-10 text-center text-slate-500">No recent appointments.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
