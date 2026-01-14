<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Illuminate\Support\Facades\Log;

class DynamicMailService
{
    /**
     * Send an email using the admin-configured SMTP settings.
     *
     * @param Mailable $mailable The mailable instance to send
     * @param string|array $to Recipient email(s)
     * @return bool
     */
    public static function send(Mailable $mailable, string|array $to): bool
    {
        try {
            // Configure mailer with admin settings
            self::configureMailer();
            
            // Send the email
            Mail::to($to)->send($mailable);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('DynamicMailService Error: ' . $e->getMessage(), [
                'to' => $to,
                'mailable' => get_class($mailable),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Queue an email using the admin-configured SMTP settings.
     *
     * @param Mailable $mailable The mailable instance to queue
     * @param string|array $to Recipient email(s)
     * @return bool
     */
    public static function queue(Mailable $mailable, string|array $to): bool
    {
        try {
            // Configure mailer with admin settings
            self::configureMailer();
            
            // Queue the email
            Mail::to($to)->queue($mailable);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('DynamicMailService Queue Error: ' . $e->getMessage(), [
                'to' => $to,
                'mailable' => get_class($mailable),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Configure Laravel's mailer with admin settings from the database.
     */
    public static function configureMailer(): void
    {
        $config = self::getMailConfig();
        
        // Update the mail configuration at runtime
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $config['host'],
            'mail.mailers.smtp.port' => $config['port'],
            'mail.mailers.smtp.encryption' => $config['encryption'],
            'mail.mailers.smtp.username' => $config['username'],
            'mail.mailers.smtp.password' => $config['password'],
            'mail.from.address' => $config['from_address'],
            'mail.from.name' => $config['from_name'],
        ]);

        // Purge the mailer to force it to reload with new config
        Mail::purge('smtp');
    }

    /**
     * Get mail configuration from admin settings.
     *
     * @return array
     */
    public static function getMailConfig(): array
    {
        return [
            'mailer' => Setting::get('mail_mailer', 'smtp'),
            'host' => Setting::get('mail_host', env('MAIL_HOST', 'smtp.mailtrap.io')),
            'port' => (int) Setting::get('mail_port', env('MAIL_PORT', 2525)),
            'encryption' => Setting::get('mail_encryption', env('MAIL_ENCRYPTION', 'tls')),
            'username' => Setting::get('mail_username', env('MAIL_USERNAME')),
            'password' => Setting::get('mail_password', env('MAIL_PASSWORD')),
            'from_address' => Setting::get('mail_from_address', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
            'from_name' => Setting::get('mail_from_name', Setting::get('site_name', 'National Resource Benefits')),
        ];
    }

    /**
     * Test the SMTP connection with current settings.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testConnection(): array
    {
        try {
            $config = self::getMailConfig();
            
            // Create a DSN string for the SMTP connection
            $scheme = $config['encryption'] === 'ssl' ? 'smtps' : 'smtp';
            $dsn = new Dsn(
                $scheme,
                $config['host'],
                $config['username'],
                $config['password'],
                $config['port']
            );
            
            $factory = new EsmtpTransportFactory();
            $transport = $factory->create($dsn);
            
            // Attempt to connect
            $transport->start();
            $transport->stop();
            
            return [
                'success' => true,
                'message' => 'SMTP connection successful!'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SMTP connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send a test email to verify configuration.
     *
     * @param string $toEmail The email address to send test to
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendTestEmail(string $toEmail): array
    {
        try {
            self::configureMailer();
            
            $config = self::getMailConfig();
            
            Mail::raw('This is a test email from ' . $config['from_name'] . '. Your SMTP configuration is working correctly!', function ($message) use ($toEmail, $config) {
                $message->to($toEmail)
                    ->subject('Test Email - SMTP Configuration');
            });
            
            return [
                'success' => true,
                'message' => 'Test email sent successfully to ' . $toEmail
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ];
        }
    }
}
