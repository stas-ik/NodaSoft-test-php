<?php

namespace NW\WebService\References\Operations\Notification;

use NW\WebService\References\Operations\Notification\Entities\Recipient;
use NW\WebService\References\Operations\Notification\Entities\Contractor;
use NW\WebService\References\Operations\Notification\Entities\Employee;
use NW\WebService\References\Operations\Notification\Entities\Seller;
use NW\WebService\References\Operations\Notification\Helpers\Helper;

class ReturnOperation extends ReferencesOperation
{
    public const TYPE_NEW    = 1;
    public const TYPE_CHANGE = 2;

    private $reseller;
    private $client;
    private $expert;
    private $creator;
    private $result;


    /**
     * @throws \Exception
     */

    public function doOperation(): array
    {
        $data = (array)$this->getRequest('data');
        $notificationType = (int)$data['notificationType'];

        $this->result = [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail'   => false,
            'notificationClientBySms'     => [
                'isSent'  => false,
                'message' => '',
            ],
        ];


        if (empty((int)$data['resellerId'])) {
            $this->result['notificationClientBySms']['message'] = 'Empty resellerId';
            return $this->result;
        }

        if (empty((int)$notificationType)) {
            throw new \Exception('Empty notificationType', 400);
        }

        $this->initEntities($data);

        $differences = '';
        if ($notificationType === self::TYPE_NEW) {
            $differences = __('NewPositionAdded', null, $this->reseller->getId());
        } elseif ($notificationType === self::TYPE_CHANGE && !empty($data['differences'])) {
            $differences = __('PositionStatusHasChanged', [
                'FROM' => Status::getName((int)$data['differences']['from']),
                'TO'   => Status::getName((int)$data['differences']['to']),
            ], $this->reseller->getId());
        }

        $this->sendMessages($data, $differences, $notificationType);

        return $this->result;
    }


    /**
     * @throws \Exception
     */
    private function initEntities(array $data): void
    {
        $this->reseller = Seller::getById((int)$data['resellerId']);
        if ($this->reseller === null) {
            throw new \Exception('Seller not found!', 400);
        }

        $this->client = Recipient::getById((int)$data['clientId']);
        if ($this->client === null ||
            $this->client->getType() !== Contractor::TYPE_CUSTOMER ||
            $this->client->seller->getId() !== $this->reseller->getId()
        ) {
            throw new \Exception('сlient not found!', 400);
        }

        $this->creator = Employee::getById((int)$data['creatorId']);
        if ($this->creator === null) {
            throw new \Exception('Creator not found!', 400);
        }

        $this->expert = Employee::getById((int)$data['expertId']);
        if ($this->expert === null) {
            throw new \Exception('Expert not found!', 400);
        }
    }
    private function initTemplateData(array $data, string $differences): array
    {
        $templateData = [
            'COMPLAINT_ID'       => (int)$data['complaintId'],
            'COMPLAINT_NUMBER'   => (string)$data['complaintNumber'],
            'CREATOR_ID'         => (int)$data['creatorId'],
            'CREATOR_NAME'       => $this->creator->getFullName(),
            'EXPERT_ID'          => (int)$data['expertId'],
            'EXPERT_NAME'        => $this->expert->getFullName(),
            'CLIENT_ID'          => (int)$data['clientId'],
            'CLIENT_NAME'        => empty($this->client->getFullName()) ? $this->client->getName() : $this->client->getFullName(),
            'CONSUMPTION_ID'     => (int)$data['consumptionId'],
            'CONSUMPTION_NUMBER' => (string)$data['consumptionNumber'],
            'AGREEMENT_NUMBER'   => (string)$data['agreementNumber'],
            'DATE'               => (string)$data['date'],
            'DIFFERENCES'        => $differences,
        ];

        // Если хоть одна переменная для шаблона не задана, то не отправляем уведомления
        foreach ($templateData as $key => $tempData) {
            if (empty($tempData)) {
                throw new \Exception("Template Data ({$key}) is empty!", 500);
            }
        }
        return $templateData;
    }

    /**
     * @throws \Exception
     */
    private function sendMessages(array $data, string $differences, string $notificationType): void
    {
        $templateData = $this->initTemplateData($data, $differences);

        $emailFrom = Helper::getResellerEmailFrom();

        // Получаем email сотрудников из настроек
        $emails = Helper::getEmailsByPermit();
        if ($emailFrom && $emails) {
            foreach ($emails as $email) {
                MessagesClient::sendMessage([
                    MessageTypes::EMAIL => [
                        'emailFrom' => $emailFrom,
                        'emailTo'   => $email,
                        'subject'   => __('complaintEmployeeEmailSubject', $templateData, $this->reseller->getId()),
                        'message'   => __('complaintEmployeeEmailBody', $templateData, $this->reseller->getId()),
                    ],
                ], $this->reseller->getId(), NotificationEvents::CHANGE_RETURN_STATUS);
                $this->result['notificationEmployeeByEmail'] = true;
            }
        }

        // Шлём клиентское уведомление, только если произошла смена статуса
        if ($notificationType === self::TYPE_CHANGE && !empty($data['differences']['to'])) {
            if (!empty($emailFrom) && !empty($this->client->getEmail())) {
                MessagesClient::sendMessage([
                    MessageTypes::EMAIL => [
                        'emailFrom' => $emailFrom,
                        'emailTo'   => $this->client->getEmail(),
                        'subject'   => __('complaintClientEmailSubject', $templateData, $this->reseller->getId()),
                        'message'   => __('complaintClientEmailBody', $templateData, $this->reseller->getId()),
                    ],
                ], $this->reseller->getId(), $this->client->getId(), NotificationEvents::CHANGE_RETURN_STATUS, (int)$data['differences']['to']);
                $this->result['notificationClientByEmail'] = true;
            }

            if (!empty($this->client->mobile)) {
                $isSend = NotificationManager::send(
                    $this->reseller->getId(),
                    $this->client->getId(),
                    NotificationEvents::CHANGE_RETURN_STATUS,
                    (int)$data['differences']['to'],
                    $templateData
                );

                if ($isSend) {
                    $this->result['notificationClientBySms']['isSend'] = true;
                }
            }
        }
    }
}