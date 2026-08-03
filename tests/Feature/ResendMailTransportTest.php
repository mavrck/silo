<?php

namespace Tests\Feature;

use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendMailTransportTest extends TestCase
{
    /**
     * Proves the resend/resend-php client is actually installed and wired
     * up correctly, not just that config/mail.php has a "resend" entry —
     * this would throw a "class not found" error if the package were
     * missing, since Laravel's ResendTransport type-hints Resend\Contracts\Client.
     */
    public function test_the_resend_transport_can_be_resolved(): void
    {
        config(['services.resend.key' => 'test-key']);

        $transport = Mail::mailer('resend')->getSymfonyTransport();

        $this->assertInstanceOf(ResendTransport::class, $transport);
    }
}
