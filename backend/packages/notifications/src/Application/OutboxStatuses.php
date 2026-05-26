<?php

namespace Ticket\Notifications\Application;

class OutboxStatuses
{
    public const Pending = 'pending';

    public const Processing = 'processing';

    public const Published = 'published';

    public const Failed = 'failed';
}
