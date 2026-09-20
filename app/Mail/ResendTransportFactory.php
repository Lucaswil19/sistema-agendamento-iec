<?php

namespace App\Mail;

use GuzzleHttp\Client;
use Illuminate\Mail\Transport\ResendTransport;
use Resend\Client as ResendClient;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;

final class ResendTransportFactory
{
    public function __invoke(array $config): ResendTransport
    {
        // O cliente padrao do SDK nao limita o tempo total da chamada HTTP.
        $http = new Client(array_merge($config['client'] ?? [], [
            'connect_timeout' => max(1, (float) ($config['connect_timeout'] ?? 3)),
            'timeout' => max(1, (float) ($config['timeout'] ?? 8)),
        ]));

        return new ResendTransport(new ResendClient(new HttpTransporter(
            $http,
            BaseUri::from($config['base_uri'] ?? 'api.resend.com'),
            Headers::withAuthorization(ApiKey::from($config['key'] ?? config('services.resend.key'))),
        )));
    }
}
