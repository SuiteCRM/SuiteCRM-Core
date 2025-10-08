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
use App\Install\Service\Installation\InstallStepTrait;
use App\Install\Service\LegacyMigration\LegacyMigrationStepInterface;

/**
 * Class ChangeRewriteBase
 * @package App\Install\Service\LegacyMigration\Steps;
 */
class ChangeRewriteBase implements LegacyMigrationStepInterface
{
    use ProcessStepTrait;
    use InstallStepTrait;

    public const HANDLER_KEY = 'change-rewrite-base';
    public const POSITION = 400;

    /**
     * @var string
     */
    private $legacyDir;

    /**
     * ChangeRewriteBase constructor.
     * @param string $legacyDir
     */
    public function __construct(string $legacyDir)
    {
        $this->legacyDir = $legacyDir;
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
     * @inheritDoc
     */
    public function execute(array &$context): Feedback
    {
        $htAccessPath = $this->legacyDir . '/.htaccess';
        if (!file_exists($htAccessPath)) {
            $feedback = new Feedback();
            $feedback->setSuccess(true);
            $feedback->setMessages(['WARNING: No htaccess file. Skipping htaccess update']);

            return $feedback;
        }

        if (!is_writable($htAccessPath)) {
            $feedback = new Feedback();
            $feedback->setSuccess(true);
            $feedback->setMessages(['WARNING:  Not able to write to htaccess. Skipping htaccess update']);

            return $feedback;
        }

        $contents = file_get_contents($htAccessPath);

        $matches = [];
        $matchFound = preg_match("/RewriteBase\s*(\/)?(.*)(\/)?/", $contents, $matches);

        $match = $matches[0] ?? '';

        if (!$matchFound || empty($match)) {
            $feedback = new Feedback();
            $feedback->setSuccess(true);
            $feedback->setMessages([
                'WARNING: No RewriteBase configuration or not according to expected pattern. Skipping htaccess update',
            ]);

            return $feedback;
        }

        $replacement = preg_replace("/RewriteBase\s*(\/)?(.*)(\/)?$/", "RewriteBase /$2/public/legacy", $match);
        $replacement = str_replace('//', '/', $replacement);

        $contents = preg_replace("/RewriteBase\s*(.*)/", $replacement, $contents);

        file_put_contents($htAccessPath, $contents);

        $feedback = new Feedback();
        $feedback->setSuccess(true);
        $feedback->setMessages([
            'Updated htaccess RewriteBase',
        ]);

        return $feedback;
    }
}
