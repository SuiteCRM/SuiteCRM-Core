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

namespace App\MediaObjects\Controller;

use App\Engine\LegacyHandler\AclHandler;
use App\MediaObjects\Entity\ArchivedDocumentMediaObject;
use App\MediaObjects\Entity\PrivateDocumentMediaObject;
use App\MediaObjects\Entity\PrivateImageMediaObject;
use App\MediaObjects\Repository\ArchivedDocumentMediaObjectRepository;
use App\MediaObjects\Repository\PrivateDocumentMediaObjectRepository;
use App\MediaObjects\Repository\PrivateImageMediaObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Handler\DownloadHandler;

class MediaController extends AbstractController
{

    public function __construct(
        protected ArchivedDocumentMediaObjectRepository $archivedDocumentRepository,
        protected PrivateDocumentMediaObjectRepository $privateDocumentRepository,
        protected PrivateImageMediaObjectRepository $privateImageRepository,
        protected AclHandler $aclHandler
    ) {
    }

    #[Route('/media/documents/{id}', name: 'media_documents', methods: ["GET"], stateless: false)]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function downloadDocument(string $id, Request $request, DownloadHandler $downloadHandler): Response
    {

        $mediaObject = $this->privateDocumentRepository->find($id);

        if (!$mediaObject) {
            throw $this->createNotFoundException('Media object not found');
        }

        if (!empty($mediaObject->parentId) && !empty($mediaObject->parentType) && !$this->aclHandler->checkRecordAccess($mediaObject->parentType, 'view', $mediaObject->parentId)) {
            throw $this->createNotFoundException('Media object not found');
        }

        return $downloadHandler->downloadObject($mediaObject, 'file', PrivateDocumentMediaObject::class, $mediaObject->originalName);
    }

    #[Route('/media/archived/{id}', name: 'media_archived', methods: ["GET"], stateless: false)]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function downloadArchived(string $id, Request $request, DownloadHandler $downloadHandler): Response
    {
        $mediaObject = $this->archivedDocumentRepository->find($id);

        if (!$mediaObject) {
            throw $this->createNotFoundException('Media object not found');
        }

        if (!empty($mediaObject->parentId) && !empty($mediaObject->parentType) && !$this->aclHandler->checkRecordAccess($mediaObject->parentType, 'view', $mediaObject->parentId)) {
            throw $this->createNotFoundException('Media object not found');
        }

        return $downloadHandler->downloadObject($mediaObject, 'file', ArchivedDocumentMediaObject::class, $mediaObject->originalName);
    }

    #[Route('/media/images/{id}', name: 'media_image', methods: ["GET"], stateless: false)]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function downloadImage(string $id, Request $request, DownloadHandler $downloadHandler): Response
    {
        $mediaObject = $this->privateImageRepository->find($id);

        if (!$mediaObject) {
            throw $this->createNotFoundException('Media object not found');
        }

        if (!empty($mediaObject->parentId) && !empty($mediaObject->parentType) && !$this->aclHandler->checkRecordAccess($mediaObject->parentType, 'view', $mediaObject->parentId)) {
            throw $this->createNotFoundException('Media object not found');
        }

        return $downloadHandler->downloadObject($mediaObject, 'file', PrivateImageMediaObject::class, $mediaObject->originalName);
    }

}
