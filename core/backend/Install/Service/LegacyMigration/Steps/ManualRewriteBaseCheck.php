<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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

namespace App\Install\Service\LegacyMigration\Steps;

use App\Engine\Model\Feedback;
use App\Engine\Model\ProcessStepTrait;
use App\Engine\Service\ProcessSteps\ProcessStepAlert;
use App\Install\LegacyHandler\InstallHandler;
use App\Install\Service\Installation\InstallStepTrait;
use App\Install\Service\LegacyMigration\LegacyMigrationStepInterface;

/**
 * Class ManualRewriteBaseCheck
 * @package App\Install\Service\LegacyMigration\Steps;
 */
class ManualRewriteBaseCheck implements LegacyMigrationStepInterface
{
    use ProcessStepTrait;
    use InstallStepTrait;

    public const HANDLER_KEY = 'manual-rewrite-base-check';
    public const POSITION = 450;

    /**
     * @var InstallHandler
     */
    private $handler;

    /**
     * ManualRewriteBaseCheck constructor.
     * @param InstallHandler $handler
     */
    public function __construct(InstallHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return self::POSITION;
    }

    /**
     * Get Alert
     * @param array $context
     * @return ProcessStepAlert
     */
    public function getAlert(array &$context): ProcessStepAlert
    {

        $alert = new ProcessStepAlert();
        $alert->setTile('Manually Check legacy htaccess');
        $messages = [
            'Please check RewriteBase in \'public/legacy/.htaccess\' and update manually',
            'Check SuiteCRM 8 Migration documentation for information on how to configure'
        ];

        $config = $this->handler->loadLegacyConfig();
        $siteURL = $config['site_url'] ?? '';

        if (!empty($siteURL)) {
            $urlParts = parse_url($siteURL) ?? [];
            $path = rtrim($urlParts['path'] ?? '', " \t\n\r\0\x0B/");

            $messages[] = "- Based on your site url '" . $siteURL . "'.";
            $messages[] = "- The RewriteBase should be something like '" . $path . '/legacy' . "'.";
        }

        $alert->setMessages($messages);

        return $alert;
    }

    /**
     * @inheritDoc
     */
    public function execute(array &$context): Feedback
    {

        $feedback = new Feedback();
        $feedback->setSuccess(true);
        $feedback->setMessages([
            'Manual RewriteBase check done',
        ]);

        return $feedback;
    }
}
