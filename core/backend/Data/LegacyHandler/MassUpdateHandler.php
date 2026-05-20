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

namespace App\Data\LegacyHandler;

use ApiBeanMapper;
use App\Data\Service\RecordListProviderInterface;
use App\Data\Service\RecordMassUpdateServiceInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use MassUpdatePort;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MassUpdateHandler
 * @package App\Legacy
 */
class MassUpdateHandler extends LegacyHandler implements RecordMassUpdateServiceInterface, LoggerAwareInterface
{
    public const HANDLER_KEY = 'massupdate';
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;
    /**
     * @var RecordListProviderInterface
     */
    private $listViewProvider;

    /**
     * MassUpdateHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RecordListProviderInterface $listViewProvider
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RecordListProviderInterface $listViewProvider,
        RequestStack $session
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
        $this->listViewProvider = $listViewProvider;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Delete records
     *
     * @param string $moduleName
     * @param array $attributes
     * @param array $ids
     * @return array
     */
    public function massUpdate(string $moduleName, array $attributes, array $ids = []): array
    {
        $this->init();
        $this->startLegacyApp();

        $result = $this->callLegacy($moduleName, $attributes, $ids);

        $this->close();

        return $result;
    }

    /**
     * @param string $moduleName
     * @param array $attributes
     * @param array $ids
     * @return array
     */
    protected function callLegacy(string $moduleName, array $attributes, array $ids): array
    {
        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/MassUpdate/MassUpdatePort.php';

        $massUpdate = new MassUpdatePort();

        $inputs = [];
        $inputs['massupdate'] = true;
        $inputs['delete'] = false;
        $inputs['merge'] = false;
        $inputs['module'] = $moduleName;
        $inputs['uid'] = implode(',', $ids);
        $inputs['mass'] = $ids;
        $inputs['selectCount'] = count($ids);

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/ApiBeanMapper/ApiBeanMapper.php';
        $mapper = new ApiBeanMapper();
        $mapper->toBeanAttributes(BeanFactory::newBean($moduleName), $attributes);

        $inputs = array_merge($inputs, $attributes);

        $result = $massUpdate->run($inputs);
        $this->logDebug($result);

        return $result;
    }

    /**
     * @param array $result
     * @return void
     */
    protected function logDebug(array $result): void
    {
        if (!empty($result['debug'])) {
            foreach ($result['debug'] as $item) {
                $this->logger->debug($item);
            }
        }

        if (!empty($result['updated'])) {
            $this->logger->debug("Updated '" . count($result['updated']) . "' records");
        }

    }

    /**
     * @param string $moduleName
     * @param array $attributes
     * @param array $criteria
     * @param array $sort
     * @return array
     */
    public function massUpdateFromCriteria(
        string $moduleName,
        array $attributes,
        array $criteria,
        array $sort
    ): array {
        $this->init();
        $this->startLegacyApp();

        $listView = $this->listViewProvider->getList(
            $this->moduleNameMapper->toFrontEnd($moduleName),
            $criteria,
            -1,
            // Hardcoded limit - passing 0 as limit throws a fatal
            100000,
            $sort
        );

        $ids = [];
        foreach ($listView->getRecords() as $record) {
            $ids[] = $record['id'];
        }

        $result = $this->callLegacy($moduleName, $attributes, $ids);

        $this->close();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
