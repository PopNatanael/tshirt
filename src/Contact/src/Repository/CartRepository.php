<?php

declare(strict_types=1);

namespace Frontend\Contact\Repository;

use Frontend\Contact\Entity\Message;
use Doctrine\ORM;
use Doctrine\ORM\EntityRepository;
use Frontend\Contact\Entity\Cart;
use Frontend\User\Entity\User;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;

/**
 * Class CartRepository
 * @package Frontend\Contact\Repository
 */
class CartRepository extends EntityRepository
{
    public function getCart()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cart')
            ->from(Cart::class, 'cart');

        return $qb->getQuery()->useQueryCache(true)->getResult();
    }

    public function saveCart(Cart $cart)
    {
        $this->getEntityManager()->persist($cart);
        $this->getEntityManager()->flush();
    }

    public function getUserCartItems(User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cart')
            ->from(Cart::class, 'cart')
            ->where('cart.user = :user')
            ->setParameter('user', $user->getUuid(), UuidBinaryOrderedTimeType::NAME);

        return $qb->getQuery()->useQueryCache(true)->getResult();
    }

    public function emptyUserCart(User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(Cart::class, 'cart')
            ->where('cart.user = :user')
            ->setParameter('user', $user->getUuid(), UuidBinaryOrderedTimeType::NAME);

        return $qb->getQuery()->useQueryCache(true)->getResult();
    }
}