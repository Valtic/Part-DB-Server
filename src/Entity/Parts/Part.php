<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2022 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Entity\Parts;

use App\Entity\Attachments\Attachment;
use App\Entity\Attachments\AttachmentContainingDBElement;
use App\Entity\Attachments\PartAttachment;
use App\Entity\Devices\Device;
use App\Entity\Parameters\ParametersTrait;
use App\Entity\Parameters\PartParameter;
use App\Entity\Parts\PartTraits\AdvancedPropertyTrait;
use App\Entity\Parts\PartTraits\BasicPropertyTrait;
use App\Entity\Parts\PartTraits\InstockTrait;
use App\Entity\Parts\PartTraits\ManufacturerTrait;
use App\Entity\Parts\PartTraits\OrderTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Part class.
 *
 * The class properties are split over various traits in directory PartTraits.
 * Otherwise this class would be too big, to be maintained.
 *
 * @ORM\Entity(repositoryClass="App\Repository\PartRepository")
 * @ORM\Table("`parts`", indexes={
 *    @ORM\Index(name="parts_idx_datet_name_last_id_needs", columns={"datetime_added", "name", "last_modified", "id", "needs_review"}),
 *    @ORM\Index(name="parts_idx_name", columns={"name"}),
 *    @ORM\Index(name="parts_idx_ipn", columns={"ipn"}),
 * })
 * @UniqueEntity(fields={"ipn"}, message="part.ipn.must_be_unique")
 */
class Part extends AttachmentContainingDBElement
{
    use AdvancedPropertyTrait;
    //use MasterAttachmentTrait;
    use BasicPropertyTrait;
    use InstockTrait;
    use ManufacturerTrait;
    use OrderTrait;
    use ParametersTrait;

    /**
     * TODO.
     */
    protected $devices = [];

    /** @var Collection<int, PartParameter>
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="App\Entity\Parameters\PartParameter", mappedBy="element", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"group" = "ASC" ,"name" = "ASC"})
     */
    protected $parameters;

    /**
     * @ORM\Column(type="datetime", name="datetime_added", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected ?DateTime $addedDate = null;

    /** *************************************************************
     * Overridden properties
     * (They are defined here and not in a trait, to avoid conflicts).
     ****************************************************************/

    /**
     * @var string The name of this part
     * @ORM\Column(type="string")
     */
    protected string $name = '';

    /**
     * @var Collection<int, PartAttachment>
     * @ORM\OneToMany(targetEntity="App\Entity\Attachments\PartAttachment", mappedBy="element", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"name" = "ASC"})
     * @Assert\Valid()
     */
    protected $attachments;

    /**
     * @var DateTime the date when this element was modified the last time
     * @ORM\Column(type="datetime", name="last_modified", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected ?DateTime $lastModified = null;

    /**
     * @var Attachment
     * @ORM\ManyToOne(targetEntity="App\Entity\Attachments\Attachment")
     * @ORM\JoinColumn(name="id_preview_attachement", referencedColumnName="id")
     * @Assert\Expression("value == null or value.isPicture()", message="part.master_attachment.must_be_picture")
     */
    protected ?Attachment $master_picture_attachment = null;

    public function __construct()
    {
        parent::__construct();
        $this->partLots = new ArrayCollection();
        $this->orderdetails = new ArrayCollection();
        $this->parameters = new ArrayCollection();
    }

    public function __clone()
    {
        if ($this->id) {
            //Deep clone part lots
            $lots = $this->partLots;
            $this->partLots = new ArrayCollection();
            foreach ($lots as $lot) {
                $this->addPartLot(clone $lot);
            }

            //Deep clone order details
            $orderdetails = $this->orderdetails;
            $this->orderdetails = new ArrayCollection();
            foreach ($orderdetails as $orderdetail) {
                $this->addOrderdetail(clone $orderdetail);
            }

            //Deep clone parameters
            $parameters = $this->parameters;
            $this->parameters = new ArrayCollection();
            foreach ($parameters as $parameter) {
                $this->addParameter(clone $parameter);
            }
        }
        parent::__clone();
    }

    /**
     *  Get all devices which uses this part.
     *
     * @return Device[] * all devices which uses this part as a one-dimensional array of Device objects
     *                  (empty array if there are no ones)
     *                  * the array is sorted by the devices names
     */
    public function getDevices(): array
    {
        return $this->devices;
    }
}
