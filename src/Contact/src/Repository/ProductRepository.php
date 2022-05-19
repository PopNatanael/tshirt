<?php

declare(strict_types=1);

namespace Frontend\Contact\Repository;

use Frontend\Contact\Entity\Message;
use Doctrine\ORM;
use Doctrine\ORM\EntityRepository;
use Frontend\Contact\Entity\Product;

/**
 * Class ProductRepository
 * @package Frontend\Contact\Repository
 */
class ProductRepository extends EntityRepository
{
    public function getProducts()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('products')
            ->from(Product::class, 'products');

        return $qb->getQuery()->useQueryCache(true)->getResult();
    }

    public function saveProduct(Product $product)
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
    }
}
