<?php

namespace NW\WebService\References\Operations\Notification\Helpers;


class Helper
{
    static function getResellerEmailFrom(): string
    {
        return 'contractor@example.com';
    }

    static function getEmailsByPermit($resellerId, $event): array
    {
        // fakes the method
        return ['someemeil@example.com', 'someemeil2@example.com'];
    }
}