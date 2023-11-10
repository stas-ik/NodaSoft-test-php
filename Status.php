<?php

namespace NW\WebService\References\Operations\Notification;

class Status
{
    public $id, $name;

    public static function getName(int $id): string
    {
        $a = [
            0 => 'Completed',
            1 => 'Pending',
            2 => 'Rejected',
        ];

        return $a[$id] ?? 'Status Not Found'; // так как мы получаем данные из вне - нет никаких гарантий, что id статуса придет верный, в любом случае необходимо предусмотреть этот момент, чтобы не обращаться к несуществующему элементу массива
    }
}