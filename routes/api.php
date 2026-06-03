<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InboundController;

Route::get("/inbound/{inbound}/allocations",[ InboundController::class,"showInboundAllocations"])->name("");
