<?php

namespace App\Enums;

enum AccessPassType: string
{
    case EventTicket = 'event_ticket';
    case TrainingEnrollment = 'training_enrollment';
    case StandReservation = 'stand_reservation';
    case PurchasePass = 'purchase_pass';

    public static function options(): array
    {
        return [
            self::EventTicket->value => 'Billet événement',
            self::TrainingEnrollment->value => 'Inscription formation',
            self::StandReservation->value => 'Réservation stand',
            self::PurchasePass->value => 'Pass achat',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }

    public static function fromOfferableType(string $offerableType): self
    {
        return match ($offerableType) {
            'App\\Models\\Event' => self::EventTicket,
            'App\\Models\\Training' => self::TrainingEnrollment,
            'App\\Models\\Stand' => self::StandReservation,
            default => self::PurchasePass,
        };
    }
}
