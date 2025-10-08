<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\Engine\Model;

class Feedback
{
    /**
     * @var bool
     */
    public $success;

    /**
     * @var int
     */
    public $statusCode = -1;

    /**
     * @var string[]
     */
    public $messages = [];

    /**
     * @var string[]
     */
    public $messageLabels = [];

    /**
     * @var string[]
     */
    public $debug = [];

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    public $errors = [];
    /**
     * @var array
     */
    public $warnings = [];

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return Feedback
     */
    public function setSuccess(bool $success): Feedback
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @return Feedback
     */
    public function setStatusCode(int $statusCode): Feedback
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages ?? [];
    }

    /**
     * @param string[] $messages
     * @return Feedback
     */
    public function setMessages(array $messages): Feedback
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getMessageLabels(): array
    {
        return $this->messageLabels;
    }

    /**
     * @param string[] $messageLabels
     * @return Feedback
     */
    public function setMessageLabels(array $messageLabels): Feedback
    {
        $this->messageLabels = $messageLabels;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDebug(): array
    {
        return $this->debug;
    }

    /**
     * @param string[] $debug
     * @return Feedback
     */
    public function setDebug(array $debug): Feedback
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @param string $message
     * @return Feedback
     */
    public function appendDebug(string $message): Feedback
    {
        $debug = $this->debug ?? [];
        $debug[] = $message;
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return Feedback
     */
    public function setData(array $data): Feedback
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return Feedback
     */
    public function setErrors(array $errors): Feedback
    {
        $this->errors = $errors;

        return $this;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function setWarnings(array $warnings): Feedback
    {
        $this->warnings = $warnings;

        return $this;
    }
}
