<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $q = request('search');
        $services = Service::withCount('appointments')->when($q, fn ($x) => $x->where('name', 'like', "%$q%"))->latest()->paginate(10)->withQueryString();

        return view('services.index', compact('services'));
    }

    public function create()
    {
        return view('services.form', ['service' => new Service]);
    }

    public function store(ServiceRequest $r)
    {
        $s = Service::create($r->validated());

        return redirect()->route('services.show', $s)->with('success', 'Service created.');
    }

    public function show(Service $service)
    {
        $service->loadCount('appointments');

        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        return view('services.form', compact('service'));
    }

    public function update(ServiceRequest $r, Service $service)
    {
        $service->update($r->validated());

        return redirect()->route('services.show', $service)->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        if ($service->appointments()->exists()) {
            $service->update(['status' => 'unavailable']);

            return back()->with('success', 'Referenced service was marked unavailable.');
        }$service->delete();

        return redirect()->route('services.index')->with('success','Service deleted.');
    }
}
