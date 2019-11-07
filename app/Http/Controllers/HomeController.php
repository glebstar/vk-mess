<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Result;


class HomeController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date;
        $result = false;
        if ($date) {
            $result = Result::getResult($date);
        }
        return view('home', [
            'date' => $date,
            'result' => $result
        ]);
    }
}
