<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Event::all());
    }

    public function seats($id)
    {
        $event = \App\Models\Event::findOrFail($id);
        return response()->json($event->seats);
    }
}
