<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Schedulers\Command;

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Install\Command\BaseCommand;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'schedulers:show')]
class ShowSchedulersCommand extends BaseCommand {

    protected PreparedStatementHandler $preparedStatementHandler;

    public function __construct(
        PreparedStatementHandler $preparedStatementHandler,
        ?string $name = null
    )
    {
        parent::__construct($name);
        $this->preparedStatementHandler = $preparedStatementHandler;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Show Schedulers')
            ->addArgument('version', InputArgument::OPTIONAL);
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output, array $inputs): int
    {
        $appStrings = $this->getAppStrings();

        $query = "SELECT name, status, job FROM schedulers ";
        $where = '';
        $title = $appStrings['LBL_ALL_SCHEDULERS'];

        $version = $input->getArgument('version');

        if ($version === '7') {
            $title = $appStrings['LBL_LEGACY_SCHEDULERS'];
            $where = "WHERE job NOT LIKE '%scheduler::%'";
        }

        if ($version === '8') {
            $title = $appStrings['LBL_SCHEDULERS'];
            $where = "WHERE job LIKE '%scheduler::%'";
        }

        try {
            $records = $this->preparedStatementHandler->fetchAll($query . $where, []);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }

        $output->writeln([
            '',
            $title,
            '===============',
            ''
        ]);

        foreach ($records as $key => $value) {
            $color = 'green';

            if ($value['status'] === 'Inactive') {
                $color = 'red';
            }

            $output->write('(' . $this->colorText($color, $value['status']) . ') ');
            $output->write($value['name']);
            $output->writeln("");
        }

        $output->writeln("");

        return Command::SUCCESS;
    }
}
