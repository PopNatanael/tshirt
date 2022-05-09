<?php

namespace Frontend\Contact\Service;

use Frontend\Contact\Entity\Message;
use Frontend\Contact\Repository\MessageRepository;
use Doctrine\ORM\EntityNotFoundException;
use Frontend\Contact\Repository\ProductRepository;

/**
 * Class ProductServiceInterface
 * @package Frontend\Contact\Service
 */
interface ProductServiceInterface
{
    /**
     * @return ProductRepository
     */
    public function getRepository(): ProductRepository;
}
