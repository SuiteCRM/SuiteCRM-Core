<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\AsyncTask\Service\MessageListener;

use App\AsyncTask\Message\AsyncTaskFailure;
use App\AsyncTask\Message\AsyncTaskRun;
use App\AsyncTask\Service\TaskFailureHandler\AsyncTaskFailureHandlerRegistryInterface;
use App\Authentication\LegacyHandler\Authentication;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


class AsyncTaskFailureMessageListener
{

    public function __construct(
        protected AsyncTaskFailureHandlerRegistryInterface $registry,
        protected Authentication $authentication,
        protected LoggerInterface $logger
    ) {
    }

    #[AsMessageHandler(fromTransport: 'failed')]
    public function handleInternalFailure(AsyncTaskRun $message): void
    {
        $type = $message->getTaskType();
        $handlerKey = $message->getHandlerKey();

        $this->logger->debug('Received internal failure (from failed transport)', [
            'component' => 'async-task-failure-listener',
            'taskId' => $message->getTaskId(),
            'type' => $type,
            'module' => $message->getModule(),
            'handlerKey' => $handlerKey,
        ]);

        $this->authentication->initLegacySystemSession();

        $handler = $this->registry->getHandler($type, $handlerKey);

        if ($handler === null) {
            $this->logger->error('No AsyncTaskFailureHandler found for type: ' . $type . ' and handler key: ' . $handlerKey);
            return;
        }

        $failureMessage = new AsyncTaskFailure(
            $message->getTaskId(),
            $type,
            $message->getModule(),
            $handlerKey,
            $message->getData(),
            $message->getProgress()
        );

        $handler->onFailure($failureMessage);

        $this->logger->debug('Internal failure handled', [
            'component' => 'async-task-failure-listener',
            'taskId' => $message->getTaskId(),
            'handlerClass' => get_class($handler),
        ]);
    }

    #[AsMessageHandler]
    public function handleFailureMessage(AsyncTaskFailure $message): void
    {
        $type = $message->getTaskType();
        $handlerKey = $message->getHandlerKey();

        $this->logger->debug('Received AsyncTaskFailure message', [
            'component' => 'async-task-failure-listener',
            'taskId' => $message->getTaskId(),
            'type' => $type,
            'module' => $message->getModule(),
            'handlerKey' => $handlerKey,
        ]);

        $this->authentication->initLegacySystemSession();

        $handler = $this->registry->getHandler($type, $handlerKey);

        if ($handler === null) {
            $this->logger->error('No AsyncTaskFailureHandler found for type: ' . $type . ' and handler key: ' . $handlerKey);
            return;
        }

        $handler->onFailure($message);

        $this->logger->debug('AsyncTaskFailure handled', [
            'component' => 'async-task-failure-listener',
            'taskId' => $message->getTaskId(),
            'handlerClass' => get_class($handler),
        ]);
    }
}
