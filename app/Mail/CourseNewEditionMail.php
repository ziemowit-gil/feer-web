<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\CourseEdition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Jednorazowa wiadomość do osoby, która zapisała się na powiadomienie o nowym terminie szkolenia. */
class CourseNewEditionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Course $course, public CourseEdition $edition, public string $siteName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nowy termin szkolenia: '.$this->course->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.course-new-edition');
    }
}
