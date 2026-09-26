<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Clasificación de los documentos adjuntos (polimórficos).
 */
enum DocumentCategory: string
{
    use HasLabel;

    case Quote = 'quote';
    case Contract = 'contract';
    case Invoice = 'invoice';
    case DeliveryNote = 'delivery_note';
    case Insurance = 'insurance';
    case Prl = 'prl';
    case SocialSecurity = 'social_security';
    case TaxCertificate = 'tax_certificate';
    case Identity = 'identity';
    case Plan = 'plan';
    case License = 'license';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Presupuesto',
            self::Contract => 'Contrato',
            self::Invoice => 'Factura',
            self::DeliveryNote => 'Albarán',
            self::Insurance => 'Seguro RC',
            self::Prl => 'PRL / Seguridad y salud',
            self::SocialSecurity => 'Alta autónomo / RETA',
            self::TaxCertificate => 'Certificado AEAT',
            self::Identity => 'Identificación (DNI/NIE/CIF)',
            self::Plan => 'Plano / Memoria',
            self::License => 'Licencia de obra',
            self::Certificate => 'Certificado final',
            self::Other => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Quote => 'blue',
            self::Contract => 'indigo',
            self::Invoice => 'green',
            self::DeliveryNote => 'teal',
            self::Insurance => 'amber',
            self::Prl => 'orange',
            self::SocialSecurity => 'purple',
            self::TaxCertificate => 'pink',
            self::Identity => 'gray',
            self::Plan => 'blue',
            self::License => 'indigo',
            self::Certificate => 'green',
            self::Other => 'gray',
        };
    }
}
