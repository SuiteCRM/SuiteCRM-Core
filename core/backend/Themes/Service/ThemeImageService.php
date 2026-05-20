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


namespace App\Themes\Service;

use App\Themes\Entity\ThemeImages;
use Symfony\Contracts\Cache\CacheInterface;

class ThemeImageService
{
    /**
     * @var string[]
     */
    private $themeImagePaths;

    /**
     * @var array
     */
    private $themeImageTypePriority;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var ThemeImageFinder
     */
    private $themeImageFinder;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * ThemeImageService constructor.
     * @param string[] $themeImagePaths
     * @param array $themeImageSupportedTypes
     * @param string $projectDir
     * @param ThemeImageFinder $themeImageFinder
     * @param CacheInterface $cache
     */
    public function __construct(
        array $themeImagePaths,
        array $themeImageSupportedTypes,
        string $projectDir,
        ThemeImageFinder $themeImageFinder,
        CacheInterface $cache
    ) {
        $this->themeImagePaths = $themeImagePaths;
        $this->projectDir = $projectDir;
        $this->themeImageFinder = $themeImageFinder;

        $this->themeImageTypePriority = $this->buildTypePriorityMap($themeImageSupportedTypes);
        $this->cache = $cache;
    }

    /**
     * Build the priority map
     * @param array $types
     * @return array
     */
    protected function buildTypePriorityMap(array $types): array
    {
        return array_flip($types);
    }

    /**
     * Get images for given $theme
     * @param string $theme
     * @return ThemeImages
     */
    public function get(string $theme): ThemeImages
    {
        $images = new ThemeImages();
        $images->setId($theme);

        $items = $this->cache->get('theme-images', function () use ($theme) {

            $items = [];

            foreach ($this->themeImagePaths as $themeImagePath) {
                $themeImages = $this->getImages($themeImagePath, $theme);
                foreach ($themeImages as $imageKey => $image) {
                    $items[$imageKey] = $image;
                }
            }


            return $items;
        });

        $images->setItems($items);

        return $images;
    }

    /**
     * Get images information for given path and theme
     * @param string $imagePath
     * @param string $theme
     * @return array
     */
    protected function getImages(string $imagePath, string $theme): array
    {

        $path = $this->buildPath($imagePath, $theme);
        $basePath = $this->projectDir . '/public/';
        $fullPath = $basePath . $path;

        $it = $this->themeImageFinder->find($fullPath);

        $images = [];
        if (empty($it)) {
            return $images;
        }

        foreach ($it as $file) {
            $filename = $file->getFilename();
            $name = pathinfo($filename, PATHINFO_FILENAME);

            $filePath = "$path/{$file->getFilename()}";
            $extension = $file->getExtension();

            if (!$this->hasPriority($images, $name, $extension)) {
                continue;
            }

            $content = '';
            if ($extension === 'svg' && file_exists($file->getRealPath())) {
                $content = file_get_contents($file->getRealPath());
            }

            $images[$name] = [
                'path' => $filePath,
                'name' => $name,
                'type' => $extension,
                'content' => $content
            ];
        }

        return $images;
    }

    /**
     * Build path
     * @param string $imagePath
     * @param string $theme
     * @return string
     */
    protected function buildPath(string $imagePath, string $theme): string
    {
        return str_replace('<theme>', $theme, $imagePath);
    }

    /**
     * Check if current image has priority over already store image
     * @param array $images
     * @param string $name
     * @param string $currentType
     * @return bool
     */
    protected function hasPriority(array $images, string $name, string $currentType): bool
    {
        if (empty($images[$name])) {
            return true;
        }

        $existingType = $images[$name]['type'];

        $existingPriority = $this->themeImageTypePriority[$existingType];
        $currentPriority = $this->themeImageTypePriority[$currentType];

        return $existingPriority > $currentPriority;
    }
}
