<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $q = request('search');
        $customers = Customer::withCount('appointments')->when($q, fn ($x) => $x->where(fn ($y) => $y->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")))->latest()->paginate(10)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $r)
    {
        $c = Customer::create($r->validated());

        return redirect()->route('customers.show', $c)->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['appointments' => fn ($q) => $q->with(['barber', 'service'])->latest('appointment_date')]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $r, Customer $customer)
    {
        $customer->update($r->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->appointments()->exists()) {
            $customer->update(['is_active' => false]);

            return back()->with('success', 'Customer has history and was safely deactivated.');
        } $customer->delete();

        return redirect()->route('customers.index')->with('success','Customer deleted.');
    }
}
