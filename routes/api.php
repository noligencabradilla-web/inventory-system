<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InboundController;
use App\Http\Controllers\Admin\OutboundController;

Route::get("/inbound/{inbound}/allocations", [InboundController::class, "showInboundAllocations"])->name("");
Route::get("outbound/check/inbound", [OutboundController::class, "checkForInboundAllocation"]);
// Route::post("outbound/storeAjax", [OutboundController::class, "storeAjaxOutbound"]);
