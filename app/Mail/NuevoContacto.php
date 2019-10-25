<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NuevoContacto extends Mailable
{
    use Queueable, SerializesModels;

    public $c;
    public $historial;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($c, $historial)
    {
        $this->c = $c;
        $this->historial = $historial;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // dd($this->c);
        $c         = $this->c;
        $historial = $this->historial;
        return $this->replyTo($c->email, "Contacto desde la App")
        ->subject("App: ".$c->tema)
        ->to('gerardo.ruiz@mobilechile.app')
        // ->attach($c->url_imagen)
        // ->text("emails.contacto");
        ->view('emails.contacto', compact('c','historial'));
    }
}
