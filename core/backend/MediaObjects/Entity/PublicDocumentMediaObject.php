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

namespace App\MediaObjects\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Data\Entity\DefaultRecordTrait;
use App\MediaObjects\Repository\PublicDocumentMediaObjectRepository;
use App\MediaObjects\Validator\UploadValidator\UploadConstraint;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicDocumentMediaObjectRepository::class)]
#[ORM\Table(name: 'public_documents_media_objects')]
#[ApiResource(
    types: ['https://schema.org/MediaObject'],
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject(
                        [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'file' => [
                                            'type' => 'string',
                                            'format' => 'binary'
                                        ],
                                        'parentType' => [
                                            'type' => 'string',
                                            'format' => 'string'
                                        ],
                                        'parentField' => [
                                            'type' => 'string',
                                            'format' => 'string'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    )
                )
            ),
            security: "is_granted('ROLE_USER')"
        )
    ],
    outputFormats: ['jsonld' => ['application/ld+json']],
    stateless: false,
    normalizationContext: ['groups' => ['media_object:read']],
    security: "is_granted('ROLE_USER')"
)]
class PublicDocumentMediaObject implements MediaObjectInterface
{
    use DefaultRecordTrait;
    use MediaObjectTrait;

    #[ORM\Id]
    #[ORM\Column(
        name: "id",
        type: "string",
        length: 36,
        nullable: false,
        options: ["fixed" => true]
    )]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['media_object:read'])]
    public ?string $id;

    #[ApiProperty(writable: false, types: ['https://schema.org/contentUrl'])]
    #[Groups(['media_object:read'])]
    public ?string $contentUrl = null;

    #[Assert\NotNull]
    #[UploadConstraint(storageType: 'public-documents')]
    public ?File $file = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "file_path", type: "string", length: 255, nullable: true, options: ["default" => null])]
    public ?string $filePath = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "size", type: "integer", nullable: true, options: ["default" => null])]
    #[Groups(['media_object:read'])]
    public ?int $size = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "mime_type", type: "string", length: 255, nullable: true, options: ["default" => null])]
    #[Groups(['media_object:read'])]
    public ?string $mimeType = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "original_name", type: "string", length: 255, nullable: true, options: ["default" => null])]
    #[Groups(['media_object:read'])]
    public ?string $originalName = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(nullable: true)]
    #[Groups(['media_object:read'])]
    public ?array $dimensions = null;

    #[ApiProperty(writable: true)]
    #[ORM\Column(name: "parent_type", type: "string", length: 100, nullable: true, options: ["default" => null])]
    public ?string $parentType = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "parent_id", type: "string", length: 36, nullable: true, options: ["fixed" => true])]
    public ?string $parentId = null;

    #[ApiProperty(writable: true)]
    #[ORM\Column(name: "parent_field", type: "string", length: 36, nullable: true, options: ["fixed" => true])]
    public ?string $parentField = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(
        name: "temporary",
        type: "boolean",
        length: 1,
        nullable: true,
        options: ["default" => 0],
    )]
    public ?bool $temporary = true;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true, options: ["default" => null])]
    public ?string $name = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "date_entered", type: "datetime", nullable: true, options: ["default" => null])]
    public ?DateTimeInterface $dateEntered = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "date_modified", type: "datetime", nullable: true, options: ["default" => null])]
    public ?DateTimeInterface $dateModified = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "modified_user_id", type: "string", length: 36, nullable: true, options: ["fixed" => true])]
    public ?string $modifiedUserId = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "created_by", type: "string", length: 36, nullable: true, options: ["fixed" => true])]
    public ?string $createdBy = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(name: "description", type: "standard_text", nullable: true, options: ["default" => null])]
    public ?string $description = null;

    #[ApiProperty(writable: false)]
    #[ORM\Column(
        name: "deleted",
        type: "boolean",
        length: 1,
        nullable: true,
        options: ["default" => 0],
    )]
    public bool $deleted = false;

    #[ApiProperty(writable: false)]
    #[ORM\Column(
        name: "assigned_user_id",
        type: "string",
        length: 36,
        nullable: true,
        options: ["fixed" => true]
    )]
    public ?string $assignedUserId = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDateEntered(): ?DateTimeInterface
    {
        if ($this->dateEntered === null) {
            $this->dateEntered = new DateTime();
            return $this->dateEntered;
        }

        return $this->dateEntered;
    }
}
