<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    public function save(array $data, ?Appointment $appointment = null): Appointment
    {
        return DB::transaction(function () use ($data, $appointment) {
            $barber = Barber::lockForUpdate()->findOrFail($data['barber_id']);
            $service = Service::findOrFail($data['service_id']);
            if ($barber->status !== 'active') {
                throw ValidationException::withMessages(['barber_id' => 'The selected barber is inactive.']);
            }
            if ($service->status !== 'available') {
                throw ValidationException::withMessages(['service_id' => 'The selected service is unavailable.']);
            }
            $date = Carbon::parse($data['appointment_date']);
            $start = Carbon::parse($data['appointment_date'].' '.$data['start_time']);
            $end = $start->copy()->addMinutes($service->duration_minutes);
            $days = array_map('trim', explode(',', Setting::valueOf('operating_days', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday')));
            if (! in_array($date->englishDayOfWeek, $days, true)) {
                throw ValidationException::withMessages(['appointment_date' => 'The shop is closed on this day.']);
            }
            $open = Setting::valueOf('opening_time', '08:00');
            $close = Setting::valueOf('closing_time', '19:00');
            if ($start->format('H:i') < $open || $end->format('H:i') > $close) {
                throw ValidationException::withMessages(['start_time' => "Appointment must be within business hours ($open-$close)."]);
            }
            $conflict = Appointment::where('barber_id', $barber->id)->whereDate('appointment_date', $date->toDateString())
                ->where('status', '!=', 'cancelled')->when($appointment, fn ($q) => $q->whereKeyNot($appointment->id))
                ->where('start_time', '<', $end->format('H:i:s'))->where('end_time', '>', $start->format('H:i:s'))->lockForUpdate()->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['start_time' => 'This barber already has an overlapping appointment.']);
            }
            $payload = array_merge($data, ['appointment_date' => $date->toDateString(), 'start_time' => $start->format('H:i:s'), 'end_time' => $end->format('H:i:s'), 'service_name_snapshot' => $service->name,
                'service_price_snapshot' => $service->price, 'service_duration_snapshot' => $service->duration_minutes]);
            if ($appointment) {
                $appointment->update($payload);

                return $appointment->fresh();
            }

            return Appointment::create($payload);
        }, 3);
    }
}
