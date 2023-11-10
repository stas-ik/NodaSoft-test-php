<?php

namespace NW\WebService\References\Operations\Notification;

abstract class ReferencesOperation
{
    abstract public function doOperation(): array;

    public function getRequest($pName)
    {
        $clean_post = filter_var_array($_POST, FILTER_SANITIZE_STRING); // необходимо добработать фильтр получаемых данных (удаление спецсимволов, экарнирование и т.д.)
        return $clean_post[$pName]; // использование $_REQUEST недопустимо в целях безопасности по многим причинам, необходимо знать как именно будут приходить данные ($_GET, $_POST и т.д.).
    }
}