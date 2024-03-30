<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function index() {
        return "";
    }
    //
    public function cancel() {
        return "cancel";
    }
    //
    public function success() {
        return View("success");
    }
    //
    public function checkoutpay() {
        return "checkoutpay";
    }
}
