<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::pluck('value', 'key');

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $r)
    {
        $data = $r->validate(['shop_name' => 'required|string|max:150', 'contact_number' => 'required|string|max:30', 'address' => 'nullable|string|max:500', 'opening_time' => 'required|date_format:H:i', 'closing_time' => 'required|date_format:H:i|after:opening_time', 'operating_days' => 'required|array|min:1', 'operating_days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday']);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => is_array($value) ? implode(',', $value) : $value]);
        }

return back()->with('success', 'Settings updated.');
    }
}
