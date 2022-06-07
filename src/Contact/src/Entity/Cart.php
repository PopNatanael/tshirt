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
use Frontend\User\Entity\User;
use Frontend\User\Entity\UserInterface;

/**
 * Class Cart
 * @package Frontend\Frontend\Contact\Entity
 *
 * @ORM\Entity(repositoryClass="Frontend\Contact\Repository\CartRepository")
 * @ORM\Table(name="cart")
 * @ORM\HasLifecycleCallbacks
 * @package Frontend\Contact\Entity
 */
class Cart extends AbstractEntity
{
    public const PLATFORM_WEBSITE = 'website';
    public const PLATFORM_ADMIN = 'admin';

    /**
     * @ORM\ManyToOne(targetEntity="Frontend\User\Entity\User", inversedBy="user")
     * @ORM\JoinColumn(name="userUuid", referencedColumnName="uuid", nullable=false)
     * @var User $user
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Frontend\Contact\Entity\Product", inversedBy="product")
     * @ORM\JoinColumn(name="productUuid", referencedColumnName="uuid", nullable=false)
     * @var Product $productUuid
     */
    protected $productUuid;


    /**
     * Message constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Product
     */
    public function getProductUuid(): Product
    {
        return $this->productUuid;
    }

    /**
     * @param Product $productUuid
     */
    public function setProductUuid(Product $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }
}
