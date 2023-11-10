<?php

namespace NW\WebService\References\Operations\Notification\Entities;

/**
 * @property Seller $Seller
 */
class Contractor
{
    const TYPE_CUSTOMER = 0;
    protected $id;
    protected $type;
    protected $name;


    public function getId(): int
    {
        return $this->id;
    }
    public function getType()
    {
        return $this->type;
    }
    public function getName()
    {
        return $this->name;
    }


    public static function getById(int $resellerId): ?self
    {
        try {
            // делаем запрос в базу
            $entity = new static($resellerId);
        } catch (\Exception $e) {
            return null;
        }

        return $entity;
    }

    public function getFullName(): string
    {
        return $this->name . ' ' . $this->id;
    }




}