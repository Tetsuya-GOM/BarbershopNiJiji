<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarberRequest;
use App\Models\Barber;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BarberController extends Controller
{
    public function index()
    {
        $q = request('search');
        $barbers = Barber::withCount(['appointments', 'appointments as completed_appointments_count' => fn ($x) => $x->where('status', 'completed')])->when($q, fn ($x) => $x->where(fn ($y) => $y->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")))->latest()->paginate(10)->withQueryString();

        return view('barbers.index', compact('barbers'));
    }

    public function create()
    {
        return view('barbers.form', ['barber' => new Barber]);
    }

    public function store(BarberRequest $r)
    {
        $data = $r->validated();
        $barber = DB::transaction(function () use ($r, $data) {
            if ($r->boolean('create_login')) {
                $u = User::create(['name' => $data['first_name'].' '.$data['last_name'], 'email' => $data['email'], 'password' => $data['password'] ?: 'password', 'role' => 'barber']);
                $data['user_id'] = $u->id;
            } if ($r->hasFile('profile_photo')) {
                $data['profile_photo'] = $r->file('profile_photo')->store('barbers', 'public');
            }

return Barber::create($data);
        });

        return redirect()->route('barbers.show', $barber)->with('success', 'Barber created.');
    }

    public function show(Barber $barber)
    {
        $barber->load(['appointments' => fn ($q) => $q->with(['customer', 'service'])->latest('appointment_date')->limit(20)]);

        return view('barbers.show', compact('barber'));
    }

    public function edit(Barber $barber)
    {
        return view('barbers.form', compact('barber'));
    }

    public function update(BarberRequest $r, Barber $barber)
    {
        $data = $r->validated();
        if ($r->hasFile('profile_photo')) {
            $data['profile_photo'] = $r->file('profile_photo')->store('barbers', 'public');
        }$barber->update($data);

        return redirect()->route('barbers.show', $barber)->with('success', 'Barber updated.');
    }

    public function destroy(Barber $barber)
    {
        $barber->update(['status' => 'inactive']);

        return back()->with('success','Barber deactivated.');
    }
}
