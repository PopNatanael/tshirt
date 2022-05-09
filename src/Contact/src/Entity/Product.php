<?php

/**
 * @see https://github.com/dotkernel/frontend/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/frontend/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Frontend\Contact\Entity;

use Frontend\App\Common\AbstractEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Product
 * @package Frontend\Frontend\Contact\Entity
 *
 * @ORM\Entity(repositoryClass="Frontend\Contact\Repository\ProductRepository")
 * @ORM\Table(name="products")
 * @ORM\HasLifecycleCallbacks
 * @package Frontend\Contact\Entity
 */
class Product extends AbstractEntity
{
    public const PLATFORM_WEBSITE = 'website';
    public const PLATFORM_ADMIN = 'admin';

    /**
     * @ORM\Column(name="product", type="string", length=150)
     * @var string
     */
    protected $product;

    /**
     * @ORM\Column(name="price", type="float", length=10)
     * @var float
     */
    protected $price;

    /**
     * @ORM\Column(name="description", type="text")
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="image", type="string", length=500)
     * @var string
     */
    protected $image;

    /**
     * Message constructor.
     * @param string $product
     * @param float $price
     * @param string $description
     * @param string $image
     */
    public function __construct(
        string $product,
        float $price,
        string $description,
        string $image
    ) {
        parent::__construct();

        $this->product = $product;
        $this->price = $price;
        $this->description = $description;
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getProduct(): string
    {
        return $this->product;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImage(): string
    {
        return $this->image;
    }
}
