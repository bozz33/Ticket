<?php

namespace Ticket\Notifications\Domain;

final class DomainEventNames
{
    public const TENANT_CREATED = 'tenant.created';

    public const TENANT_ACTIVATED = 'tenant.activated';

    public const TENANT_SUSPENDED = 'tenant.suspended';

    public const USER_REGISTERED = 'user.registered';

    public const USER_EMAIL_VERIFIED = 'user.email_verified';

    public const EVENT_PUBLISHED = 'event.published';

    public const EVENT_UNPUBLISHED = 'event.unpublished';

    public const EVENT_UPDATED = 'event.updated';

    public const OFFER_CREATED = 'offer.created';

    public const OFFER_STOCK_CHANGED = 'offer.stock_changed';

    public const ORDER_CREATED = 'order.created';

    public const ORDER_PAID = 'order.paid';

    public const ORDER_REFUNDED = 'order.refunded';

    public const ORDER_CANCELLED = 'order.cancelled';

    public const PAYMENT_INITIATED = 'payment.initiated';

    public const PAYMENT_CONFIRMED = 'payment.confirmed';

    public const PAYMENT_FAILED = 'payment.failed';

    public const REFUND_REQUESTED = 'refund.requested';

    public const REFUND_APPROVED = 'refund.approved';

    public const REFUND_COMPLETED = 'refund.completed';

    public const PAYOUT_INITIATED = 'payout.initiated';

    public const PAYOUT_COMPLETED = 'payout.completed';

    public const ACCESS_PASS_ISSUED = 'access_pass.issued';

    public const ACCESS_PASS_CHECKED_IN = 'access_pass.checked_in';

    public const ACCESS_PASS_INVALIDATED = 'access_pass.invalidated';

    public const TRAINING_SESSION_SCHEDULED = 'training.session_scheduled';

    public const TRAINING_REGISTRATION_CONFIRMED = 'training.registration_confirmed';

    public const CALL_FOR_PROJECT_OPENED = 'call_for_project.opened';

    public const APPLICATION_SUBMITTED = 'application.submitted';

    public const APPLICATION_DECIDED = 'application.decided';

    public const CROWDFUNDING_CAMPAIGN_LAUNCHED = 'crowdfunding_campaign.launched';

    public const CONTRIBUTION_RECEIVED = 'contribution.received';

    public const STAND_RESERVED = 'stand.reserved';

    public const STAND_CONFIRMED = 'stand.confirmed';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'TENANT_CREATED' => self::TENANT_CREATED,
            'TENANT_ACTIVATED' => self::TENANT_ACTIVATED,
            'TENANT_SUSPENDED' => self::TENANT_SUSPENDED,
            'USER_REGISTERED' => self::USER_REGISTERED,
            'USER_EMAIL_VERIFIED' => self::USER_EMAIL_VERIFIED,
            'EVENT_PUBLISHED' => self::EVENT_PUBLISHED,
            'EVENT_UNPUBLISHED' => self::EVENT_UNPUBLISHED,
            'EVENT_UPDATED' => self::EVENT_UPDATED,
            'OFFER_CREATED' => self::OFFER_CREATED,
            'OFFER_STOCK_CHANGED' => self::OFFER_STOCK_CHANGED,
            'ORDER_CREATED' => self::ORDER_CREATED,
            'ORDER_PAID' => self::ORDER_PAID,
            'ORDER_REFUNDED' => self::ORDER_REFUNDED,
            'ORDER_CANCELLED' => self::ORDER_CANCELLED,
            'PAYMENT_INITIATED' => self::PAYMENT_INITIATED,
            'PAYMENT_CONFIRMED' => self::PAYMENT_CONFIRMED,
            'PAYMENT_FAILED' => self::PAYMENT_FAILED,
            'REFUND_REQUESTED' => self::REFUND_REQUESTED,
            'REFUND_APPROVED' => self::REFUND_APPROVED,
            'REFUND_COMPLETED' => self::REFUND_COMPLETED,
            'PAYOUT_INITIATED' => self::PAYOUT_INITIATED,
            'PAYOUT_COMPLETED' => self::PAYOUT_COMPLETED,
            'ACCESS_PASS_ISSUED' => self::ACCESS_PASS_ISSUED,
            'ACCESS_PASS_CHECKED_IN' => self::ACCESS_PASS_CHECKED_IN,
            'ACCESS_PASS_INVALIDATED' => self::ACCESS_PASS_INVALIDATED,
            'TRAINING_SESSION_SCHEDULED' => self::TRAINING_SESSION_SCHEDULED,
            'TRAINING_REGISTRATION_CONFIRMED' => self::TRAINING_REGISTRATION_CONFIRMED,
            'CALL_FOR_PROJECT_OPENED' => self::CALL_FOR_PROJECT_OPENED,
            'APPLICATION_SUBMITTED' => self::APPLICATION_SUBMITTED,
            'APPLICATION_DECIDED' => self::APPLICATION_DECIDED,
            'CROWDFUNDING_CAMPAIGN_LAUNCHED' => self::CROWDFUNDING_CAMPAIGN_LAUNCHED,
            'CONTRIBUTION_RECEIVED' => self::CONTRIBUTION_RECEIVED,
            'STAND_RESERVED' => self::STAND_RESERVED,
            'STAND_CONFIRMED' => self::STAND_CONFIRMED,
        ];
    }
}
