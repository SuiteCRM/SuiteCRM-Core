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

namespace App\Install\Service\Cache;

use App\Engine\Model\Feedback;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CacheBridge
 * @package App\Install\Service\Cache
 */
class CacheBridge
{
    /**
     * @var KernelInterface
     */
    protected $kernel;
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * CacheBridge constructor.
     * @param KernelInterface $kernel
     * @param string $cacheDir
     */
    public function __construct(KernelInterface $kernel, string $cacheDir)
    {
        $this->kernel = $kernel;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return Feedback
     * @throws Exception
     */
    public function clear(): Feedback
    {
        $feedback = new Feedback();
        $feedback->setSuccess(true)->setMessages(['Successfully cleared cache']);

        try {

            $this->clearCacheDir();
            $this->clearPhpCache();

        } catch (Exception $e) {
            $feedback->setSuccess(false)->setMessages(['Error clearing cache']);
        }


        return $feedback;
    }

    /**
     * Clear php cache
     * @return void
     */
    protected function clearPhpCache(): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
    }

    /**
     * Rename and delete cache subdirectory
     * @param $fs
     * @param $tempId
     * @return void
     */
    protected function clearCacheDir() : void
    {
        $fs = new Filesystem();
        $tempId = uniqid();

        if($fs->exists($this->cacheDir)){
            $fs->rename($this->cacheDir, $this->cacheDir . '_' . $tempId . '_deprecated/');
            $fs->mkdir($this->cacheDir);
            $fs->remove($this->cacheDir . '_' . $tempId . '_deprecated/');
        }
    }

}
