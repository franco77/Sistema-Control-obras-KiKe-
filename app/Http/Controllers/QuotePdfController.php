<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PortalAccessToken;
use App\Models\Quote;
use App\Services\Branding\LogoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Genera el PDF del presupuesto para el panel y para el portal del cliente. */
class QuotePdfController extends Controller
{
    public function show(Request $request, Quote $quote)
    {
        if ($request->user()) {
            $this->authorize('view', $quote);
        } else {
            /** @var PortalAccessToken|null $token */
            $token = $request->attributes->get('portalToken');

            abort_if($token === null || ! $token->can('quote.download'), 403);
            abort_unless($token->tokenable_id === $quote->id && $token->tokenable instanceof Quote, 403);
        }

        $quote->load(['client', 'property', 'sections.items', 'author']);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => [
                'name' => setting('company.name', config('app.name')),
                'tax_id' => setting('company.tax_id'),
                'address' => setting('company.address'),
                'phone' => setting('company.phone'),
                'email' => setting('company.email'),
                // Incrustado en base64: dompdf no descarga recursos remotos.
                'logo' => app(LogoService::class)->dataUri(),
            ],
        ])->setPaper('a4');

        return $pdf->stream("presupuesto-{$quote->number}-v{$quote->version}.pdf");
    }
}