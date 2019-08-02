<?php

namespace App\Http\Controllers;

use App\Contacto;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function welcome(){

            $contactos = Contacto::All();
            // dd($contactos);
            return view('welcome',compact("contactos"));
    }
}
