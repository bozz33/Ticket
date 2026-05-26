<?php

namespace App\Support\Microservices;

final class MicroserviceNames
{
    public const ApiGateway = 'api_gateway';

    public const Notifications = 'notifications';

    public const Media = 'media';

    public const CatalogSearch = 'catalog_search';

    public const Analytics = 'analytics';

    public const AccessCheckin = 'access_checkin';

    public const All = [
        self::ApiGateway,
        self::Notifications,
        self::Media,
        self::CatalogSearch,
        self::Analytics,
        self::AccessCheckin,
    ];
}
