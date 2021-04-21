<?php

declare(strict_types=1);

namespace Frontend\App\Common;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Trait TimestampAwareTrait
 * @package Frontend\App\Common
 */
trait TimestampAwareTrait
{
    /**
     * @var string $dateFormat
     */
    private $dateFormat = 'Y-m-d H:i:s';

    /**
     * @ORM\Column(name="created", type="datetime_immutable")
     * @var DateTimeImmutable
     */
    protected $created;

    /**
     * @ORM\Column(name="updated", type="datetime_immutable", nullable=true)
     * @var DateTimeImmutable
     */
    protected $updated;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     * @return void
     */
    public function updateTimestamps(): void
    {
        $this->touch();
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return string|null
     */
    public function getCreatedFormatted(): ?string
    {
        if ($this->created instanceof DateTimeImmutable) {
            return $this->created->format($this->dateFormat);
        }

        return null;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * @return string|null
     */
    public function getUpdatedFormatted(): ?string
    {
        if ($this->updated instanceof DateTimeImmutable) {
            return $this->updated->format($this->dateFormat);
        }

        return null;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat(string $dateFormat): void
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * @return void
     */
    public function touch(): void
    {
        try {
            if (!($this->created instanceof DateTimeImmutable)) {
                $this->created = new DateTimeImmutable();
            }

            $this->updated = new DateTimeImmutable();
        } catch (Exception $exception) {
            #TODO save the error message
        }
    }
}
