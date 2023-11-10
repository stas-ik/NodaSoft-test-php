<?php

namespace NW\WebService\References\Operations\Notification\Entities;


/**
 * @property Seller $seller
 * @property string $email
 * @property string $mobile
 */
class Recipient extends Contractor
{
    protected string $email;
    protected string $mobile;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }




}